<?php
//Переделать ответы!
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Access-Token, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';

if ($method === 'OPTIONS') {
  http_response_code(200); return;
} elseif ($method === 'POST') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки
  include 'scripts/generators.php';//файл с генераторами строк
  include 'scripts/tokensOp.php';
  $result = ['error' => false, 'code' => 200, 'message' => 'Token refreshed.'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message'];goto endRequest;
  }

  $postData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($postData, true);//парсинг параметров запроса
  $refreshToken = $postDataJson["refreshToken"];//токен из запроса

  $result = checkRefreshToken($link, $result, $refreshToken);
  if ($result["error"]) goto endRequest;

  $userId = $result['userId']; unset($result['userId']);
  
  $result = generateTokens($link, $result, $userId);
  if ($result['error']) goto endRequest;
  $tokens = $result['tokens'];//В результате есть ответ с новыми токенами + userId
  
  //$result['tokens'] = ['accessToken' => $newAccessToken, 'refreshToken' => $newRefreshToken, 'userId' => $userId];

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link)mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);
