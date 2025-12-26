<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Language");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';//файл с генераторами строк

if ('OPTIONS' === $method) {
  http_response_code(200); return;//ответ на пробный запрос
} elseif ('POST' === $method) {
  include __DIR__.'/scripts/generators.php';//файл с генераторами строк
  include __DIR__.'/scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include __DIR__.'/scripts/tokensOp.php';
  include __DIR__.'/scripts/userOp.php';
  include_once __DIR__.'/utils/checkUtils.php';

  $result = ['error' => false, 'code' => 200, 'message' => 'Authorization success!'];//Создание массива с ответом Ок

  $postData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($postData, true);//парсинг параметров запроса
  $loginPost = strtolower($postDataJson["email"]) ?? null;//логин из запроса
  $passwordPost = $postDataJson["password"] ?? null;//пароль из запроса

  if (empty($passwordPost) || empty($loginPost)) {
    $result['error']=true; $result['code'] = 400; $result['message'] = $dataErr['notRecognized']; goto endRequest;
  }

  if (!preg_match($emailRegEx, $loginPost)) {
    $result['error'] = true;$result['code'] = 401;$result['message'] = $authError['emailNotValid'];goto endRequest;
  }
  if (!preg_match($passwordRegEx, $passwordPost)) {
    $result['error'] = true;$result['code'] = 401;$result['message'] = $authError['wrongPassword'];goto endRequest;
  } //Всё равно отдаем ошибку об ошибочном пароле

  $loginProtection = checkLoginProtection($loginPost);
  if ($loginProtection>0){
    $result['error'] = true;$result['code'] = 429;$result['message'] = $authError['tooManyFailedLogins'];
    $result['timer'] = $loginProtection;goto endRequest;
  }

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;
  }

  $result = login($link, $result, $loginPost, $passwordPost);
  if ($result['error']){
    if ($result['code'] >= 400 && $result['code'] <= 499) {
      $loginProtection=registerFailedLogin($loginPost);
      if ($loginProtection)$result['timer'] = $loginProtection;
    }
    goto endRequest;
  }
  clearLoginProtection($loginPost);
  
  $userId = $result['user']['userId']; unset($result['user']);
  $result = generateTokens($link, $result, $userId);//$result['user'] = ['userId' => $userId, 'accessToken' => $accessToken, 'refreshToken' => $refreshToken];
  if ($result['error']) goto endRequest;

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link)mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);
