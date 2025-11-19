<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, POST, GET, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Access-Token, X-Language");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/languageOp.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки

if ($method === 'OPTIONS') {
  http_response_code(200);//ответ на пробный запрос
  return;
} elseif ($method === 'POST') {
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/tokensOp.php';//Проверка токена
  include 'scripts/favoritesOp.php';
  include 'scripts/productsOp.php';
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
  $result = getProductShortInfo($link, $result, $postProductId, $reqLanguage);
  if ($result['error']) goto endRequest;

  //Добавление записи в избранное
  $result = addToFavorite($link,$result,$userId, $postProductId);
  if ($result['error']) goto endRequest;

  /* Ответ $result['product']{
    "id": "6660c46d5bd7273d906cdcce",
    "name": "Ливистона китайская",
    "url": "livistona_kitaiskaya",
    "image": "10-3.jpg",
    "price": 179
}*/

} elseif ($method === 'GET') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  include 'scripts/tokensOp.php';//Проверка токена
  include 'scripts/favoritesOp.php';
  $reqName = 'Favorites [GET]';
  $result = ['error' => false, 'code' => 200, 'message' => 'Request success!'];//Создание массива с ответом Ок
  //{"productId": "638672d5257c18cd625190ea"}
  
  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = 'DB connection Error! ' . $db_connect_response['message']; goto endRequest;}
  $result['headers']=$reqLanguage;
  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) {
    goto endRequest;//Если пришла ошибка - завршаем скрипт
  } else {
    if ($result['userId'] && $result['userPassword']){
      $userId = $result['userId'];
      $userPwd = $result['userPassword'];
      unset($result['userId']); unset($result['userPassword']);
    }else{
      $result['error']=true; $result['code'] = 500; $result['message'] = $critErr['userIdNotFound']."($reqName)"; goto endRequest;
    }
  }

  //составляем список избранного для пользователя
  $result = getUserFavorites($link,$result, $userId);
  if ($result['error']) goto endRequest;
  $favoriteList = $result['favoriteList']; unset($result['favoriteList']);

  //Подготовка запроса информации всех товаров из корзины пользователя
  $result = generateFavList($link,$result, $favoriteList, $reqLanguage);//вывод списка в переменной $result['favorites']
  if ($result['error']) goto endRequest;
  
} elseif ($method === 'DELETE'){
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/tokensOp.php';//Проверка токена
  include 'scripts/favoritesOp.php';
  //{"productId": "12"}
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

  if ($result['error']) goto endRequest;
  if ($result['userId'] && $result['userPassword']){
    $userId = $result['userId'];
    $userPwd = $result['userPassword'];
    unset($result['userId']); unset($result['userPassword']);
  }else{
    $result['error']=true; $result['code'] = 500; $result['message'] = 'User data not found in record! Critical error.'; goto endRequest;
  }

  $result = delFromFavorite($link, $result, $userId, $delProductId);
} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);