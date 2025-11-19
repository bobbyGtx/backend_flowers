<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Access-Token, X-Language, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];

if ('OPTIONS' === $method) {
  http_response_code(200); echo "ok"; return;
}elseif ('POST' === $method) {
  include 'scripts/connectDB.php';//Подключение к БД и настройки

  $result = ['error' => false, 'code' => 200, 'message' => 'User logged out'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'DB connection Error! ' . $db_connect_response['message']; goto endRequest;
  }
  $postData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($postData, true);//парсинг параметров запроса
  $refreshToken = $postDataJson["refreshToken"];//токен из запроса

  if (empty($refreshToken)) {
    $result['error']=true; $result['code'] = 400; $result['message'] = $dataErr['notRecognized']; goto endRequest;
  }
  
  if(preg_match($refreshTokenRegEx, $refreshToken)){
    $sql = "UPDATE `$userTableName` SET `$accTokenField`='',`$accTokenLifeField`='0',`$refreshTokenField`='',`$refrTokenLifeField`='0' WHERE `$refreshTokenField`= '" . $refreshToken . "'";
    $sqlResultSaveTokens = mysqli_query($link, $sql);//выполняем запрос
    if (empty($sqlResultSaveTokens)) {
      $result['error']=true; $result['code'] = 500; $result['message'] = $errors['delReqRejected']; goto endRequest;
    }
  }else{
    $result['error']=true; $result['code'] = 400; $result['message'] = $errors['dataNotAcceptable'];
  }

} else {
  include 'scripts/variables.php';
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link)mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);
