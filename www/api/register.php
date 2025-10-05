<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';//файл с генераторами строк

if ('OPTIONS' === $method) {
  http_response_code(200);//ответ на пробный запрос
  return;
} elseif ('POST' === $method) {
  include 'scripts/generators.php';//файл с генераторами строк
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/userOp.php';//Проверка email и т.д.
  include 'scripts/cartOp.php';//работа с корзинойтоваров

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
  $firstNamePost = $postDataJson["firstName"];//логин из запроса
  $lastNamePost = $postDataJson["lastName"];//логин из запроса
  $emailPost = $postDataJson["email"];//логин из запроса
  $phonePost = $postDataJson["phone"];//логин из запроса
  $passwordPost = $postDataJson["password"];//пароль из запроса
  $passwordRepeatPost = $postDataJson["passwordRepeat"];//пароль из запроса

  if (empty($firstNamePost) || empty($lastNamePost) || empty($emailPost) || empty($phonePost) || empty($passwordPost) || empty($passwordRepeatPost)) {
    $result['error']=true; $result['code'] = 400; $result['message'] = 'Request parameters not recognized!'; goto endRequest;
  } else {
    $messages = [];
    if (!preg_match($firstNameRegEx, $firstNamePost)) {
      $result['error']=true; $messages[] = 'Invalid First Name!';
    }//проверка на соответствие имени. Собираем все ошибки
    if (!preg_match($lastNameRegEx, $lastNamePost)) {
      $result['error']=true; $messages[] = 'Invalid Last Name!';
    } //проверка на соответствие фамилии. Собираем все ошибки
    if (!preg_match($emailRegEx, $emailPost)) {
      $result['error']=true; $messages[] ='EMail not acceptable!';
    }//проверка на соответствие требованиям почты
    if (!preg_match($telephoneRegEx, $phonePost)) {
      $result['error']=true; $messages[] = 'Invalid phone format!';
    } //проверка на соответствие формата телефона
    if (!preg_match($passwordRegEx, $passwordPost)) {
      $result['error']=true; $messages[] ='Password not acceptable!';
    }//проверка на соответствие требованиям почты
    if ($passwordPost <> $passwordRepeatPost) {
      $result['error']=true; $messages[] ='Passwords do not match!';
    } //проверка идентичности паролей
    if ($result['error']==true) {
      $result['code'] = 406;$result['message'] = 'Not Acceptable!'; $result['messages'] = $messages; goto endRequest;//error 406: unacceptable format
    }
  }
  
  $passwordPostEnc = __encode($passwordPost, $key);//шифрование пароля
  
  $result = checkEmail($link,$result,$emailPost,false);//проверка уникальности почты
  if ($result['error']) {goto endRequest;}
//добавление пользователя
  $sql="INSERT INTO `$userTableName`(`firstName`, `lastName`, `email`, `phone`, `password`, `updatedAt`) VALUES (?,?,?,?,?,?)";
  $timeStamp=time();
  try{
  mysqli_report(MYSQLI_REPORT_ALL);
  $stmt = mysqli_prepare($link, $sql);
  mysqli_stmt_bind_param($stmt, 'sssssi',$firstNamePost,$lastNamePost,$emailPost,$phonePost,$passwordPostEnc,$timeStamp);
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

  //Создание записи в таблице корзин
  $result = createUserCart($link, $result, $newUserId);
  if ($result['error']){
    goto endRequest;
  }
   
}else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);
