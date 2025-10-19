<?php
//Проверка токена на валидность
function checkEmail($link,$result,$email,$checkRegex=true)
{
   include 'variables.php';
   $funcName = 'checkEmail_func';
   //Нужен доп параметр для того, чтоб контролировать ошибку. Если ошибка по функции, мы можем не завершать основной скрипт
   if (empty($link)) {
      $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . "($funcName)"; goto endFunc;
   }
   if (empty($email)) {
      $result['error']=true; $result['code'] = 400; $result['message'] = 'Email parameter not found! ' . "($funcName)"; goto endFunc;
   }

   if ($checkRegex && !preg_match($emailRegEx, $email)){
      $result['funcError']=true; $result['message'] ='EMail not acceptable!' . "($funcName)"; goto endFunc;
   }

   $sql = "SELECT `id` FROM users WHERE email = '" . $email . "'";
   $sqlResult = mysqli_query($link, $sql);
   $numRows = mysqli_num_rows($sqlResult);
   if ($numRows <> 0) {
      $result['error']=true; $result['code'] = 400; $result['message'] = 'User with this email is already registered!'; unset($result['funcError']); goto endFunc;
   }
   endFunc:
   return $result;
}
function getUserInfo($link, $result, $userId, $languageTag = ''){
   include 'variables.php';
   $funcName = 'getUserInfo'.'_func';

   if (empty($result) || $result['error']){goto endFunc;}
   if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
   if (!$userId) {$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}
   
   $sql= "SELECT users.id, users.firstName, users.lastName, users.email, users.phone, users.deliveryInfo, users.emailVerification, delivery_types.deliveryType$languageTag as deliveryType, payment_types.paymentType$languageTag as paymentType
   FROM users 
   LEFT OUTER JOIN delivery_types ON users.deliveryType_id = delivery_types.id 
   LEFT OUTER JOIN payment_types ON users.paymentType_id = payment_types.id 
   WHERE users.id = $userId";
   try{
      $sqlResult = mysqli_query($link, $sql);
   } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName) ($emessage))";goto endFunc;
  }

   $numRows = mysqli_num_rows($sqlResult);
   if ($numRows <> 1) {
      $result['error']=true; $result['code'] = 400; $result['message'] = $dbError['unexpResponse'] . "($funcName)"; goto endFunc;
   }

   $result['user'] = mysqli_fetch_assoc($sqlResult);//Парсинг
   if ($result['user']['deliveryInfo']){
      $result['user']['deliveryInfo'] = json_decode($result['user']['deliveryInfo']);//Парсинг
   }

  endFunc:
  return $result;
}//Получение информации о пользователе. Возвращает данные в $result['user']

function login($link, $result, $login, $pass){
   include 'variables.php';
   $funcName = 'login'.'_func';

   if (empty($result) || $result['error']){goto endFunc;}
   if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
   if (empty($pass) || empty($login) ) {
      $result['error']=true; $result['code'] = 400; $result['message'] = $dataErr['notRecognized'] . "($funcName)"; goto endFunc;
   }

   //проверка на соответствие минимальным требованиям почты и пароля перед запросом в БД.
   if (!preg_match($emailRegEx, $login) || !preg_match($passwordRegEx, $pass)) {
      $result['error']=true; $result['code'] = 400; $result['message'] = $authError['loginOrPassNA'] . "($funcName)"; goto endFunc;
   } 

   $settings = getSettings($link);//Получение ключа шифрования. 
   if ($settings == false) {
      $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbrequestSettings']; goto endFunc;
   }
   $key = $settings['secretKey'];//ключ шифрования паролей
   $passwordEnc = __encode($pass, $key);//шифрование пароля

   $sql = "SELECT `id`,`email`,`password`,`emailVerification`,`blocked`  FROM users WHERE email = '" . $login . "' AND `password` = '" . $passwordEnc . "'";
   try{
      $sqlResult = mysqli_query($link, $sql);
   } catch (Exception $e){
      $emessage = $e->getMessage();
      $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName) ($emessage))";goto endFunc;
   }
  
   $numRows = mysqli_num_rows($sqlResult);
   if ($numRows <> 1) {
      $result['error']=true; $result['code'] = 401; $result['message'] = $authError['loginOrPassNC']; goto endFunc;
   }
   $row = mysqli_fetch_assoc($sqlResult);//парсинг
   if (empty($row['id']) || empty($row['email']) || empty($row['password'])) {
      $result['error']=true; $result['code'] = 500; $result['message'] = $errors['recognizeUnableDB']; goto endFunc;
   }
   if(boolval($row['blocked'])===true){
      $result['error']=true; $result['code'] = 403; $result['message'] = $infoMessages['userBlocked']; goto endFunc;
   }
   $result['user'] = ['userId'=>$row['id']];

   endFunc:
   return $result;
}//Запрос из таблицы пользователей Возвращает данные в $result['user'] = userId, firstName, lastName

