<?php
//Проверка токена на валидность. userData - флаг для возврата идентификатора пользователя и пароля
function checkToken($link, $result, $http_Headers,$userData = false){
   include 'variables.php';
   $funcName = 'checkToken'.'_func';
   $http_Headers = array_change_key_case($http_Headers, CASE_LOWER);
   $http_AccessToken = isset($http_Headers[$accessTokenHeader])?$http_Headers[$accessTokenHeader]:null;
   if (empty($http_AccessToken) || !preg_match($accessTokenRegEx, $http_AccessToken)) {
      $result['error'] = true;$result['code'] = 401;$result['message'] = $authError['accTokenNotFound']; return $result;
      //$result['error'] = true;$result['code'] = 402;$result['message'] = 'Token invalid! (Unable to recognize Token).'; return $result;
   }
   
   $sql = "SELECT `id`, `password`, `email`,`$accTokenField`,`$accTokenLifeField` FROM `$userTableName` WHERE `$accTokenField` = '" . $http_AccessToken . "'";
   $sqlSelRecord = mysqli_query($link, $sql);//выполняем запрос
   if (empty($sqlSelRecord)) {
      $result['error'] = true;$result['code'] = 500;$result['message'] = $errors['reqRejected'] . "($funcName)";return $result;
   }//Проверяем запрос в БД на успешность
   if (mysqli_num_rows($sqlSelRecord) < 1) {
      $result['error'] = true;$result['code'] = 401;$result['message'] = $authError['accTokenInvalid'];
      return $result;
   }//Если записей нет - то такой токен не найден в базе. Разделение нужно для след. проверки
   $record = mysqli_fetch_array($sqlSelRecord);//парсинг
   $userId = intval($record['id']);//id пользователя
   $userPwd = $record['password'];//password пользователя
  $userEmail = $record['email'];//email пользователя
   $accessToken = $record[$accTokenField];//токен из базы
   $accTokenTime = $record[$accTokenLifeField];//дата по которую токен действует
   if (empty($userId) || empty($accessToken)) {
      $result['error'] = true;$result['code'] = 500;$result['message'] = $errors['recognizeUnableDB'] . "($funcName)";return $result;
   }
   if (($accTokenTime - time()) < 0) {
      //Еесли токен просрочен выходим
      $result['error'] = true;$result['code'] = 401;$result['message'] = $authError['accTokenOutOfDate']; return $result;
   }
   if ($userData){
      $result['userId'] = $userId; $result['userPassword'] = $userPwd; $result['userEmail'] = $userEmail;
   }
   return $result;
}

function generateTokens($link, $result, $userId){
   include 'variables.php';
   $funcName = 'generateTokens'.'_func';
   if (empty($result) || $result['error']){goto endFunc;}
   if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
   if (!$userId) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}
   
   $accessToken = generate_string($accTokenLenght);//Генерация accessToken согласно длины из настроек
   if (!$accessToken || strlen($accessToken)<>$accTokenLenght){
      $result['error']=true; $result['code']=500; $result['message']="Critical error! Problem with accessToken Generation($funcName)";goto endFunc;
   }//проверка наличия токена
   $refreshToken = generate_string($refrTokenLenght);//Генерация refreshToken согласно длины из настроек
   if (!$refreshToken || strlen($refreshToken)<>$refrTokenLenght){
      $result['error']=true; $result['code']=500; $result['message']="Critical error! Problem with refreshToken Generation($funcName)";goto endFunc;
   }//проверка наличия токена
   $nowTimeStamp = time();
   $accessTokenEndTime = $nowTimeStamp + $accTokenLife;//конечная дата действия токена
   $refreshTokenEndTime = $nowTimeStamp + $refrTokenLife;//конечная дата действия токена

   $sql = "UPDATE $userTableName SET `$accTokenField` = '" . $accessToken . "', `$accTokenLifeField` = '" . $accessTokenEndTime . "', `$refreshTokenField` = '" . $refreshToken . "' ,`$refrTokenLifeField` = '" . $refreshTokenEndTime . "' WHERE `$userTableName`.`id` = " . $userId;
   try{
      $sqlResultSaveTokens = mysqli_query($link, $sql);//сохраняем токены и даты
   } catch (Exception $e){
      $emessage = $e->getMessage();
      $result['error']=true; $result['code']=500; $result['message']=$errors['updReqRejected'] . "($funcName)($emessage))";goto endFunc;
   }

   $result['user'] = ['userId' => $userId, 'accessToken' => $accessToken, 'refreshToken' => $refreshToken];

   endFunc:
   return $result;
}//Генерация и сохранение токенов в БД. Возвращает данные в $result['tokens']

