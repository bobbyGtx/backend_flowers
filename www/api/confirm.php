<?php
header("Content-Type: text/html; charset=utf-8");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';

function render(string $template, array $vars = []): string {
  extract($vars, EXTR_SKIP);
  ob_start();
  include $template;
  return ob_get_clean();
}

if ($method === 'GET') {
  include_once 'scripts/enums.php';
  include_once 'scripts/connectDB.php';
  include_once 'scripts/confirmOp.php';
  $result = ["error"=>false,'code'=>200, 'message'=>'Operation was successful.', 'page'=>''];

  if (isset($_GET['vToken'])) {
    $operation = UserOpTypes::verifyEmail;
    $token = $_GET['vToken'];
  }elseif(isset($_GET['eToken'])){
    $operation = UserOpTypes::changeEmail;
    $token = $_GET['eToken'];
  }else{
    $result['error']=true; $result['code'] = 400; $result['message'] = 'Token not found!'; goto endRequest;
  }

  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = 'DB Connection Error ' . $db_connect_response['message']; goto endRequest;
  }

  //В этом месте я через if выполняю действия и формирую страницу с успешным результатом. Например: Вы успешно подтвердили свой eMail.

  // Переходим в endRequest: для отображения шаблона пользователю


} else {
  $result['code'] = 405;
  $title = 'Метод не разрешён';
  $message = 'Используйте GET';

  $result['page'] = render("{$templatesDir}/templates/error.php", [
    'title' => $title,
    'message' => $message,
    'code' => $result['code'],
  ]);
}


endRequest:
if (isset($link)) mysqli_close($link);
http_response_code($result['code']);
echo $result['page'];

