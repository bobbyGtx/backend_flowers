<?php
//Проверка токена на валидность
function checkEmail($link, $result, $email, $checkRegex = true) {
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

  if ($checkRegex && !preg_match($emailRegEx, $email)) {
    $result['validationError'] = true;
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

function getUserInfo($link, $result, $userId) {
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
    $result['code'] = 400;
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

function login($link, $result, $login, $pass) {
  global $errors, $dataErr;
  include 'variables.php';
  $funcName = 'login_func';

  if (empty($result) || $result['error']) {
    goto endFunc;
  }
  if (!$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";
    goto endFunc;
  }
  if (empty($pass) || empty($login)) {
    $result['error'] = true;
    $result['code'] = 400;
    $result['message'] = $dataErr['notRecognized'] . "($funcName)";
    goto endFunc;
  }

  //проверка на соответствие минимальным требованиям почты и пароля перед запросом в БД. Если нет - возвращаем ошибку!
  if (!preg_match($emailRegEx, $login)) {
    $result['error'] = true;
    $result['code'] = 401;
    $result['message'] = $authError['emailNotValid'];
    goto endFunc;
  }
  if (!preg_match($passwordRegEx, $pass)) {
    $result['error'] = true;
    $result['code'] = 401;
    $result['message'] = $authError['wrongPassword'];
    goto endFunc;
  } //Всё равно отдаем ошибку о ошибочном пароле

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

function updateUserData($link, $result, $userId, $newData) {
  include 'variables.php';
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

  if (count($newData) < 1) {
    $result['error'] = true;
    $result['message'] = $infoErrors['nothingToChange'];
    goto endFunc;
  }//Выходим если нет данных для добавления

  $newData['updatedAt'] = time();//Добавление временной метки

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
        if (is_null($v))$v='NULL';
        $dinSqlStr = $dinSqlStr . "`$k` = '$v',";
      }
    } else {
      if (is_array($v)) {
        $jsonEnc = json_encode($v, JSON_UNESCAPED_UNICODE);
        $dinSqlStr = $dinSqlStr . "`$k` = '$jsonEnc'";
      } else {
        if (is_null($v))$v='NULL';
        $dinSqlStr = $dinSqlStr . "`$k` = '$v'";
      }
    }
  }
  $sqlUpdateStr .= $dinSqlStr . " WHERE users.id = '" . $userId . "';";
  //============= Запрос в БД =============
  $result['sql'] = $sqlUpdateStr;
  $result['crudeData'] = $newData;
  goto endFunc;
  try {
    $sqlResult = mysqli_query($link, $sqlUpdateStr);
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['updReqRejected'] . "($funcName) ($emessage)";
    goto endFunc;
  }

  endFunc:
  return $result;
}//Сохранение изменений в учетную запись пользователя

/*
 * Функция проверяет переданные в запрос данные и обрабатывает их.
 * Если ключ найден, а значение нулевое null, 0, "0",'', false - то данные удаляются из базы.
 * Поле Email обязательное и остается не тронутым, если оно не изменено.
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
      if (preg_match($emailRegEx, $postDataJson['email'])) {
        $oldMessage = $result['message'];
        $result = checkEmail($link, $result, $postDataJson['email'], false);
        if ($result['error']){
          if ($result['message'] === $errors['emailIsBusy']){
            $result['error']=false; $result['code'] = 200;
            $messages[] = $result['message']; $result['message']=$oldMessage;
          }else goto endFunc;
        }
        if ($result['validationError']) {
          $result['error'] = true;
          $messages[] = 'E-Mail is incorrect';
          unset($result['validationError']);
        } else $newData['email'] = $postDataJson['email'];
      } else $messages[] = 'E-Mail is incorrect';
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
    $passwordError=false;
    if (!isset($postDataJson['password']) || empty($postDataJson['password'])){
      $messages[] = 'Current password not found!';$passwordError = true;
    }
    if (!empty($postDataJson['password']) && $postDataJson['password'] !== __decode($userPwd, $key)){
      $messages[] = 'Current password wrong.';$passwordError = true;
    }
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
        } else $messages[] = 'ZIP code not valid!';
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
        } else $messages[] = 'House number not valid!';

      }
      $newData['deliveryInfo'] = $deliveryInfo;
    } else $newData['deliveryInfo'] = null;
  }//проверка инфо о доставке.
  if (array_key_exists('deliveryType_id', $postDataJson)) {
    $patchDeliveryId = !empty($postDataJson['deliveryType_id']) ? intval($postDataJson['deliveryType_id']) : null;
    $newData['deliveryType_id'] = $patchDeliveryId;
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
      $newData['deliveryType_id'] = $patchDeliveryId;
    }
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
      $newData['paymentType_id'] = $patchPaymentId;
    }
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
