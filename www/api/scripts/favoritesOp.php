<?php
function getUserFavorites($link, $result, $userId){
  //Возвращает массив с индексами товаров [1,2,3,4,5]
  include 'variables.php';
  $funcName = 'getUserFavorites'.'_func';

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql = "SELECT `product_id` FROM `favorites` WHERE `user_id` = $userId";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;
  }
  if (mysqli_num_rows($sqlResult)===0){$result['favorites'] = []; goto endFunc;}

  $rows = mysqli_fetch_all($sqlResult, MYSQLI_NUM);
  $favoriteList = array_column($rows, 0);// берём первый столбец
  $result['favoriteList'] = $favoriteList;

  endFunc:
  return $result;
}//Получение списка ID товаров из избранного пользователя

function generateFavList($link, $result, $favoriteList, $languageTag = ''){
  include 'variables.php';
  $funcName = 'generateFavList'.'_func';
  //вывод списка в переменной $result['favorites']

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}

  if (empty($favoriteList) || !is_array($favoriteList) || count($favoriteList)<1){
    $result['favorites'] = [];goto endFunc;
  }//Если переданный список пуст, возвращаем пустой массив

  //Подготовка запроса информации всех товаров из корзины пользователя
  $sqlStr='';//Переменная для создания условия запроса (всё что после WHERE)
  $productIDs = array_map('intval', $favoriteList);//Приводим всё к числам
  $idList = implode(',', $productIDs);// Делаем строку вида "1,2,3,4,5"


  $sql = "SELECT `id`,`name$languageTag` as `name`, `price`, `image`, `url`, `count`, `disabled` FROM `products` WHERE `id` IN ($idList);";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->execute(); 
    $response = $stmt->get_result();
    $numRows = $response->num_rows;
    $stmt->close();
  } catch (Exception $e) {
    $emessage = $e->getMessage();$result['error'] = true;
    $result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}

  if ($numRows===0){
    $result['error']=true; $result['code']=500; $result['message']=$errors['productsNotFound']; $result['favorites'] = [];goto endFunc;
  }// Если мы делали запрос, то избранное должно быть.

  $productsList = $response->fetch_all(MYSQLI_ASSOC);
  foreach($productsList as &$item){
    $item['ends'] = intval($item['count'])<$endsCount?true:false;
  }//Установка отметки о том, что товар заканчивается
  $result['favorites'] = $productsList;

  endFunc:
  return $result;
}//Формирование ответа с избранным пользователя

function addToFavorite($link, $result, $userId, $productId){
  include 'variables.php';
  $funcName = 'addToFavorite'.'_func';

  //вывод списка в переменной $result['favorites']
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}
  if (!$productId) {$result['error']=true; $result['message'] = $errors['productIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql = "INSERT IGNORE INTO `favorites`(`user_id`, `product_id`, `addDate`) VALUES ($userId, $productId,".time().");";
  try{
  $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['insertReqRejected'] . "($emessage))";goto endFunc;
  }

  endFunc:
  return $result;
}//добавление товара в избранное

function delFromFavorite($link, $result, $userId, $productId){
  include 'variables.php';
  $funcName = 'delFromFavorite'.'_func';

  //вывод списка в переменной $result['favorites']
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  settype($userId, 'integer');settype($productId, 'integer');
  if (!$userId) {$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}
  if (!$productId) {$result['error']=true; $result['message'] = $errors['productIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql = "DELETE FROM `favorites` WHERE `user_id` = $userId AND `product_id` = $productId ;";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['delReqRejected'] . "($funcName) ($emessage))";goto endFunc;
  }

  endFunc:
  return $result;
}//удаление товара из избранного