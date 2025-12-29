<?php
//Проверка токена на валидность
function checkEmail(mysqli $link, $result, $email, $checkRegex = true) {
  include 'variables.php';
  $funcName = 'checkEmail_func';
  //Нужен доп параметр для того, чтоб контролировать ошибку. Если ошибка по функции, мы можем не завершать основной скрипт
  if (empty($link)) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbConnect'] . "($funcName)";
    goto endFunc;
  }
  if (empty($email)) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['emailNotRecognized'];
    goto endFunc;
  }

  $email = strtolower($email);
  if ($checkRegex && !preg_match($emailRegEx, $email)) {
    $result['error'] = true;$result['code'] = 406;$result['message'] = $errors['emailNotValid'];
    goto endFunc;
  }

  $sql = "SELECT `id` FROM users WHERE email = '" . $email . "'";
  $sqlResult = mysqli_query($link, $sql);
  $numRows = mysqli_num_rows($sqlResult);
  if ($numRows <> 0) {
    $result['error'] = true;
    $result['code'] = 400;
    $result['message'] = $errors['emailIsBusy'];//Используется для сравнения в другой функции
    goto endFunc;
  }
  endFunc:
  return $result;
}
function getUserInfoFromEmail(mysqli $link, $result, $email, $checkRegex = true) {
  global $emailRegEx, $errors, $authError;
  include_once 'variables.php';
  $funcName = 'getUserInfoFromEmail_func';

  if (empty($link)) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['dbConnect'] . "($funcName)";goto endFunc;}
  if ($result['error']) goto endFunc;
  if (empty($email)) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['emailNotRecognized'];goto endFunc;}

  $email = strtolower($email);
  if ($checkRegex && !preg_match($emailRegEx, $email)) {$result['error'] = true;$result['code'] = 406;$result['message'] = $errors['emailNotValid'];goto endFunc;}
  $userIdField = 'id';//Идентификатор пользователя
  $userBlockedField = 'blocked';//Отметка о блокировке пользователя
  $emailField = 'email';//поле email для выборки
  $emailVerificationField = 'emailVerification';

  $sql = "SELECT $userIdField, $emailField, $userBlockedField,$emailVerificationField FROM users WHERE $emailField = ?";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $response = $stmt->get_result();
    $numRows = $response->num_rows;//только для select
    $row = $response->fetch_assoc();
    $stmt->close();
  } catch (Exception $e) {$eMessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($eMessage))";goto endFunc;}

  if ($numRows !== 1) {$result['error'] = true;$result['code'] = 400;$result['message'] = $authError['emailNotFound'];goto endFunc;}

  $result['user'] = $row;

  endFunc:
  return $result;
}
function addUser(mysqli $link, $result, $email, $password) {
  global $errors, $userTableName;
  include_once 'crypt.php';
  $funcName = 'addUser_func';
  if (empty($result) || $result['error'])goto endFunc;
  if (!$link) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";goto endFunc;}
  if (empty($email)) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['emailNotRecognized'] . "($funcName)";goto endFunc;}

  $settings = getSettings($link);//Получение ключа шифрования.
  if (!$settings) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbrequestSettings']; goto endFunc;}
  $key = $settings['secretKey'];//ключ шифрования паролей

  $sql="INSERT INTO `$userTableName`(`email`, `password`, `updatedAt`) VALUES (?,?,?)";

  $email = strtolower($email);
  $passwordEnc = __encode($password, $key);//шифрование пароля
  $timeStamp=time();
  try{
    mysqli_report(MYSQLI_REPORT_ALL);
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'ssi',$email,$passwordEnc,$timeStamp);
    mysqli_stmt_execute($stmt);
    $newUserId = mysqli_insert_id($link);
    mysqli_stmt_close($stmt);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['insertReqRejected'] . "($emessage))";goto endFunc;
  }

  if (empty($newUserId) && $newUserId<1){
    $result['error']=true; $result['code']=500; $result['message']="Problem with UserID. Creating Cart record in DB impossible.";goto endFunc;
  }
  $result['newUserId'] = $newUserId;

  endFunc:
  return $result;
}

