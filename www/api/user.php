<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: PATCH, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
  http_response_code(200);//ответ на пробный запрос
  return;
} elseif ($method === 'PATCH') {
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/tokensOp.php';//Проверка токена
  include 'scripts/userOp.php';//Проверка email и т.д. 

  $result = ['error' => false, 'code' => 200, 'message' => 'Record changed'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;
  } else $settings = getSettings($link);//Получение ключа шифрования.

  if ($settings == false) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbrequestSettings'] ; goto endRequest;
  } else  $key = $settings['secretKey'];//ключ шифрования паролей

  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) {
    goto endRequest;//Если пришла ошибка - завршаем скрипт
  } else {
    if ($result['userId'] && $result['userPassword']){
      $userId = $result['userId'];
      $userPwd = $result['userPassword'];
      unset($result['userId']); unset($result['userPassword']);
    }else{
      $result['error']=true; $result['code'] = 500; $result['message'] = 'User data not found in record! Critical error.'; goto endRequest;
    }
  }
  
  /*Пример данных'{
        "firstName": "Gregor",
        "lastName": "Müller",
        "email": "email@gmail.com",
        "phone": "+491223112342",
        "password": "oldPass",
        "newPassword": "newPass22",
        "newPasswordRepeat" : "newPass22",
        "deliveryInfo": {
            "region": "Baden Württemberg",
            "zip": "70372",
            "city": "Stuttgart",
            "street": " Mercedesstraße",
            "house": "100",
            "entrance": "",
            "apartment": ""
        },
        "emailVerification": "0",
        "deliveryType_id": "1",
        "paymentType_id": "1"
    }'*/
  $patchData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($patchData, true);//парсинг параметров запроса
  settype($postDataJson['deliveryType_id'], 'integer');//защита от инъекции
  $patchDeliveryId = $postDataJson['deliveryType_id'];
  settype($postDataJson['paymentType_id'], 'integer');//защита от инъекции
  $patchPaymentId = $postDataJson['paymentType_id'];

  $newData = [];//массив для проверенных данных
  $messages = [];//Массив для ошибок
  if (!empty($postDataJson['firstName'])){
    if (preg_match($firstNameRegEx, $postDataJson['firstName'])){
      $newData['firstName']=$postDataJson['firstName'];
    }else{
      $result['error']=true; $messages[] = 'Invalid First Name!';
    }
  }//проверка имени
  if (!empty($postDataJson['lastName'])){
    if (preg_match($lastNameRegEx, $postDataJson['lastName'])){
      $newData['lastName']=$postDataJson['lastName'];
    } else {
      $result['error']=true; $messages[] = 'Invalid Last Name!';
    }
  }//проверка фамилии
  if (!empty($postDataJson['email'])) {
    $result = checkEmail($link,$result,$postDataJson['email']);
    if ($result['error']){goto endRequest;}
    if (!$result['funcError']){
      $newData['email']=$postDataJson['email'];
    } else {
      $result['error']=true; $messages[] = $result['message']; unset($result['funcError']);
    }
  }//проверка почты
  if (!empty($postDataJson['phone'])) {
    if (preg_match($telephoneRegEx, $postDataJson['phone'])){
      $newData['phone']=$postDataJson['phone'];
    } else {
      $result['error']=true; $messages[] = 'Invalid phone format!';
    }
  } //проверка телефона
  if (!empty($postDataJson['password'])){
    if ($postDataJson['password'] === __decode($userPwd, $key)){
      $oldPwdConfirm = true;
    }else{
      $result['error']=true; $oldPwdConfirm = false; $messages[] = 'Wrong password!';
    }
  }//проверка старого пароля
  if (!empty($postDataJson['newPassword'])) {
    if (preg_match($passwordRegEx, $postDataJson['newPassword'])){
      if ($postDataJson['newPassword'] === $postDataJson['newPasswordRepeat']){
        if ($oldPwdConfirm === true){
          $newData['password']=__encode($postDataJson['newPassword'], $key);//шифрование пароля
        }
      } else {
      $result['error']=true; $messages[] = 'New passwords do not match! ';
      } //Проверка идентичности паролей
    } else{
      $result['error']=true; $messages[] = 'Password not acceptable!';
    }//проверка на соответствие формата пароля
  }//проверка пароля
  if (!empty($postDataJson['deliveryInfo'])) {
    $patchDeliveryInfo = $postDataJson['deliveryInfo'];
    $deliveryInfo = [];
    if (!empty($patchDeliveryInfo['region'])){
      if (in_array($postDataJson['region'], $regionsD)){
        $deliveryInfo['region']=$patchDeliveryInfo['region'];
      }else{$result['error']=true; $messages[] ='Delivery Info -> Region invalid!';}
    } else {
      $result['error']=true; $messages[] ='Delivery Info -> Region are required!';  
    }
    if (!empty($patchDeliveryInfo['zip'])){
      $deliveryInfo['zip']=$patchDeliveryInfo['zip'];
    } else {
      $result['error']=true; $messages[] ='Delivery Info -> ZIP Code are required!';  
    }
    if (!empty($patchDeliveryInfo['city'])){
      $deliveryInfo['city']=$patchDeliveryInfo['city'];
    } else {
      $result['error']=true; $messages[] ='Delivery Info -> City are required!';  
    }
    if (!empty($patchDeliveryInfo['street'])){
      $deliveryInfo['street']=$patchDeliveryInfo['street'];
    } else {
      $result['error']=true; $messages[] ='Delivery Info -> Street are required!';  
    }
    if (!empty($patchDeliveryInfo['house'])){
       $deliveryInfo['house']=$patchDeliveryInfo['house'];
    }else {
      $result['error']=true; $messages[] ='Delivery Info -> House are required!'; 
    }
    $deliveryInfo['entrance']=$patchDeliveryInfo['entrance'];
    $deliveryInfo['apartment']=$patchDeliveryInfo['apartment'];
    $newData['deliveryInfo'] = $deliveryInfo;
  }//проверка инфо о доставке. При изменении отправлять все данные т.к это одно поле
  if (!empty($patchDeliveryId) && $patchDeliveryId>0){
    $sqlCheckInd = 'SELECT * FROM `delivery_types` WHERE `id`= '.$patchDeliveryId;
    $sqlDeliveryResult = mysqli_query($link, $sqlCheckInd);
     if ($sqlDeliveryResult <> true) {
        $sqlerror = mysqli_error($link);//Получение ошибки от БД
        $result['error']=true;$result['code']=500;$result['message']="Check new delivery ID request rejected by database. ($sqlerror)";goto endRequest;
      }
      $numRows = mysqli_num_rows($sqlDeliveryResult);
      if ($numRows !== 1) {
        $result['error']=true;$result['code']=406;$result['message']="Requested delivery type not found in DB. (Getting delivery ID: $patchDeliveryId) (Edit record)";goto endRequest;
      }else {
        $rowDelivery = mysqli_fetch_array($sqlDeliveryResult);//парсинг
        if ($rowDelivery['disabled']==0){
          $newData['deliveryType_id']=$postDataJson['deliveryType_id'];
        }else{
          $result['error']=true; $messages[] = 'Requested delivery type not active now!';
        }
      }
  }//проверка типа доставки 
  if (!empty($patchPaymentId) && $patchPaymentId>0){
    $sqlCheckInd = 'SELECT * FROM `payment_types` WHERE `id` = '.$patchPaymentId;
    $sqlPaymentResult = mysqli_query($link, $sqlCheckInd);
    if ($sqlPaymentResult <> true) {
        $sqlerror = mysqli_error($link);//Получение ошибки от БД
        $result['error']=true;$result['code']=500;$result['message']="Check new payment ID request rejected by database. ($sqlerror) (Edit record)";goto endRequest;
      }
    $numRows = mysqli_num_rows($sqlPaymentResult);
    if ($numRows !== 1) {
        $result['error']=true;$result['code']=406;$result['message']="Requested payment type not found in DB. (Getting payment ID: $patchPaymentId) (Edit record)";goto endRequest;
      }else {
        $rowPayment = mysqli_fetch_array($sqlPaymentResult);//парсинг
        if ($rowPayment['disabled']==0){
          $newData['paymentType_id']=$postDataJson['paymentType_id'];
        }else{
          $result['error']=true; $messages[] = 'Requested payment method not active now!';
        }
      }
  }//проверка типа оплаты

  if (count($messages)>0) {
    $result['code'] = 406;$result['message'] = 'Data not Acceptable!'; $result['messages'] = $messages; goto endRequest;//error 406: unacceptable format
  }//Если есть ошибки данных - выводим их

  if (count($newData)<1){
    $result['code'] = 400;$result['message'] = 'No data found to change. (Edit record)'; $result['messages'] = $messages; goto endRequest;//error 406: unacceptable format
  }//Выходим если нет данных для добавления

  $newData['updatedAt']=time();//Добавление временой метки

  $sqlUpdateStr = "UPDATE `$userTableName` SET ";
  $j = 0;
  $dinSqlStr = '';
  foreach ($newData as $k => $v) {
    $j++;
    if ($j < count($newData)) {
      if (is_array($v)){
        $jsonEnc = json_encode($v,JSON_UNESCAPED_UNICODE);
        $dinSqlStr = $dinSqlStr . "`$k` = '$jsonEnc',";
      } else {
         $dinSqlStr = $dinSqlStr . "`$k` = '$v',";
      }
    } else {
      if (is_array($v)){
        $jsonEnc = json_encode($v,JSON_UNESCAPED_UNICODE);
        $dinSqlStr = $dinSqlStr . "`$k` = '$jsonEnc'";
      } else {
         $dinSqlStr = $dinSqlStr . "`$k` = '$v'";
      }
    }
  }
  $sqlUpdateStr.= $dinSqlStr." WHERE users.id = '".$userId."';" ;
  //$result['sql'] = $sqlUpdateStr;
  //============= Запрос в БД =============
  try{
  $sqlResult = mysqli_query($link, $sqlUpdateStr);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']="Insert request rejected by database. ($emessage))";goto endRequest;
  }
  if ($sqlResult <> true) {
    $sqlerror = mysqli_error($link);//Получение ошибки от БД
    $result['error']=true;$result['code']=500;$result['message']=$errors['insertReqRejected'] . "($sqlerror) (Edit user)";goto endRequest;
  }
  //============= Запрос в БД для получения измененной записи =============
  
  $sql= "SELECT users.id, users.firstName, users.lastName, users.email, users.phone, users.deliveryInfo, users.emailVerification, delivery_types.delivery_type, payment_types.payment_type FROM users LEFT OUTER JOIN delivery_types ON users.deliveryType_id = delivery_types.id LEFT OUTER JOIN payment_types ON users.paymentType_id = payment_types.id WHERE users.id = $userId";
  $sqlResult = mysqli_query($link, $sql);
  $numRows = mysqli_num_rows($sqlResult);
  if ($numRows <> 1) {
    $result['error']=true; $result['code'] = 400; $result['message'] = "DB return $numRows records!"; goto endRequest;
  }
  $result['user'] = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC);//Парсинг
  if ($result['user']['deliveryInfo']){
    $result['user']['deliveryInfo'] = json_decode($result['user']['deliveryInfo']);//Парсинг
  }
} elseif ($method === 'GET') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  include 'scripts/tokensOp.php';//Проверка токена
  $result = ['error' => false, 'code' => 200, 'message' => 'Request success!'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;
  }
  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) {
    goto endRequest;//Если пришла ошибка - завршаем скрипт
  } else {
    if ($result['userId']){
      $userId = $result['userId'];
      unset($result['userId']);
      unset($result['userPassword']);
    }else{
      $result['error']=true; $result['code'] = 500; $result['message'] = 'UserID not found in record! Critical error.'; goto endRequest;
    }
  }

  $sql= "SELECT users.id, users.firstName, users.lastName, users.email, users.phone, users.deliveryInfo, users.emailVerification, delivery_types.delivery_type, payment_types.payment_type FROM users LEFT OUTER JOIN delivery_types ON users.deliveryType_id = delivery_types.id LEFT OUTER JOIN payment_types ON users.paymentType_id = payment_types.id WHERE users.id = $userId";
  $sqlResult = mysqli_query($link, $sql);
  $numRows = mysqli_num_rows($sqlResult);
  if ($numRows <> 1) {
    $result['error']=true; $result['code'] = 400; $result['message'] = "DB return $numRows records!"; goto endRequest;
  }
  $result['user'] = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC);//Парсинг
  if ($result['user']['deliveryInfo']){
    $result['user']['deliveryInfo'] = json_decode($result['user']['deliveryInfo']);//Парсинг
  }
} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = 'Method Not Allowed';
}

endRequest:
if ($link) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);