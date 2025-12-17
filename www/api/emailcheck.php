<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, PATCH, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Access-Token, X-Language");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';


if ($method === 'OPTIONS') {
  http_response_code(200);//ответ на пробный запрос
  return;
} elseif ($method === 'POST') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  include 'scripts/userOp.php';
  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['reqSuccess']];//Создание массива с ответом Ок
  $postData = json_decode(file_get_contents('php://input'), true);//парсинг параметров запроса

  if (!array_key_exists('email', $postData) || empty($postData['email'])) {
    $result['error'] = true; $result['code'] = 400; $result['message'] = $errors['emailNotRecognized'];goto endRequest;
  }

  if (!preg_match($emailRegEx,$postData['email'])){
    $result['error'] = true; $result['code'] = 406; $result['message'] = $errors['emailNotValid'];goto endRequest;
  }//Проверка вне функции чтоб не подключаться к БД лишной раз
  $postEmail = $postData['email'];
  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;}

  $result = checkEmail($link,$result,$postEmail,false);
  if ($result['error'] === true)goto endRequest;

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if (isset($link)) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);