function getUserInfo(mysqli $link, $result, $userId) {
  include 'variables.php';
  $funcName = 'getUserInfo' . '_func';

  if (empty($result) || $result['error']) {
    goto endFunc;
  }
  if (!$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";
    goto endFunc;
  }
  if (!$userId) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['userIdNotFound'] . "($funcName)";
    goto endFunc;
  }

  $sql = "SELECT 
   users.id,
   users.firstName,
   users.lastName,
   users.email,
   users.phone,
   users.deliveryType_id,
   users.paymentType_id,
   users.deliveryInfo,
   users.emailVerification
   FROM users 
   WHERE users.id = ?";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {
      throw new Exception($link->error);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $response = $stmt->get_result();
    $numRows = $response->num_rows;
    $stmt->close();
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";
    goto endFunc;
  }

  if ($numRows <> 1) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $dbError['unexpResponse'] . "($funcName)";
    goto endFunc;
  }

  $result['user'] = $response->fetch_assoc();//Парсинг
  if ($result['user']['deliveryInfo']) {
    $result['user']['deliveryInfo'] = json_decode($result['user']['deliveryInfo']);//Парсинг
  }

  endFunc:
  return $result;
}//Получение информации о пользователе. Возвращает данные в $result['user']

/**
 * @param mysqli $link - DB connection link
 * @param $result - result array
 * @param $login - Valid email
 * @param $pass - Valid password
 * @return array - $result
 */
function login(mysqli $link, $result, $login, $pass):array {
  global $errors, $dataErr;
  include 'variables.php';
  $funcName = 'login_func';

  if (empty($result) || $result['error']) goto endFunc;

  if (!$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";
    goto endFunc;
  }
  if (empty($pass) || empty($login)) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $dataErr['notRecognized'] . "($funcName)";
    goto endFunc;
  }

  $sql = "SELECT id,email,`password`,emailVerification,blocked FROM users WHERE email = '$login';";
  try {
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['selReqRejected'] . "($funcName) ($emessage))";
    goto endFunc;
  }

  $numRows = mysqli_num_rows($sqlResult);
  if ($numRows <> 1) {
    $result['error'] = true;
    $result['code'] = 401;
    $result['message'] = $authError['emailNotFound'];
    goto endFunc;
  }
  $row = mysqli_fetch_assoc($sqlResult);//парсинг
  if (empty($row['id']) || empty($row['email']) || empty($row['password'])) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['recognizeUnableDB'];
    goto endFunc;
  }

  $settings = getSettings($link);//Получение ключа шифрования.
  if ($settings == false) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbrequestSettings'];
    goto endFunc;
  }
  $key = $settings['secretKey'];//ключ шифрования паролей
  $passwordDec = __decode($row['password'], $key);//дешифрование пароля из БД
  if ($passwordDec !== $pass) {
    $result['error'] = true;
    $result['code'] = 401;
    $result['message'] = $authError['wrongPassword'];
    goto endFunc;
  }

  if (boolval($row['blocked']) === true) {
    $result['error'] = true;
    $result['code'] = 403;
    $result['message'] = $infoMessages['userBlocked'];
    goto endFunc;
  }

  $result['user'] = ['userId' => $row['id']];

  endFunc:
  return $result;
}//Запрос из таблицы пользователей Возвращает данные в $result['user'] = userId, firstName, lastName

