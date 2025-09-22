<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];


if ('OPTIONS' === $method) {
  http_response_code(200);//ответ на пробный запрос
  return;
} elseif ('POST' === $method) {
  include 'scripts/generators.php';//файл с генераторами строк
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки

  $result = ['error' => false, 'code' => 200, 'message' => 'Authorization success!'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'DB connection Error! ' . $db_connect_response['message']; goto endRequest;
  }

  $settings = getSettings($link);//Получение ключа шифрования. 
  if ($settings == false) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'Error while requesting settings from DB'; goto endRequest;
  }

  $key = $settings['secretKey'];//ключ шифрования паролей

  $postData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($postData, true);//парсинг параметров запроса
  $loginPost = $postDataJson["email"];//логин из запроса
  $passwordPost = $postDataJson["password"];//пароль из запроса
  if (empty($passwordPost) || empty($loginPost) ) {
    $result['error']=true; $result['code'] = 400; $result['message'] = 'Request parameters not recognized!'; goto endRequest;
  }else {
    //проверка на соответствие минимальным требованиям почты и пароля перед запросом в БД. Если нет - возвращаем ошибку!
    if (!preg_match($emailRegEx, $loginPost) || !preg_match($passwordRegEx, $passwordPost)) {
      $result['error']=true; $result['code'] = 401; $result['message'] = 'Login or password not acceptable!'; goto endRequest;
    } 
  }
  $passwordPostEnc = __encode($passwordPost, $key);//шифрование пароля

  $sql = "SELECT `id`,`firstName`,`lastName`,`email`,`password`,`emailVerification`,`blocked`  FROM users WHERE email = '" . $loginPost . "' AND `password` = '" . $passwordPostEnc . "'";
  $sqlResult = mysqli_query($link, $sql);
  $numRows = mysqli_num_rows($sqlResult);
  if ($numRows <> 1) {
    $result['error']=true; $result['code'] = 401; $result['message'] = 'Login or password not correct!'; goto endRequest;
  }
  $row = mysqli_fetch_array($sqlResult);//парсинг
  if (empty($row['id']) || empty($row['email']) || empty($row['password'])) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'Unable to recognize data from database!'; goto endRequest;
  }
  if(boolval($row['blocked'])===true){
    $result['error']=true; $result['code'] = 403; $result['message'] = 'User blocked!'; goto endRequest;
  }
  $accessToken = generate_string($accTokenLenght);//Генерация accessToken согласно длины из настроек
  $refreshToken = generate_string($refrTokenLenght);//Генерация refreshToken согласно длины из настроек
  $nowTimeStamp = time();
  $accessTokenEndTime = $nowTimeStamp + $accTokenLife;//конечная дата действия токена
  $refreshTokenEndTime = $nowTimeStamp + $refrTokenLife;//конечная дата действия токена
  $userId = $row['id'];
  $userFirstName = $row['firstName'];$userLastName = $row['lastName'];
  $sql = "UPDATE $userTableName SET `$accTokenField` = '" . $accessToken . "', `$accTokenLifeField` = '" . $accessTokenEndTime . "', `$refreshTokenField` = '" . $refreshToken . "' ,`$refrTokenLifeField` = '" . $refreshTokenEndTime . "' WHERE `$userTableName`.`id` = " . $userId;
  $sqlResultSaveTokens = mysqli_query($link, $sql);//сохраняем токены и даты

  if (empty($sqlResultSaveTokens)) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'Request rejected by save data to database.'; goto endRequest;
  }

  $result['user'] = ['userId' => $userId, 'firstName' => $userFirstName, 'lastName' => $userLastName, 'accessToken' => $accessToken, 'refreshToken' => $refreshToken];
} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = 'Method Not Allowed';
}

endRequest:
if ($link)mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);
