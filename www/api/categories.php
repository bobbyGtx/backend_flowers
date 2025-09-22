<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
  http_response_code(200);//ответ на пробный запрос
  return;
} elseif ($method === 'GET') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования

  $result = ['error' => false, 'code' => 200, 'message' => 'Request success!'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'DB connection Error! ' . $db_connect_response['message']; goto endRequest;
  }

  $sql= "SELECT `id`,`name`,`url` FROM `categories`;";
  $sqlResult = mysqli_query($link, $sql);
  $numRows = mysqli_num_rows($sqlResult);
  if ($numRows === 0) {
    $result['error']=true; $result['code'] = 400; $result['message'] = "DB return null records!"; goto endRequest;
  }
  $result['categories'] = mysqli_fetch_all($sqlResult, MYSQLI_ASSOC);//Парсинг
} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = 'Method Not Allowed';
}

endRequest:
if ($link) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);