function updateUserData(mysqli $link, $result, $userId, $newData) {
  include 'variables.php';
  include_once "confirmOp.php";
  include_once "enums.php";
  $funcName = 'updateUserData_func';

  if (empty($result) || $result['error']) {
    goto endFunc;
  }
  if (!$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";
    goto endFunc;
  }
  if (!$userId) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['userIdNotFound'] . "($funcName)";
    goto endFunc;
  }
  if (empty($newData) || !is_array($newData)) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $dataErr['dataInFunc'] . "($funcName)";
    goto endFunc;
  }

  if (count($newData) === 0) {
    $result['error'] = true;
    $result['message'] = $infoErrors['nothingToChange'];
    goto endFunc;
  }//Выходим если нет данных для добавления

  $newData['updatedAt'] = time();//Добавление временной метки

  $fieldValues = [];//Значения полей
  $fieldTypes='';//Типы данных полей (i = integer)(s = string)
  $setParts = [];//группы параметров [поле = ?]
  foreach ($newData as $key => $value){
    $setParts[]="$key = ?";
    if (is_array($value)) {
      $fieldValues[]=json_encode($value, JSON_UNESCAPED_UNICODE);
      $fieldTypes.='s';
    }else{
      $fieldValues[]=is_null($value)?null:$value;
      $fieldTypes.=is_int($value)?'i':'s';
    }
  }
  $setSql = implode(',',$setParts);//[firstName = ?, lastName = ?, address = ?]
  $sql = "UPDATE users SET $setSql WHERE id = ?";
  $fieldValues[]=$userId;$fieldTypes.='i';//добавляем userId

  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->bind_param($fieldTypes, ...$fieldValues);
    $stmt->execute();
    $affectedRows = mysqli_stmt_affected_rows($stmt);//Кол-во затронутых полей
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}
  if ($affectedRows===0) {$result['error'] = true;$result['code'] = 500;$result['message']='Ни одна запись не была изменена';goto endFunc;}

  /*Удалить после тестов
  $sqlUpdateStr = "UPDATE `$userTableName` SET ";
  $j = 0;
  $dinSqlStr = '';
  foreach ($newData as $k => $v) {
    $j++;
    if ($j < count($newData)) {
      if (is_array($v)) {
        $jsonEnc = json_encode($v, JSON_UNESCAPED_UNICODE);
        $dinSqlStr = $dinSqlStr . "`$k` = '$jsonEnc',";
      } else {
        $dinSqlStr = is_null($v)?$dinSqlStr . "`$k` = NULL,":$dinSqlStr . "$k = '$v',";
      }
    } else {
      if (is_array($v)) {
        $jsonEnc = json_encode($v, JSON_UNESCAPED_UNICODE);
        $dinSqlStr = $dinSqlStr . "`$k` = '$jsonEnc'";
      } else {
        $dinSqlStr = is_null($v)?$dinSqlStr . "$k = NULL":$dinSqlStr . "$k = '$v'";
      }
    }
  }
  $sqlUpdateStr .= $dinSqlStr . " WHERE users.id = $userId;";
  //============= Запрос в БД =============
  try {
    $sqlResult = mysqli_query($link, $sqlUpdateStr);
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['updReqRejected'] . "($funcName) ($emessage)";
    goto endFunc;
  }
*/
  endFunc:
  return $result;
}//Сохранение изменений в учетную запись пользователя, с подготовкой смены email. (добавить)
function emailVerification(mysqli $link, $result, $userId) {
  global $errors;
  include_once 'variables.php';
  $funcName = 'changeEmailVerification_func';

  if (empty($result) || $result['error']) goto endFunc;
  if (!$link) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";goto endFunc;}
  if (!$userId) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['userIdNotFound'] . "($funcName)";goto endFunc;}

  $sql = "UPDATE users
   SET emailVerification = 1
   WHERE id = ?";

  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $numRows = $stmt->affected_rows;
    $stmt->close();
  } catch (Exception $e) {$eMessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($eMessage))";goto endFunc;}

  if ($numRows <> 1){
    $errorDump=$errors['updReqNothing']."($funcName). Table[users], User ID = $record_id";
    file_put_contents(__DIR__ . '../logs/debug.log', print_r($errorDump, true), FILE_APPEND);
  }//Если затронуто 0 строк - ошибка в лог файл

  endFunc:
  return $result;
}//Установка 1 в поле emailVerification
function changeEmail($link, $result, $userId, $newEmail) {
  global $errors;
  include_once 'variables.php';
  $funcName = 'changeEmail_func';

  if (empty($result) || $result['error']) goto endFunc;
  if (!$link) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";goto endFunc;}
  if (!$userId) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['userIdNotFound'] . "($funcName)";goto endFunc;}

  $result = checkEmail($link, $result, $newEmail);
  if ($result['error']) {
    if ($result['code']===406 || $result['message'] === $errors['emailIsBusy']){
      $result['code']=406;//корректируем номер ошибки на разрешенный в clearConfirmationField()
    }else goto endFunc;
  }
  $updatedAt = time();//Добавление временной метки
  $emailValidation = 1;

  $sql = "UPDATE users
   SET email =  ?, updatedAt = ?, emailVerification = ?
   WHERE id = ?";

  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->bind_param('siii', $newEmail,$updatedAt,$emailValidation,$userId);
    $stmt->execute();
    $numRows = $stmt->affected_rows;
    $stmt->close();
  } catch (Exception $e) {$eMessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($eMessage))";goto endFunc;}

  if ($numRows <> 1){
    $errorDump=$errors['updReqNothing']."($funcName). Table[users], User ID = $userId";
    file_put_contents(__DIR__ . '../logs/debug.log', print_r($errorDump, true), FILE_APPEND);
  }//Если затронуто 0 строк - ошибка в лог файл

  endFunc:
  return $result;
}//Изменение email пользователя и установка 1 в поле emailVerification
/**
 * @param mysqli $link - DB connection link
 * @param $result
 * @param int $userId - user ID
 * @param string $newPassword - encrypted new password
 * @return array $result
 */
