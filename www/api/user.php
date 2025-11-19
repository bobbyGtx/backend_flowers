<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, PATCH, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Access-Token, X-Language");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';
include 'scripts/tokensOp.php';//Проверка токена
include 'scripts/userOp.php';
include 'scripts/languageOp.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки

if ($method === 'OPTIONS') {
  http_response_code(200);//ответ на пробный запрос
  return;
} elseif ($method === 'PATCH') {
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/paymentOp.php';
  $reqName = 'user [PATCH]'; 

  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['recordChanged']];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $dbError['connectionError'] . "($reqName)" . $db_connect_response['message']; goto endRequest;
  } else $settings = getSettings($link);//Получение ключа шифрования.

  if ($settings == false) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbrequestSettings'] . "($reqName)"; goto endRequest;
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
      $result['error']=true; $result['code'] = 500; $result['message'] = $critErr['userDNotFound'] . "($reqName)"; goto endRequest;
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

//============= Обработка полученных данных и формирование изменений =============
  $result = prepareNewData($result, $postDataJson);
  if ($result['error']) goto endRequest;
  $newData = $result['newData']; unset($result['newData']);

  //============= Запрос в БД для применения изменений =============
  $result = updateUserData($link, $result, $userId,$newData);
  if ($result['error']) goto endRequest;
    
  //$result['debug'] = $result;
  //============= Запрос в БД для получения измененной записи =============
  $result = getUserInfo($link, $result, $userId, $reqLanguage);
  if ($result['error']) goto endRequest;
} elseif ($method === 'GET') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  $reqName = 'user [GET]';
  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['reqSuccess']];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] ."($reqName)". $db_connect_response['message']; goto endRequest;
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
      $result['error']=true; $result['code'] = 500; $result['message'] = $critErr['userIdNotFound']."($reqName)"; goto endRequest;
    }
  }
  $result = getUserInfo($link, $result, $userId, $reqLanguage);
  if ($result['error']) goto endRequest;
} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);