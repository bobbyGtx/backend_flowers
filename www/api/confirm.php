<?php
header("Content-Type: text/html; charset=utf-8");

$method = $_SERVER['REQUEST_METHOD'];
include_once __DIR__ . '/scripts/variables.php';
include_once __DIR__ . '/scripts/enums.php';
include_once __DIR__ . '/scripts/connectDB.php';
include_once __DIR__ . '/scripts/languageOp.php';
include_once __DIR__ . '/scripts/confirmOp.php';
include_once __DIR__ . '/scripts/userOp.php';

$reqLanguage = languageDetection($_GET);//Определение запрашиваемого языка и возврат приставки

function render(string $template, array $vars = []): string {
  extract($vars, EXTR_SKIP);
  ob_start();
  include $template;
  return ob_get_clean();
}

if ($method === 'GET') {
  //Подтверждение E-Mail адреса и смена EMail. Обработка перехода по ссылке
  $result = ["error"=>false,'code'=>200,'message'=>'Operation was successful.'];

  if (isset($_GET['vToken'])) {
    $token = $_GET['vToken'];
    $result['message'] = match ($reqLanguage) {
      '_en' => 'Email address confirmed!',
      '_de' => 'E-Mail-Adresse wurde bestätigt!',
      default => 'Адрес электронной почты подтвержден!',
    };
    $operation = UserOpTypes::verifyEmail;
  }elseif(isset($_GET['eToken'])){
    $token = $_GET['eToken'];
    $result['message'] = match ($reqLanguage) {
      '_en' => 'Email address successfully changed!',
      '_de' => 'E-Mail-Adresse wurde erfolgreich geändert!',
      default => 'Адрес электронной почты успешно изменен!',
    };
    $operation = UserOpTypes::changeEmail;
  }else{
    $result['error']=true; $result['code'] = 400;$result['message'] = $opErrors['confTokenNotFound']; goto endRequest;
  }

  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500;$result['message'] = $dbError['connectionError'].". Message:" . $db_connect_response['message']; goto endRequest;
  }
  $result = checkConfirmationToken($result,$link,$token,$operation);
  if ($result['error'] && $result['code']!==403) goto endRequest;

  $opRecord = $result['opRecord']; unset($result['opRecord']);
  //file_put_contents(__DIR__ . '/debug.log', print_r($opRecord, true), FILE_APPEND);
  $record_id = $opRecord['id'];
  $userId = $opRecord['user_id'];

  if (!$result['error'] && $operation===UserOpTypes::changeEmail){
    $newEmail = $opRecord['newEmail'];
    $result = changeEmail($link, $result,$userId, $newEmail );//Может вернуть ошибку 406, если с newEmail проблема.

  }elseif (!$result['error'] && $operation===UserOpTypes::verifyEmail){
    $result = emailVerification($link, $result, $userId);
    if ($result['error']) goto endRequest;
  }

  //Вызываем даже в случае наличия ошибок. Внутри обработка 403 и 406 ошибки, при которой чистим данные
  $result = clearConfirmationField($result,$link,$record_id,$operation);
  if ($result['error']) goto endRequest;

} elseif($method === 'POST'){
  /**
   * Смена пароля через запрос с формы фронтенда
   * GET параметр:
   * @var string $token ['rToken'] - обязательный токен.
   * Обязательные входящие данные в теле запроса:
   * @var string $newPassword ['newPassword']
   * @var string $newPasswordRepeat ['newPasswordRepeat']
   * Возвращает стандартный ответ на фронтенд
   */
  include_once __DIR__ . '/scripts/crypt.php';
  $result = ["error"=>false,'code'=>200,'message'=>$infoMessages['reqSuccess']];
  $operation = UserOpTypes::resetPass;
  $token = $_GET[$operation->urlParam()] ?? null;
  if (empty($token)){
    $result['error']=true; $result['code'] = 400;
    $result['message'] = $productionMode?$opErrors['linkNotValid']:$opErrors['confTokenNotFound'];
    goto endPostRequest;
  }
  if (!preg_match($opTokenRegEx, $token)){
    $result['error']=true; $result['code'] = 400;
    $result['message'] = $productionMode?$opErrors['linkNotValid']:$opErrors['opTokenInvalid'];
    goto endPostRequest;
  }

  $postData = file_get_contents('php://input');
  $postDataJson = json_decode($postData, true);
  $newPassword = strtolower($postDataJson["newPassword"]) ?? null;
  if (empty($newPassword)){
    $result['error']=true; $result['code'] = 400;
    $result['message'] = $opErrors['newPasswortNotRecognized'];
    goto endPostRequest;
  }

  if (preg_match($passwordRegEx, $newPassword)){
    $result['error']=true; $result['code'] = 406;
    $result['message'] = $opErrors['newPasswortNotValid'];
    goto endPostRequest;
  }

  $newPasswordRepeat = $postDataJson["newPasswordRepeat"] ?? null;

  if ($newPassword !== $newPasswordRepeat){
    $result['error']=true; $result['code'] = 406;
    $result['message'] = $opErrors['passwordsNotMatch'];
    goto endPostRequest;
  }

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endPostRequest;
  }
  $settings = getSettings($link);//Получение ключа шифрования.
  if (!$settings) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbrequestSettings']; goto endPostRequest;
  } else  $key = $settings['secretKey'];//ключ шифрования паролей



  $newUserPass = __encode($newPassword,$key);


  endPostRequest:
  if (isset($link))mysqli_close($link);
  http_response_code($result['code']); unset($result['code']);
  echo json_encode($result);
  return;
}else{
  $result['code'] = 405;$result['message'] = $errors['MethodNotAllowed'];goto endRequest;
}


endRequest:
if (isset($link)) mysqli_close($link);
$message = $result['message'];
$code = $result['code'];
http_response_code($code);

if ($result['error']) {
 $page = render("{$templatesDir}/error{$reqLanguage}.php", [
    'message' => $productionMode && $code<>403?null:$message,
    'code' => $productionMode?null:$result['code'],
  ]);
}else{
  $page = render("{$templatesDir}/success{$reqLanguage}.php", [
    'message' => $message,
    'link' => $frontendAddress,
  ]);
}
echo $page;