function setNewPassword(mysqli $link, $result, int $userId, string $newPassword):array {
  global $errors;
  include_once 'variables.php';
  $funcName = 'setNewPassword_func';

  if (empty($result) || $result['error']) goto endFunc;
  if (!$link) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";goto endFunc;}
  if (!$userId) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['userIdNotFound'] . "($funcName)";goto endFunc;}
  if (empty($newPassword)) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['dataNotFound'] . "($funcName)";goto endFunc;}

  $updatedAt = time();//Добавление временной метки

  $sql = "UPDATE users
   SET password =  ?, updatedAt = ?, accessToken = NULL, accTokenEndTime = NULL, refreshToken = NULL, refrTokenEndTime = NULL
   WHERE id = ?";

  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->bind_param('sii', $newPassword,$updatedAt,$userId);
    $stmt->execute();
    $numRows = $stmt->affected_rows;
    $stmt->close();
  } catch (Exception $e) {$eMessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($eMessage))";goto endFunc;}

  if ($numRows <> 1){
    $errorDump=$errors['updReqNothing']."($funcName). Table[users], User ID = $userId";
    file_put_contents(__DIR__ . '../logs/debug.log', print_r($errorDump, true), FILE_APPEND);
  }//Если затронуто 0 строк - ошибка в лог файл.

  endFunc:
  return $result;
}//Изменение пароля пользователя

/*
 * Функция проверяет переданные в запрос данные и обрабатывает их.
 * Если ключ найден, а значение нулевое null, 0, "0",'', false - то данные удаляются из базы.
 * Поле Email обязательное и остается не тронутым, если оно не изменено. При его изменении сбрасывается верификация EMail
 * Поле пароль меняется при наличии ключа newPassword.
 * Поле меняется полностью, поэтому с фронта передавать необходимо все данные не зависимо от изменения.
 * Возвращается массив ошибок валидации с кодом 406
 * "firstName": "Volodymyr",
   "lastName": "Volobuiev",
   "email": "bobbygtx2@gmail.com",
   "phone": "+491771750803",
   "deliveryType_id": 2,
   "paymentType_id": 3,
   "deliveryInfo": {
       "region": "Baden-Württemberg",
       "zip": "70373",
       "city": "Stuttgart",
       "street": "Munichstraße",
       "house": "20вы"
   }
 *
 */
