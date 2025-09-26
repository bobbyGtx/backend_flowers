<?php

function getDeliveryInfo($link, $result, $deliveryId){
  include 'variables.php';
  $funcName = 'getDeliveryInfo_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (empty($deliveryId) || intval($deliveryId)<1){$result['error']=true; $result['code']=500; $result['message'] = $errors['deliveryIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql = "SELECT * FROM `delivery_types` WHERE `id` = $deliveryId";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName) ($emessage)";goto endFunc;
  }
  $selectedDelivery = mysqli_fetch_array($sqlResult);//парсинг
  if ( !empty($selectedDelivery['id']) && intval($selectedDelivery['id'])===intval($deliveryId)){
    $result['selectedDelivery'] = $selectedDelivery;
  } else {
    $result['error']=true; $result['code']=500;$result['message']=$dbError['unexpResponse'] . "($funcName)";goto endFunc;
  }

  endFunc:
  return $result;
}//Получение инфо о доставке.
