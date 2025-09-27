<?php

function checkPayment($link, $result, $paymentId, $getInfo=false){
  include 'variables.php';
  $funcName = 'checkPayment_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (empty($paymentId) || intval($paymentId)<1){$result['error']=true; $result['code']=500; $result['message'] = $errors['paymentIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql = "SELECT * FROM `payment_types` WHERE `id` = $paymentId";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName) ($emessage)";goto endFunc;
  }
  $selectedPayment = mysqli_fetch_array($sqlResult);//парсинг
  if (!empty($selectedPayment['id']) && intval($selectedPayment['id'])===intval($paymentId)){
    if ($getInfo){$result['selectedPayment'] = $selectedPayment;}
  } else {
    $result['error']=true; $result['code']=500;$result['message']=$dbError['unexpResponse'] . "($funcName)";goto endFunc;
  }

  if ($selectedPayment['disabled']){
    $result['error']=true; $result['code']=500;$result['message']=$infoErrors['paymentNotPos'] . "($funcName)";goto endFunc;
  }

  endFunc:
  return $result;
}//Получение инфо о выбранном методе оплаты.
