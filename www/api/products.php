<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Access-Token, X-Language");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';
include 'scripts/languageOp.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки
//Переработать dbConnect($result);

if ($method === 'OPTIONS') {
  http_response_code(200);//ответ на пробный запрос
  return;
} elseif ($method === 'GET') {
  include_once 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include_once 'scripts/productsOp.php';

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
    if ($slug === 'recommended') {
      //categoryId - для выборки товаров из этой же категории, productId - для исключения выбранного товара из рекомендаций
      $result = getRecommendProducts($link, $result,$_GET['categoryId'],$_GET['productId'], $reqLanguage);
      if ($result['error']) goto endRequest;
      goto endRequest;
    }//обработчик запроса рекомендуемых товаров
    if ($slug === 'short-info') {
      $result = getProductCount($link, $result,$_GET['productId']);
      if ($result['error']) goto endRequest;
      goto endRequest;
    }//обработчик запроса получения короткой информации о товаре по id. disabled, count, price
    if ($slug === 'search'){
      $query = $_GET['query'] ?? null;  // 'flower'
      $result = searchProducts($link, $result, $query, $reqLanguage);
      if ($result['error']) goto endRequest;
      goto endRequest;
    }//обработчик запроса поиска товара
    //Получение информации о товаре по его url
    $result = getProductInfo($link, $result, $slug, $reqLanguage);
    if ($result["error"]) goto endRequest;
  } else {
    $result = getProducts($link, $result, $_GET, $reqLanguage);
    if ($result['error']) goto endRequest;
  }//обработчик запроса всех товаров по фильтрам url = /products.php

}elseif ($method === 'POST'){
  //Получение информации о товарах по их ID. Работа с офлайн корзиной и переводом.
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/productsOp.php';

  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['reqSuccess']];

  $postData = json_decode(file_get_contents('php://input'), true);//получение запроса и парсинг
  if (!isset($postData['productIDs']) || !is_array($postData['productIDs']) || count($postData['productIDs'])===0){
    $result['error']=true; $result['code']=400; $result['message']=$dataErr['notRecognized']; goto endRequest;
  }
  $productIDs = [];
  foreach ($postData['productIDs'] as $productID) {
    if (filter_var($productID, FILTER_VALIDATE_INT) !== false) $productIDs[] = intval($productID);
  }
  if (count($productIDs)===0 || count($productIDs)!==count($postData['productIDs'])){
    $result['error']=true; $result['code']=400;$result['message']=$dataErr['notRecognized'];
    goto endRequest;
  }

  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 400; $result['message'] = $dbError['connectionError'] . $db_connect_response['message']; goto endRequest;
  }

  $result = getProductsInfo($link,$result, $productIDs, $reqLanguage);
  if ($result["error"]) goto endRequest;

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result, JSON_UNESCAPED_UNICODE);