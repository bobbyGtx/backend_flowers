<?php
function getProductInfo($link, $result, $productId, $languageTag=''){

  //переделать запрос на явные поля!
  include 'variables.php';
  $funcName = 'favoritesRequest_func';

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$productId) {$result['error']=true; $result['message'] = $errors['productIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql = "SELECT `id`,`name$languageTag` as `name`,`price`,`image`,`type_id`,`lightning$languageTag` as `lightning`,`humidity$languageTag` as `humidity`,`temperature$languageTag` as `temperature`,`height`,`diameter`,`url`,`count`,`disabled` FROM `products` WHERE `id` = $productId;";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;
  }

  if (mysqli_num_rows($sqlResult)===0){$result['error']=true; $result['code']=400;$result['message']=$errors['productNotFound'] . "($funcName)";goto endFunc;}

  $result['row'] = mysqli_fetch_assoc($sqlResult);

  endFunc:
  return $result;
}//Получение инфо о товаре по id
