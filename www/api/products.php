<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';
include 'scripts/languageOp.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки
//Переработать dbConnect($result);

if ($method === 'OPTIONS') {
  http_response_code(200);//ответ на пробный запрос
  return;
} elseif ($method === 'GET') {
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/productsOp.php';

  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['reqSuccess']];//Создание массива с ответом Ок
  $slug = $_GET['slug'] ?? null;

  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 400; $result['message'] = $dbError['connectionError'] . $db_connect_response['message']; goto endRequest;
  }

  if ($slug) {
    $slug = htmlspecialchars($slug);
    if ($slug === 'best') {
      $result = getBestProducts($link, $result, $reqLanguage);
      if ($result['error']) goto endRequest;
      
      goto endRequest;
    }//обработчик запроса лучших товаров
    if ($slug === 'search'){
      $query = $_GET['query'] ?? null;  // 'flower'
      $result = searchProducts($link, $result, $query, $reqLanguage);
      if ($result['error']) goto endRequest;
      goto endRequest;
    }//обработчик запроса поиска товара
    //Получение информации о товаре по ег url
    $result = getProductInfo($link, $result, $slug, $reqLanguage);
    if ($result["error"]) goto endRequest;
  } else {
    $result = getProducts($link, $result, $_GET, $reqLanguage);
    if ($result['error']) goto endRequest;
  }//обработчик запроса всех товаров по фильтрам url = /products.php

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);