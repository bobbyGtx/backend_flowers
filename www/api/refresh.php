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
  http_response_code(200);
  return;
} elseif ($method === 'POST') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки
  include 'scripts/generators.php';//файл с генераторами строк
  $result = ['error' => false, 'code' => 200, 'message' => 'Token refreshed.'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'DB connection Error! ' . $db_connect_response['message'];goto endRequest;
  }

  $postData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($postData, true);//парсинг параметров запроса
  $refreshToken = $postDataJson["refreshToken"];//токен из запроса

  if (empty($refreshToken) || !preg_match($refreshTokenRegEx, $refreshToken)) {
    $result['error']=true; $result['code'] = 401; $result['message'] = 'Token invalid! (Unable to recognize Token)';goto endRequest;
  }

  $sql = "SELECT `id`,`$refreshTokenField`,`$refrTokenLifeField` FROM `$userTableName` WHERE `$refreshTokenField` = '" . $refreshToken . "'";
  $sqlSelRecord = mysqli_query($link, $sql);//выполняем запрос
  $numRows = mysqli_num_rows($sqlSelRecord);
  if (empty($sqlSelRecord)) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'Request rejected by database. (Check token.)';goto endRequest;
  }
  if ($numRows <> 1) {
    $result['error']=true; $result['code'] = 401; $result['message'] = 'Token invalid!';goto endRequest;
  }//Совпадений в базе не найдено

  $record = mysqli_fetch_array($sqlSelRecord);//парсинг
  $userId = $record['id'];
  $refreshToken = $record[$refreshTokenField];
  $refrTokenTime = $record[$refrTokenLifeField];
  if (empty($userId) || empty($refreshToken)) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'Unable to recognize data from database!';goto endRequest;
  }
  if ((time() - $refrTokenTime) > 0) {
    //рефреш токен просрочен. Удаляем токены из базы
    $sqlClearTokensRequest = "UPDATE `$userTableName` SET `$accTokenField` = '', `$accTokenLifeField` = '', `$refreshTokenField` = '' ,`$refrTokenLifeField` = '' WHERE `$userTableName`.`id` = " . $userId;
    $sqlSelRecord = mysqli_query($link, $sqlClearTokensRequest);//выполняем запрос
    $result['error']=true; $result['code'] = 401; $result['message'] = 'Refresh token invalid!';goto endRequest;
  }

  $newAccessToken = generate_string($accTokenLenght);//Генерация accessToken
  $newRefreshToken = generate_string($refrTokenLenght);//Генерация refreshToken
  $nowTimeStamp = time();
  $accessTokenEndTime = $nowTimeStamp + $accTokenLife;//конечная дата действия токена
  $refreshTokenEndTime = $nowTimeStamp + $refrTokenLife;//конечная дата действия токена

  $sqlSaveTokens = "UPDATE `$userTableName` SET `$accTokenField` = '" . $newAccessToken . "', `$accTokenLifeField` = '" . $accessTokenEndTime . "', `$refreshTokenField` = '" . $newRefreshToken . "' ,`$refrTokenLifeField` = '" . $refreshTokenEndTime . "' WHERE `$userTableName`.`id` = " . $userId;
  $sqlResultSaveTokens = mysqli_query($link, $sqlSaveTokens);//сохраняем токены и даты
  if (empty($sqlResultSaveTokens)) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'Error saving new tokens to DB.';goto endRequest;
  }
  $result['tokens'] = ['accessToken' => $newAccessToken, 'refreshToken' => $newRefreshToken, 'userId' => $userId];

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = 'Method Not Allowed';
}

endRequest:
if ($link)mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);
