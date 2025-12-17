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
} elseif ($method === 'POST') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['reqSuccess']];//Создание массива с ответом Ок
  $postData = json_decode(file_get_contents('php://input'), true);//парсинг параметров запроса

  if (array_key_exists('key', $postData) && !empty($postData['key']) && preg_match($passwordRegEx,$postData['key'])) {
    $postPassword = $postData['key'];
  }else{
    $result['error'] = true; $result['code'] = 400; $result['message'] = $authError['wrongPassword'];goto endRequest;
  }

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;}

  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) goto endRequest;
  if ($result['userId'] && $result['userPassword']){$userId = $result['userId'];$userPwd = $result['userPassword'];unset($result['userId'],$result['userPassword'],$result['userEmail']); }

  $settings = getSettings($link);//Получение ключа шифрования.
  if (!$settings) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbrequestSettings']; goto endRequest;
  } else  $key = $settings['secretKey'];//ключ шифрования паролей

  if ($postPassword !==__decode($userPwd, $key)){
    $result['error'] = true; $result['code'] = 400; $result['message'] = $authError['wrongPassword'];goto endRequest;
  }else goto endRequest;//возвращаем ответ 200

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if (isset($link)) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);