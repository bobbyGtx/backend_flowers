<?php

function checkPayment($link, $result, $paymentId, $languageTag = '', $getInfo=false, $disabledChecking = true){
  include 'variables.php';
  $funcName = 'checkPayment_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (empty($paymentId) || intval($paymentId)<1){$result['error']=true; $result['code']=500; $result['message'] = $errors['paymentIdNotFound'] . " ($funcName)"; goto endFunc;}

  $sql="SELECT `id`, `paymentType$languageTag` as `paymentType`,`disabled` FROM `payment_types` WHERE `id` = $paymentId;";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName) ($emessage)";goto endFunc;
  }
  if (mysqli_num_rows($sqlResult)<>1){
    //Сообщение об ошибке используется для сравнения в другой функции
    $result['error']=true;$result['code']=400;$result['message']=$errors['paymentIdNotFound'];goto endFunc;
  }
  $selectedPayment = mysqli_fetch_assoc($sqlResult);//парсинг
  if (!empty($selectedPayment['id']) && intval($selectedPayment['id'])===intval($paymentId)){
    if ($disabledChecking && $selectedPayment['disabled']){
      $result['error']=true; $result['code']=400;$result['message']=$infoErrors['paymentNotPos'];goto endFunc;
    }//проверка доступности метода доставки, если включена
    if ($getInfo){$result['selectedPayment'] = $selectedPayment;}
  } else {
    $result['error']=true; $result['code']=500;$result['message']=$dbError['unexpResponse'] . "($funcName)";goto endFunc;
  }
  //$result['selectedPayment']
  endFunc:
  return $result;
}//Получение инфо о выбранном методе оплаты.
