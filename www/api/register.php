<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Language");

$method = $_SERVER['REQUEST_METHOD'];
include_once 'scripts/variables.php';//файл с генераторами строк
include 'scripts/languageOp.php';
include_once 'scripts/enums.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки

if ('OPTIONS' === $method) {
  http_response_code(200);return;
} elseif ('POST' === $method) {
  include_once 'scripts/generators.php';//файл с генераторами строк
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/userOp.php';//Проверка email и т.д.
  include 'scripts/cartOp.php';//работа с корзиной товаров

  $result = ['error' => false, 'code' => 200, 'message' => 'User registered!'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;
  }

  $settings = getSettings($link);//Получение ключа шифрования. 
  if ($settings == false) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbrequestSettings']; goto endRequest;
  }

  $key = $settings['secretKey'];//ключ шифрования паролей

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
  
  $passwordPostEnc = __encode($passwordPost, $key);//шифрование пароля
  
  $result = checkEmail($link,$result,$emailPost,false);//проверка уникальности почты
  if ($result['error']) goto endRequest;
//добавление пользователя
  $sql="INSERT INTO `$userTableName`(`email`, `password`, `updatedAt`) VALUES (?,?,?)";
  $timeStamp=time();
  try{
    mysqli_report(MYSQLI_REPORT_ALL);
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'ssi',$emailPost,$passwordPostEnc,$timeStamp);
    mysqli_stmt_execute($stmt);
    $newUserId = mysqli_insert_id($link);
    mysqli_stmt_close($stmt);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['insertReqRejected'] . "($emessage))";goto endRequest;
  }

  if (empty($newUserId) && $newUserId<1){
    $result['error']=true; $result['code']=500; $result['message']="Problem with UserID. Creating Cart record in DB impossible. ($emessage))";goto endRequest;
  }
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