function prepareNewData($result, $link, $postDataJson, $userEml, $userPwd, $key) {
  include 'variables.php';
  include 'deliveryOp.php';
  $funcName = 'prepareNewData_func';

  if (empty($result) || $result['error']) goto endFunc;
  if (!$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";
    goto endFunc;
  }
  if (empty($postDataJson) || !is_array($postDataJson)) {
    $result['error'] = true;
    $result['message'] = $errors['dataNotFound'] . "($funcName)";
    goto endFunc;
  }
  if (count($postDataJson) === 0) {
    $result['error'] = true;
    $result['code'] = 400;
    $result['message'] = $infoErrors['nothingToChange'];
    goto endFunc;
  }


  $newData = [];//массив для проверенных данных
  $messages = [];//Массив для ошибок
  //проверка имени
  if (array_key_exists('firstName', $postDataJson)) {
    if (!empty($postDataJson['firstName'])) {
      if (preg_match($firstNameRegEx, $postDataJson['firstName'])) {
        $newData['firstName'] = $postDataJson['firstName'];
      } else $messages[] = 'Invalid First Name!';
    } else $newData['firstName'] = null;
  }
  if (array_key_exists('lastName', $postDataJson)) {
    if (!empty($postDataJson['lastName'])) {
      if (preg_match($lastNameRegEx, $postDataJson['lastName'])) {
        $newData['lastName'] = $postDataJson['lastName'];
      } else $messages[] = 'Invalid Last Name!';

    } else$newData['lastName'] = null;
  }
  if (array_key_exists('email', $postDataJson) && isset($postDataJson['email'])) {
    if ($userEml !== $postDataJson['email']) {
      $passwordError=false;
      if (!isset($postDataJson['oldPassword']) || empty($postDataJson['oldPassword'])){
      $messages[] = 'Current password not found!';$passwordError = true;
      }
      if (isset($postDataJson['oldPassword']) && $postDataJson['oldPassword']===isset($postDataJson['newPassword'])){
        $messages[] = 'Old and new passwords are the same!';$passwordError = true;
      }
      if (!empty($postDataJson['oldPassword']) && $postDataJson['oldPassword'] !== __decode($userPwd, $key)){
        $messages[] = 'Current password wrong.';$passwordError = true;
      }
      if (!$passwordError) {
        if (preg_match($emailRegEx, $postDataJson['email'])){
          $oldMessage = $result['message'];
          $result = checkEmail($link, $result, $postDataJson['email'], false);
          if ($result['error']){
            if ($result['message'] === $errors['emailIsBusy']){
              $result['error']=false; $result['code'] = 200;
              $messages[] = $result['message']; $result['message']=$oldMessage;
            }else goto endFunc;
          }
          $newData['emailVerification']=0;
          $newData['email'] = $postDataJson['email'];
        }else $messages[] = 'E-Mail is incorrect';
      } 
    }
  }//Если email не изменился - игнорируем его
  if (array_key_exists('phone', $postDataJson)) {
    if (!empty($postDataJson['phone'])) {
      if (preg_match($telephoneRegEx, $postDataJson['phone'])) {
        $newData['phone'] = $postDataJson['phone'];
      } else $messages[] = 'Invalid phone!';
    } else $newData['phone'] = null;
  }//проверка телефона
  //Если придет пустая строка, она будет проигнорирована так, как пароль обязателен
  if (array_key_exists('newPassword', $postDataJson) && !empty($postDataJson['newPassword'])) {
    $passwordError=isset($passwordError)?$passwordError:false;
    if (!$passwordError){
      
      if (!isset($postDataJson['oldPassword']) || empty($postDataJson['oldPassword'])){
        $messages[] = 'Current password not found!';$passwordError = true;
      }
      if (isset($postDataJson['oldPassword']) && $postDataJson['oldPassword']===isset($postDataJson['newPassword'])){
        $messages[] = 'The old and new passwords are the same!';$passwordError = true;
      }
      if (!empty($postDataJson['oldPassword']) && $postDataJson['oldPassword'] !== __decode($userPwd, $key)){
        $messages[] = 'Current password wrong.';$passwordError = true;
      }
    }//Если проверка пароля ещё не была произведена
    if (!empty($postDataJson['newPassword']) && !preg_match($passwordRegEx, $postDataJson['newPassword'])){
      $messages[] = 'New password not acceptable!';$passwordError = true;
    }
    if ($postDataJson['newPassword'] !== $postDataJson['newPasswordRepeat'] ){
      $messages[] = 'New passwords do not match!';$passwordError = true;
    }
    if (!$passwordError)$newData['password'] = __encode($postDataJson['newPassword'], $key);//шифрование пароля
  }//проверка пароля
  //Данные должны приходить все, всё что придет перезапишется
  if (array_key_exists('deliveryInfo', $postDataJson)) {
    if (!empty($postDataJson['deliveryInfo'])) {
      $patchDeliveryInfo = $postDataJson['deliveryInfo'];
      $deliveryInfo = [];
      if (array_key_exists('region', $patchDeliveryInfo) && !empty($patchDeliveryInfo['region'])) {
        if (in_array($patchDeliveryInfo['region'], $regionsD)) {
          $deliveryInfo['region'] = $patchDeliveryInfo['region'];
        } else $messages[] = 'Invalid region!';
      }
      if (array_key_exists('zip', $patchDeliveryInfo) && !empty($patchDeliveryInfo['zip'])) {
        if (preg_match($zipCodeRegEx, $patchDeliveryInfo['zip'])) {
          $deliveryInfo['zip'] = $patchDeliveryInfo['zip'];
        } else $messages[] = 'Invalid ZIP code!';
      }
      if (array_key_exists('city', $patchDeliveryInfo) && !empty($patchDeliveryInfo['city'])) {
        $deliveryInfo['city'] = $patchDeliveryInfo['city'];
      }
      if (array_key_exists('street', $patchDeliveryInfo) && !empty($patchDeliveryInfo['street'])) {
        $deliveryInfo['street'] = $patchDeliveryInfo['street'];
      }
      if (array_key_exists('house', $patchDeliveryInfo) && !empty($patchDeliveryInfo['house'])) {
        if (preg_match($houseNumberRegEx, $patchDeliveryInfo['house'])) {
          $deliveryInfo['house'] = $patchDeliveryInfo['house'];
        } else $messages[] = 'Invalid house!';

      }
      $newData['deliveryInfo'] = $deliveryInfo;
    } else $newData['deliveryInfo'] = null;
  }//проверка инфо о доставке.
  if (array_key_exists('deliveryType_id', $postDataJson)) {
    $patchDeliveryId = !empty($postDataJson['deliveryType_id']) ? intval($postDataJson['deliveryType_id']) : null;
    if (!empty($patchDeliveryId)) {
      $oldMessage = $result['message'];
      $result = getDeliveryInfo($link, $result, $patchDeliveryId, null, false, false);
      if ($result['error']){
        if ($result['code'] === 400 && $result['message']===$errors['deliveryIdNotFound']) {
          //перехват определенной ошибки из функции
          $messages[] = $result['message'];
          $result['error']=false;$result['code']=200;$result['message']=$oldMessage;
        }else goto endFunc;
      }
    }
    $newData['deliveryType_id'] = $patchDeliveryId;
  }//проверка типа доставки
  if (array_key_exists('paymentType_id', $postDataJson)) {
    $patchPaymentId = !empty($postDataJson['paymentType_id']) ? intval($postDataJson['paymentType_id']) : null;
    if (!empty($patchPaymentId)) {
      $oldMessage = $result['message'];
      $result = checkPayment($link, $result, $patchPaymentId, null, false, false);
      if ($result['error']){
        if ($result['code'] === 400 && $result['message']===$errors['paymentIdNotFound']){
          $messages[] = $result['message'];
          $result['error']=false;$result['code']=200;$result['message']=$oldMessage;
        }else goto endFunc;
      }
    }
    $newData['paymentType_id'] = $patchPaymentId;
  }//проверка типа оплаты

  if (count($messages) > 0) {
    $result['error'] = true;
    $result['code'] = 406;
    $result['message'] = $errors['dataNotAcceptable'];
    $result['messages'] = $messages;
    goto endFunc;//error 406: unacceptable format
  }//Если есть ошибки данных - выводим их

  if (count($newData) === 0) {
    $result['error'] = true;
    $result['code'] = 400;
    $result['message'] = $infoErrors['nothingToChange'];
    goto endFunc;
  }

  $result['newData'] = $newData;
  endFunc:
  return $result;
}

