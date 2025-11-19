<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Access-Token, X-Language");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';
include 'scripts/languageOp.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки

if ($method === 'OPTIONS') {
  http_response_code(200);
  return;//ответ на пробный запрос
} elseif ($method === 'GET') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования

  $result = ['error' => false, 'code' => 200, 'message' => 'Request success!'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect();
  $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = 'DB connection Error! ' . $db_connect_response['message'];
    goto endRequest;
  }

  // запрос категорий
  $sql = "SELECT `id`,`name$reqLanguage` as `name`,`url` FROM `categories`;";
  try {
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['selReqRejected'] . "(Types->Categories) ($emessage))";
    goto endRequest;
  }

  $numRows = mysqli_num_rows($sqlResult);
  if ($numRows === 0) {
    $result['error'] = true;
    $result['code'] = 400;
    $result['message'] = "DB return null records from Table 'Categories'!";
    goto endRequest;
  }
  $catResponse = mysqli_fetch_all($sqlResult, MYSQLI_ASSOC);//Парсинг
  $categories = [];
  foreach ($catResponse as $item) {
    $categories[$item['id']] = $item;
  }//преобразование массива для подстановки по ключу

  //запрос типов
  $sql = "SELECT `id`,`name$reqLanguage` as `name`,`url`,`category_id` FROM `types`;";
  try {
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['selReqRejected'] . "(Types) ($emessage))";
    goto endRequest;
  }

  $numRows = mysqli_num_rows($sqlResult);
  if ($numRows === 0) {
    $result['error'] = true; $result['code'] = 400; $result['message'] = "DB return null records from table 'Types'! ";
    goto endRequest;
  }
  $types = mysqli_fetch_all($sqlResult, MYSQLI_ASSOC);//Парсинг

  $typesFormat = [];
  foreach ($types as $value) {
    $akkumulator = $value;
    $akkumulator['category'] = $categories[$value['category_id']];
    unset($akkumulator['category_id']);
    $typesFormat[] = $akkumulator;
  }
  $result['types'] = $typesFormat;

} else {
  $result['error'] = true;$result['code'] = 405;$result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link)
  mysqli_close($link);
http_response_code($result['code']);
unset($result['code']);
echo json_encode($result);