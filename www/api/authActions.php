<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, PATCH, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Language");

/**
 * Обязательные входящие данные в теле запроса:
 * @var UserOpTypes $operation (verifyEmail|resetPass|)//changeEmail not work
 * @var string      $email
 *  Errors
 *  400 - E-Mail not recognized!,E-mail not found in DB!,Email address already confirmed!
 *  403 - User blocked!
 *  406 - Email not valid!
 *  429 - Добавляется переменная $result['timer']
 *      - Reset password request limit exceeded!
 *      - Email verification request limit exceeded!
 */

$method = $_SERVER['REQUEST_METHOD'];
include_once __DIR__ . '/scripts/variables.php';
include_once __DIR__ . '/scripts/userOp.php';
include_once __DIR__ . '/scripts/enums.php';
include_once __DIR__ . '/scripts/languageOp.php';
include_once __DIR__ . '/utils/checkUtils.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки

if ($method === 'OPTIONS') {
  http_response_code(200);return;
} elseif ($method === 'POST') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['reqSuccess']];//Создание массива с ответом Ок
  $postData = json_decode(file_get_contents('php://input'), true);//парсинг параметров запроса
  if (isset($postData['operation'])) $operation = UserOpTypes::tryFrom($postData['operation']);
  if (!isset($operation)) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['unknownOperationType']; goto endRequest;}
  if ($operation !== UserOpTypes::verifyEmail && $operation !== UserOpTypes::resetPass){
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['opTypeNotSupport'];goto endRequest;
  }
  if (isset($postData['email'])) $userEmailPost = $postData['email'];
  if (!isset($userEmailPost)) {$result['error']=true; $result['code'] = 400; $result['message'] = $errors['emailNotRecognized']; goto endRequest;}
  $languageTag = array_search($reqLanguage, $language);

  $result = checkRateLimit($result,$userEmailPost,$operation);
  if ($result['error']) goto endRequest;

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;}

  $result = getUserInfoFromEmail($link, $result, $userEmailPost);
  if ($result['error']) goto endRequest;
  ['id'=>$userId,'email'=>$userEmail, 'blocked'=>$userBlocked, 'emailVerification'=>$emailVerification]=$result['user']; unset($result['user']);
  if ($userBlocked===1){$result['error'] = true;$result['code'] = 403;$result['message'] = $infoMessages['userBlocked'];goto endRequest;}

  if ($operation === UserOpTypes::verifyEmail && $emailVerification!==0){
    $result['error']=true; $result['code'] = 400; $result['message'] = $errors['emailAlreadyConfirmed']; goto endRequest;
  }

  $result = createUserOpRecord($result,$link, $userId, $operation);
  if ($result['error']) goto endRequest;
  ['token'=>$token, 'createdAt'=>$createdAt] = $result['data']; unset($result['data']);
  $result['timer'] = $rateLimit;

  $result = sendOpEmail($result,$userEmail,$token,$operation,$languageTag);
  if ($result['error']) goto endRequest;

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if (isset($link)) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result, JSON_UNESCAPED_UNICODE);