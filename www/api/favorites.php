<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, POST, GET, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
  http_response_code(200);//ответ на пробный запрос
  return;
} elseif ($method === 'POST') {
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/tokensOp.php';//Проверка токена

  $result = ['error' => false, 'code' => 200, 'message' => 'Addet to favorites'];//Создание массива с ответом Ок

  //{"productId": "638672d5257c18cd625190ea"}
  //Обработка входных данных
  $postData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($postData, true);//парсинг параметров запроса
  settype($postDataJson['productId'], 'integer');//защита от инъекции
  $postProductId = $postDataJson['productId'];
  if ($postProductId<1){$result['error']=true; $result['code'] = 400; $result['message'] = 'Request parameters (productId) not recognized!'; goto endRequest;}

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = 'DB connection Error! ' . $db_connect_response['message']; goto endRequest;}

  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) {
    goto endRequest;//Если пришла ошибка - завршаем скрипт
  } else {
    if ($result['userId'] && $result['userPassword']){
      $userId = $result['userId'];
      $userPwd = $result['userPassword'];
      unset($result['userId']); unset($result['userPassword']);
    }else{
      $result['error']=true; $result['code'] = 500; $result['message'] = 'User data not found in record! Critical error.'; goto endRequest;
    }
  }

  //ищем инфо о товаре в базе для проверки наличия в базе и ответа
  $sql = "SELECT `id`,`name`,`price`,`image`,`url` FROM `products` WHERE `id` = $postProductId;";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($emessage))";goto endRequest;
  }

  if (mysqli_num_rows($sqlResult)===0){$result['error']=true; $result['code']=400;$result['message']=$errors['productNotFound'];}
  $row = mysqli_fetch_array($sqlResult);//парсинг строки
  $product['id'] = $row['id'];
  $product['name'] =$row['name'];
  $product['price'] =$row['price'];
  $product['image'] =$row['image'];
  $product['url'] =$row['url'];

  //Добавление записи в избранное
  $sql = "INSERT INTO `favorites`(`user_id`, `product_id`, `addDate`) VALUES ($userId, $postProductId,".time().");";
  try{
  $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['insertReqRejected'] . "($emessage))";goto endRequest;
  }

  $result['product'] = $product;

  /* Ответ{
    "id": "6660c46d5bd7273d906cdcce",
    "name": "Ливистона китайская",
    "url": "livistona_kitaiskaya",
    "image": "10-3.jpg",
    "price": 179
}*/

} elseif ($method === 'GET') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  include 'scripts/tokensOp.php';//Проверка токена
  $result = ['error' => false, 'code' => 200, 'message' => 'Request success!'];//Создание массива с ответом Ок
  //{"productId": "638672d5257c18cd625190ea"}
  
  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = 'DB connection Error! ' . $db_connect_response['message']; goto endRequest;}

  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) {
    goto endRequest;//Если пришла ошибка - завршаем скрипт
  } else {
    if ($result['userId'] && $result['userPassword']){
      $userId = $result['userId'];
      $userPwd = $result['userPassword'];
      unset($result['userId']); unset($result['userPassword']);
    }else{
      $result['error']=true; $result['code'] = 500; $result['message'] = 'User data not found in record! Critical error.'; goto endRequest;
    }
  }

  //составляем список избранного для пользователя
  $sql = "SELECT `id`,`product_id` FROM `favorites` WHERE `user_id` = $userId";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($emessage))";goto endRequest;
  }
  if (mysqli_num_rows($sqlResult)===0){$result['favorites'] = []; goto endRequest;}

  $rows = mysqli_fetch_all($sqlResult, MYSQLI_ASSOC);//парсинг строк
  //Подготовка запроса информации всех товаров из корзины пользователя
  $sqlStr='';//Переменная для создания условия запроса (всё что после WHERE) 
  $j=0;
  $quantities=[];
  foreach($rows as $value){
    $itemID = $value['product_id'];
    settype($itemID, 'integer');
    if (preg_match('/^[0-9]+$/', $itemID)){
      if ($j===0){
        $sqlStr="`id`= $itemID";
      }else{
        $sqlStr=$sqlStr." OR `id`= $itemID";
      }
      $j++;
    }
  }

  $sql = "SELECT `id`,`name`, `price`, `image`, `url`, `count`,`disabled` FROM `products` WHERE $sqlStr;";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected']."($emessage))";
    goto endRequest;
  }

  if (mysqli_num_rows($sqlResult)===0){
    $result['error']=true; $result['code']=500; $result['message']=$errors['productsNotFound']; $result['favorites'] = [];goto endRequest;
  }// Если мы делали запрос, то избранное должно быть.

  $productsList = mysqli_fetch_all($sqlResult, MYSQLI_ASSOC);//парсинг строк
  //$productsList = json_decode($rows,true);//парсинг строк

  foreach($productsList as &$item){
    if (intval($item['count'])<$endsCount){
      $item['ends'] = true;
    }else{
      $item['ends'] = false;
    }
  }

  $result['favorites'] =$productsList;
  
} elseif ($method === 'DELETE'){
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/tokensOp.php';//Проверка токена
  //{"productId": "638672d5257c18cd625190ea"}
  $result = ['error' => false, 'code' => 200, 'message' => 'Record deleted'];//Создание массива с ответом Ок

  //Обработка входных данных
  $delData = file_get_contents('php://input');//получение запроса
  $delDataJson = json_decode($delData, true);//парсинг параметров запроса
  settype($delDataJson['productId'], 'integer');//защита от инъекции
  $delProductId = $delDataJson['productId'];
  if ($delProductId<1){$result['error']=true; $result['code'] = 400; $result['message'] = 'Request parameters (productId) not recognized!'; goto endRequest;}

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = 'DB connection Error! ' . $db_connect_response['message']; goto endRequest;}

  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) {
    goto endRequest;//Если пришла ошибка - завршаем скрипт
  } else {
    if ($result['userId'] && $result['userPassword']){
      $userId = $result['userId'];
      $userPwd = $result['userPassword'];
      unset($result['userId']); unset($result['userPassword']);
    }else{
      $result['error']=true; $result['code'] = 500; $result['message'] = 'User data not found in record! Critical error.'; goto endRequest;
    }
  }

  $sql = "DELETE FROM `favorites` WHERE `user_id` = $userId AND `product_id` = $delProductId ;";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['delReqRejected'] . "($emessage))";goto endRequest;
  }
} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);