<?php

function getDeliveryInfo($link, $result, $deliveryId, $languageTag = '', $getInfo=false, $disabledChecking = true){
  include 'variables.php';
  $funcName = 'getDeliveryInfo'.'_func';
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
    $result['error']=true;$result['code']=406;$result['message']=$errors['deliveryIdNotFound']."($reqName) (Getting ID: $deliveryId)";goto endFunc;
  }
  $selectedDelivery = mysqli_fetch_assoc($sqlResult);//парсинг
  if ( !empty($selectedDelivery['id']) && intval($selectedDelivery['id'])===intval($deliveryId)){
    if ($disabledChecking && $selectedDelivery['disabled']){
      $result['error']=true; $result['code']=500;$result['message']=$infoErrors['delivNotPos'];goto endFunc;
    }//проверка доступности метода доставки, если включена
    $result['selectedDelivery'] = $selectedDelivery;
  } else {
    $result['error']=true; $result['code']=500;$result['message']=$dbError['unexpResponse'] . "($funcName)";goto endFunc;
  }
  //$result['selectedDelivery']
  endFunc:
  return $result;
}//Получение инфо о доставке.
