<?php
/*
 * Если $disabledChecking = false - то проверка на disabled у типа доставки не возвращает ошибку
 */
function getDeliveryInfo($link, $result, $deliveryId, $languageTag = '', $getInfo=false, $disabledChecking = true){
  include 'variables.php';
  $funcName = 'getDeliveryInfo_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (empty($deliveryId) || intval($deliveryId)<1){$result['error']=true; $result['code']=500; $result['message'] = $errors['deliveryIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql ="SELECT `id`,`deliveryType$languageTag` as `deliveryType`,`addressNeed`,`delivery_price`,`low_price`,`lPMinPrice`,`disabled` FROM `delivery_types` WHERE `id` = $deliveryId;"; 
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName) ($emessage)";goto endFunc;
  }
  if (mysqli_num_rows($sqlResult)<>1){
    //Сообщение об ошибке используется для сравнения в другой функции
    $result['error']=true;$result['code']=400;$result['message']=$errors['deliveryIdNotFound'];goto endFunc;
  }
  $selectedDelivery = mysqli_fetch_assoc($sqlResult);//парсинг
  if ( !empty($selectedDelivery['id']) && intval($selectedDelivery['id'])===intval($deliveryId)){
    if ($disabledChecking && $selectedDelivery['disabled']){
      $result['error']=true; $result['code']=400;$result['message']=$infoErrors['delivNotPos'];goto endFunc;
    }//проверка доступности метода доставки, если включена
    if ($getInfo)$result['selectedDelivery'] = $selectedDelivery;//Возвращаем тип доставки если необходимо
  } else {
    $result['error']=true; $result['code']=500;$result['message']=$dbError['unexpResponse'] . "($funcName)";goto endFunc;
  }
  //$result['selectedDelivery']
  endFunc:
  return $result;
}//Получение инфо о доставке.
