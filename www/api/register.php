<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Language");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';//файл с генераторами строк
include_once 'scripts/languageOp.php';
include_once 'scripts/enums.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки

if ('OPTIONS' === $method) {
  http_response_code(200);return;
} elseif ('POST' === $method) {
  include_once 'scripts/generators.php';//файл с генераторами строк
  include_once 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include_once  'scripts/userOp.php';//Проверка email и т.д.
  include_once 'scripts/cartOp.php';//работа с корзиной товаров

  $result = ['error' => false, 'code' => 200, 'message' => 'User registered!'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;
  }

  $postData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($postData, true);//парсинг параметров запроса
  $emailPost = $postDataJson["email"];//логин из запроса
  $passwordPost = $postDataJson["password"];//пароль из запроса
  $passwordRepeatPost = $postDataJson["passwordRepeat"];//пароль из запроса
  $agree = $postDataJson["agree"];//соглашения

  if (empty($emailPost) || empty($passwordPost) || empty($passwordRepeatPost)) {
    $result['error']=true; $result['code'] = 400; $result['message'] = $dataErr['notRecognized']; goto endRequest;
  } else {
    $messages = [];
    if (!preg_match($emailRegEx, $emailPost)) {
      $result['error']=true; $messages[] ='E-Mail is incorrect';
    }//проверка на соответствие требованиям почты
    if (!preg_match($passwordRegEx, $passwordPost)) {
      $result['error']=true; $messages[] ='Password is too short';
    }//проверка на соответствие требованиям почты
    if ($passwordPost <> $passwordRepeatPost) {
      $result['error']=true; $messages[] ="Passwords don't match";
    } //проверка идентичности паролей
    if (!boolval($agree)) {
      $result['error']=true; $messages[] ="Agreements not accepted";
    } //проверка принятия условий
    if ($result['error']==true) {
      $result['code'] = 406;$result['message'] = 'Data not Acceptable!'; $result['messages'] = $messages; goto endRequest;//error 406: unacceptable format
    }
  }
  
  $result = checkEmail($link,$result,$emailPost,false);//проверка уникальности почты
  if ($result['error']) goto endRequest;
  //добавление пользователя
  $result = addUser($result, $link,$emailPost,$passwordPost);
  if ($result['error']) goto endRequest;
  $newUserId = $result['newUserId']; unset($result['newUserId']);

  //Создание записи для верификации email
  $operationType = UserOpTypes::verifyEmail;

  $result = createUserOpRecord($result,$link,$newUserId,$operationType);
  if ($result['error']) goto endRequest;
  $verifyEmailData = $result['data']; unset($result['data']);

  $result = sendRegisterVerificationEmail($result,$emailPost,$verifyEmailData['token'],$verifyEmailData['createdAt']);
  if ($result['error']) goto endRequest;
  //Запись в таблице с корзиной создается при первом запросе корзины или при rebaseCart(post)
}else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);
