<?php
//Переделать ответы!
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods:  OPTIONS, POST, PATCH, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Access-Token, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];
if ('OPTIONS' === $method) {
  http_response_code(200); return;
} elseif ('POST' === $method) {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  include 'scripts/tokensOp.php';//Проверка токена
  $result = ['error' => false, 'code' => 200, 'message' => 'Data added successfully.'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'DB connection Error! ' . $db_connect_response['message']; goto endRequest;
  }
  $result = checkToken($link, $result, getallheaders());
  if ($result['error']) {
    goto endRequest;
  }//Если пришла ошибка - завршаем скрипт

  
} elseif ('PATCH' === $method) {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  include 'scripts/tokensOp.php';//Проверка токена
  $result = ['error' => false, 'code' => 200, 'message' => 'Record changed!'];

  $db_connect_response = dbConnect();//Подключение к БД
  $link = $db_connect_response['link'];
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'DB connection Error! ' . $db_connect_response['message']; goto endRequest;
  }

  $result = checkToken($link, $result, getallheaders());
  if ($result['error']) {
    goto endRequest;
  }//Если токен не валиден - завершаем всё

} elseif ('DELETE' === $method) {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  include 'scripts/tokensOp.php';//Проверка токена

  $result = ['error' => false, 'code' => 200, 'message' => 'Record deleted successfully.'];

  $db_connect_response = dbConnect();//Подключение к БД
  $link = $db_connect_response['link'];
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'DB connection Error! ' . $db_connect_response['message']; goto endRequest;
  }

  $result = checkToken($link, $result, getallheaders());
  if ($result['error']) {
    goto endRequest;
  }//Если токен не валиден - завершаем всё

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = 'Method Not Allowed';
}

endRequest:
if ($link)mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);