function updateUserData($link, $result, $userId, $newData){
   include 'variables.php';
   $funcName = 'updateUserData'.'_func';

   if (empty($result) || $result['error']){goto endFunc;}
   if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
   if (!$userId) {$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}
   if (empty($newData) || !is_array($newData)){$result['error']=true; $result['message'] = $dataErr['dataInFunc'] . "($funcName)"; goto endFunc;}

   if (count($newData)<1){
      $result['code'] = 400;$result['message'] = $infoErrors['nothingToChange'] . "($funcName)"; goto endFunc;//error 406: nothing to change
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
   //============= Запрос в БД =============
   try{
      $sqlResult = mysqli_query($link, $sqlUpdateStr);
   } catch (Exception $e){
      $emessage = $e->getMessage();
      $result['error']=true; $result['code']=500; $result['message']=$errors['updReqRejected'] . "($funcName) ($emessage)";goto endFunc;
   }

   endFunc:
   return $result;
}//Сохранение изменений в учетную запись пользователя

function prepareNewData($result, $postDataJson){
   include 'variables.php';
   include 'deliveryOp.php';
   $funcName = 'prepareNewData'.'_func';

   if (empty($result) || $result['error']){goto endFunc;}
   if (empty($postDataJson)|| !is_array($postDataJson)){$result['error']=true; $result['message'] = $errors['dataNotFound'] . "($funcName)"; goto endFunc;}
   if (count($postDataJson)===0){$result['error']=true; $result['message'] = $infoErrors['nothingToChange'] . "($funcName)"; goto endFunc;}

   if ($postDataJson['deliveryType_id']){
      settype($postDataJson['deliveryType_id'], 'integer');//защита от инъекции
      $patchDeliveryId = $postDataJson['deliveryType_id'];
   } 
   if ($postDataJson['paymentType_id']){
      settype($postDataJson['paymentType_id'], 'integer');//защита от инъекции
      $patchPaymentId = $postDataJson['paymentType_id'];
   }

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
      if ($result['error']){goto endFunc;}
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
         if (in_array($patchDeliveryInfo['region'], $regionsD)){
            $deliveryInfo['region']=$patchDeliveryInfo['region'];
         }else{
            $result['error']=true; $messages[] ='Delivery Info -> Region invalid!';
         }
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
//запрос вынести
   if (!empty($patchDeliveryId) && $patchDeliveryId>0){
      $result = getDeliveryInfo($link, $result, $patchDeliveryId,$reqLanguage, true,false);
      if ($result['error']) goto endFunc;
      $rowDelivery = $result['selectedDelivery']; unset($result['selectedDelivery']);
      if (intval($rowDelivery['disabled'])===0){
         $newData['deliveryType_id']=$postDataJson['deliveryType_id'];
      }else{
         $result['error']=true; $messages[] = $infoErrors['delivNotPos'];
      }
   }//проверка типа доставки 
   if (!empty($patchPaymentId) && $patchPaymentId>0){
      $result = checkPayment($link, $result, $patchPaymentId,$reqLanguage, true,false);
      if ($result['error']) goto endFunc;
      $rowPayment = $result['selectedPayment']; unset($result['selectedPayment']);
      if (intval($rowPayment['disabled'])===0){
         $newData['paymentType_id']=$postDataJson['paymentType_id'];
      }else{
         $result['error']=true; $messages[] = $infoErrors['paymentNotPos'];
      }
   }//проверка типа оплаты

   if (count($messages)>0) {
      $result['code'] = 406;$result['message'] = $errors['dataNotAcceptable'] . "($reqName)"; $result['messages'] = $messages; goto endFunc;//error 406: unacceptable format
   }//Если есть ошибки данных - выводим их

   $result['newData'] = $newData;

   endFunc:
   return $result;
}