/*
 * Функция создает запись в базе данных для определенных операций пользователя:
 * - changeEmail - изменение email
 * - verifyEmail - верификация e-mail адреса нового пользователя
 * - resetPass - сброс пароля
 * Функция принимает
 * $userId - идентификатор пользователя
 * $tokenFieldName - название поля, в которое записывать токен.['changeEmailToken','verifyEmailToken','resetPassToken']
 * если $tokenFieldName = 'changeEmailToken', то обязателна передача $newEmail
 * $newEmail должен быть передан в функцию, а его валидность проверяется уже тут
 *
 * Выходные параметры
 * userOpData
 */
function createUserOpRecord($result, $link, int $userId,UserOpTypes $operationType, $newEmail=null){
  global $errors, $authError, $opErrors, $emailRegEx, $operationTokenLength, $opTokenRegEx;
  include_once 'variables.php';
  include_once 'generators.php';
  include_once 'enums.php';
  $funcName = 'createUserOpRecord_func';
  settype($userId, 'integer');

  if (empty($result) || $result['error']) goto endFunc;
  if (!$link) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";goto endFunc;}
  if (!$userId) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['userIdNotFound'] . "($funcName)";goto endFunc;}

  if ($operationType === UserOpTypes::changeEmail){
    if (!$newEmail){$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['emailNotRecognized'] . "($funcName)";goto endFunc;}
    if (!preg_match($emailRegEx, $newEmail)) {$result['error'] = true;$result['code'] = 400;$result['message'] = $authError['emailNotValid'];goto endFunc;}
    $newEmail = strtolower($newEmail);
  }

  $token = generate_string($operationTokenLength);
  if (!preg_match($opTokenRegEx, $token)) {$result['error'] = true;$result['code'] = 500;$result['message'] = $opErrors['opTokenInvalid'] . "($funcName)"; goto endFunc;}
  $createdAt=time();

  $tokenField = $operationType->tokenField();
  $timeField = $operationType->timeField();

  $sql = "INSERT INTO user_operations (`user_id`,`newEmail`,`{$tokenField}`,`{$timeField}`)VALUES (?,?,?,?)
    ON DUPLICATE KEY UPDATE
    newEmail = VALUES(newEmail),
    `{$tokenField}` = VALUES(`{$tokenField}`),
    `{$timeField}` = VALUES(`{$timeField}`)";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->bind_param("issi", $userId, $newEmail,$token, $createdAt);
    $stmt->execute();
    $stmt->close();
  } catch (Exception $e) {$eMessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['insertReqRejected'] . "($funcName)($eMessage))";goto endFunc;}

  $result['data'] = ['userId'=>$userId,'token'=>$token, 'createdAt' => $createdAt];
  if ($newEmail && $operationType === UserOpTypes::changeEmail)$result['data']['newEmail'] = $newEmail;
  endFunc:
  return $result;
}
function sendOpEmail($result,string $userEmail,string $token,UserOpTypes $operation,string $languageTag){
  global $emailRegEx, $opTokenRegEx, $errors, $opErrors, $imagesUrl, $frontendAddress, $authError,$emailTemplatesDir, $productionMode, $passResetUrl, $language, $confirmationScriptURL, $passChangeUrl, $critErr;
  include_once 'enums.php';
  $funcName = "sendOpEmail_func operation:($operation->name)";
  if ($result['error']) goto endFunc;
  if (!$userEmail){$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['emailNotRecognized'] . "($funcName)";goto endFunc;}
  if (!preg_match($emailRegEx, $userEmail)) {$result['error'] = true;$result['code'] = 500;$result['message'] = $authError['emailNotValid'] . "($funcName)";goto endFunc;}
  if (!$token){$result['error'] = true;$result['code'] = 500;$result['message'] = $opErrors['opTokenNotFound'] . "($funcName)";goto endFunc;}
  if (!preg_match($opTokenRegEx,$token)){$result['error'] = true;$result['code'] = 500;$result['message'] = $opErrors['opTokenInvalid'] . "($funcName)";goto endFunc;}

  $urlParamName = $operation->urlParam();
  $logoUrl = $imagesUrl.'logo.png';
  $reqLanguage = $language[$languageTag];//временное

  if ($operation->tokenLifeTime()>0){
    $actuallyFor = time() + $operation->tokenLifeTime();
    $date = new DateTime("@{$actuallyFor}");
    $date->setTimezone(new DateTimeZone('Europe/Berlin'));
    $endOfLifeDate = $date->format("d.m.Y H:i");
  }else $endOfLifeDate = null;

  if ($operation===UserOpTypes::verifyEmail){
    $mailSubject = match ($languageTag) {
      'en' => '[AmoraFlowers] Email address confirmation',
      'de' => '[AmoraFlowers] E-Mail-Adressbestätigung',
      default => '[AmoraFlowers] Подтверждение email адреса',
    };
    $actionURL = "{$confirmationScriptURL}?{$urlParamName}={$token}&lng={$languageTag}";
  }elseif ($operation===UserOpTypes::changeEmail){
    $mailSubject = match ($languageTag) {
      'en' => '[AmoraFlowers] New email address confirmation',
      'de' => '[AmoraFlowers] Neue E-Mail-Adressbestätigung',
      default => '[AmoraFlowers] Подтверждение нового email адреса',
    };
    $actionURL = "{$confirmationScriptURL}?{$urlParamName}={$token}&lng={$languageTag}";
    $passChangeLink = $passChangeUrl;//Перенос переменной из настроек для доступности
  }elseif ($operation===UserOpTypes::resetPass){
    $mailSubject = match ($languageTag) {
      'en' => '[AmoraFlowers] Reset user password',
      'de' => '[AmoraFlowers] Benutzerpasswort zurücksetzen',
      default => '[AmoraFlowers] Сброс пароля пользователя',
    };
    $actionURL = "{$passResetUrl}?{$urlParamName}={$token}&lng={$languageTag}";
  }else{
    $result['error'] = true;$result['code'] = 500;$result['message'] = $critErr['UserOpNotFound']." ({$funcName})";goto endFunc;
  }
  $templateFile = "{$emailTemplatesDir}".$operation->emailTemplate().".php";
  //Получение шаблона из файла

  if (!file_exists($templateFile)){$result['error'] = true;$result['code'] = 500;$result['message'] = $opErrors['EmailTemplateNotFound'] . "($funcName)";goto endFunc;}
  ob_start();
  include $templateFile;
  $emailHtml = ob_get_clean();
  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
  $headers .= "From: AmoraFlowers <noreply@amoraflowers.atwebpages.com>\r\n";
  if (!$productionMode)$result['mailLink'] = $actionURL;
  else{
    $success = mail($userEmail, $mailSubject, $emailHtml, $headers);
    //$result['mail'] = [$userEmail,$mailSubject];
    if (!$success) {
      $result['error'] = true;$result['code'] = 500;$result['message'] = "E-Mail was not sent. ({$funcName})";goto endFunc;
    }
  }

  endFunc:
  return $result;
}