function checkRefreshToken($link, $result, $refreshToken){
   include 'variables.php';
   $funcName = 'checkRefreshToken'.'_func';
   if (empty($result) || $result['error']){goto endFunc;}
   if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
   if (empty($refreshToken) || !preg_match($refreshTokenRegEx, $refreshToken)) {
    $result['error']=true; $result['code'] = 401; $result['message'] = $authError['refrTokenNotFound'];goto endFunc;
   }

   $sql = "SELECT `id`,`$refreshTokenField`,`$refrTokenLifeField`, `blocked` FROM `$userTableName` WHERE `$refreshTokenField` = '" . $refreshToken . "'";
   try{
      $sqlSelRecord = mysqli_query($link, $sql);//выполняем запрос
   } catch (Exception $e){
      $emessage = $e->getMessage();
      $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName) ($emessage))";goto endFunc;
   }
  
   $numRows = mysqli_num_rows($sqlSelRecord);
   if ($numRows <> 1) {
    $result['error']=true; $result['code'] = 401; $result['message'] = $authError['refrTokenInvalid'];goto endFunc;
   }//Совпадений в базе не найдено

   $record = mysqli_fetch_assoc($sqlSelRecord);//парсинг
   $userId = $record['id'];
   $refreshToken = $record[$refreshTokenField];
   $refrTokenTime = $record[$refrTokenLifeField];
   if (empty($userId) || empty($refreshToken)) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['recognizeUnableDB']."($funcName)";goto endFunc;
   }
   if ($record['blocked']){
      $result['error']=true; $result['code'] = 403; $result['message'] = $infoMessages['userBlocked'];goto endFunc;
   }
   if ((time() - $refrTokenTime) > 0) {
    $result = clearTokens($link, $result, $userId);
    if ($result['error'])goto endFunc;//если ошибка пришла из функции - прокидываем её
    $result['error']=true; $result['code'] = 401; $result['message'] = $authError['refrTokenOutOfDate'];goto endFunc;
   }//рефреш токен просрочен. Удаляем токены из базы

   if (!empty($userId) && intval($userId) > 0) {
      $result['userId'] = $userId;
   }else{
      $result['error']=true; $result['code'] = 501; $result['message'] = $errors['outputtingFuncError']."($funcName)";goto endFunc;
   }


   endFunc:
   return $result;
}//Проверка refresh token. Выводит $result['userId']

function clearTokens($link, $result, $userId){
   include 'variables.php';
   $funcName = 'clearTokens'.'_func';
   if (empty($result) || $result['error']){goto endFunc;}
   if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
   if (!$userId) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}

   try{
      $sqlClearTokensRequest = "UPDATE `$userTableName` SET `$accTokenField` = '', `$accTokenLifeField` = '', `$refreshTokenField` = '' ,`$refrTokenLifeField` = '' WHERE `$userTableName`.`id` = " . $userId;
      $sqlSelRecord = mysqli_query($link, $sqlClearTokensRequest);//выполняем запрос
   } catch (Exception $e){
      $emessage = $e->getMessage();
      $result['error']=true; $result['code']=500; $result['message']=$errors['updReqRejected'] . "($funcName) ($emessage))";goto endFunc;
   }
   
   endFunc:
   return $result;
}//удаление токенов у пользователя