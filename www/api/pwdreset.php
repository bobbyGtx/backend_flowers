<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, PATCH, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Language");

$method = $_SERVER['REQUEST_METHOD'];
include_once 'scripts/variables.php';
include_once 'scripts/userOp.php';
include_once 'scripts/enums.php';
include_once 'scripts/languageOp.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки

if ($method === 'OPTIONS') {
  http_response_code(200);return;
} elseif ($method === 'POST') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['reqSuccess']];//Создание массива с ответом Ок
  $postData = json_decode(file_get_contents('php://input'), true);//парсинг параметров запроса

  if (!array_key_exists('email', $postData) || empty($postData['email'])) {
    $result['error'] = true; $result['code'] = 400; $result['message'] = $errors['emailNotRecognized'];goto endRequest;
  }else $postEmail = $postData['email'];

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;}

  $result = getUserInfoFromEmail($link, $result, $postEmail);
  if ($result['error']) goto endRequest;
  ['id'=>$userId,'email'=>$userEmail, 'blocked'=>$userBlocked]=$result['user']; unset($result['user']);
  if ($userBlocked===1){$result['error'] = true;$result['code'] = 403;$result['message'] = $infoMessages['userBlocked'];goto endRequest;}
  $operationType = UserOpTypes::resetPass;

  $result = createUserOpRecord($result,$link, $userId, $operationType);
  if ($result['error']) goto endRequest;
  ['token'=>$token, 'createdAt'=>$createdAt] = $result['data']; unset($result['data']);

  $languageTag = array_search($reqLanguage, $language);
  $result = sendOpEmail($result,$userEmail,$token,$operationType,$languageTag);

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if (isset($link)) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);

/*
 * Errors
 * 400 - $errors['emailNotRecognized'],$authError['emailNotFound']
 * 403 - $infoMessages['userBlocked']
 * 406 - $errors['emailNotValid']
 *
 */