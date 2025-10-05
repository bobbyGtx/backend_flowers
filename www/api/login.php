<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';//файл с генераторами строк

if ('OPTIONS' === $method) {
  http_response_code(200); return;//ответ на пробный запрос
} elseif ('POST' === $method) {
  include 'scripts/generators.php';//файл с генераторами строк
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/tokensOp.php';
  include 'scripts/userOp.php';

  $result = ['error' => false, 'code' => 200, 'message' => 'Authorization success!'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;
  }

  $postData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($postData, true);//парсинг параметров запроса
  $loginPost = $postDataJson["email"];//логин из запроса
  $passwordPost = $postDataJson["password"];//пароль из запроса
  if (empty($passwordPost) || empty($loginPost) ) {
    $result['error']=true; $result['code'] = 400; $result['message'] = $dataErr['notRecognized']; goto endRequest;
  }else {
    //проверка на соответствие минимальным требованиям почты и пароля перед запросом в БД. Если нет - возвращаем ошибку!
    if (!preg_match($emailRegEx, $loginPost) || !preg_match($passwordRegEx, $passwordPost)) {
      $result['error']=true; $result['code'] = 401; $result['message'] = $authError['loginOrPassNA']; goto endRequest;
    } 
  }

  $result = login($link, $result, $loginPost, $passwordPost);
  if ($result['error']) goto endRequest;
  
  $userId = $result['user']['userId'];

  $result = generateTokens($link, $result, $userId);
  if ($result['error']) goto endRequest;
  $tokens = $result['tokens'];unset($result['tokens']);

  $result['user'] +=$tokens;

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link)mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);
