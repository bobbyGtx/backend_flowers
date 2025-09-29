<?php
//Проверка токена на валидность. userData - флаг для возврата идентификатора пользователя и пароля
function checkToken($link, $result, $http_Headers,$userData = false){
   include 'variables.php';
   $funcName = 'checkToken_func';
   $http_Headers = array_change_key_case($http_Headers, CASE_LOWER);
   $http_AccessToken = $http_Headers[$accessTokenHeader];
   if (empty($http_AccessToken) || !preg_match($accessTokenRegEx, $http_AccessToken)) {
      $result['error'] = true;$result['code'] = 402;$result['message'] = 'Token invalid! (Unable to recognize Token).'; return $result;
   }

   $sql = "SELECT `id`, `password`,`$accTokenField`,`$accTokenLifeField` FROM `$userTableName` WHERE `$accTokenField` = '" . $http_AccessToken . "'";
   $sqlSelRecord = mysqli_query($link, $sql);//выполняем запрос
   if (empty($sqlSelRecord)) {
      $result['error'] = true;$result['code'] = 500;$result['message'] = $errors['reqRejected'] . "($funcName)";return $result;
   }//Проверяем запрос в БД на успешность
   if (mysqli_num_rows($sqlSelRecord) < 1) {
      $result['error'] = true;$result['code'] = 401;$result['message'] = 'Token invalid!';
      return $result;
   }//Если записей нет - то такой токен не найден в базе. Разделение нужно для след. проверки
   $record = mysqli_fetch_array($sqlSelRecord);//парсинг
   $userId = $record['id'];//id пользователя
   $userPwd = $record['password'];//password пользователя
   $accessToken = $record[$accTokenField];//токен из базы
   $accTokenTime = $record[$accTokenLifeField];//дата по которую токен действует
   if (empty($userId) || empty($accessToken)) {
      $result['error'] = true;$result['code'] = 500;$result['message'] = $errors['recognizeUnableDB'] . "($funcName)";return $result;
   }
   if (($accTokenTime - time()) < 0) {
      //Еесли токен просрочен выходим
      $result['error'] = true;$result['code'] = 401;$result['message'] = 'Token invalid!'; return $result;
   }
   if ($userData){
      $result['userId'] = $userId; $result['userPassword'] = $userPwd;   
   }
   return $result;
}

