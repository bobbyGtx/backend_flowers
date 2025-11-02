<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';
include 'scripts/languageOp.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки

if ($method === 'OPTIONS') {
  http_response_code(200);
  return;//ответ на пробный запрос
} elseif ($method === 'GET') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования

  $result = ['error' => false, 'code' => 200, 'message' => 'Request success!'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect();
  $link = $db_connect_response['link'];//Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = 'DB connection Error! ' . $db_connect_response['message'];
    goto endRequest;
  }

  $sql="SELECT
  c.id as category_id,
  c.name$reqLanguage as category_name,
  c.url as category_url,
  t.id,
  t.name$reqLanguage as name,
  t.url
  FROM types t 
  INNER JOIN categories c ON t.category_id = c.id
  ORDER By c.id ASC";

  try {
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['selReqRejected'] . "(CategoriesWithTypes) ($emessage))";
    goto endRequest;
  }

  if (mysqli_num_rows($sqlResult) === 0) {
    $result['error'] = true;$result['code'] = 400; $result['message'] = "DB return null records from Table 'Types'!";goto endRequest;
  }
  $catWithTypesReq = mysqli_fetch_all($sqlResult, MYSQLI_ASSOC);

  $catWithTypes=[];
  foreach ($catWithTypesReq as $row) {
    $categoryId= $row["category_id"];
    if (empty($catWithTypes[$categoryId])){
      $catWithTypes[$categoryId]=['id'=>$categoryId,'name'=>$row['category_name'],'url'=>$row['category_url'],'types'=>[]];
      $catWithTypes[$categoryId]['types'][]=['id'=> $row['id'],'name'=> $row['name'],'url'=> $row['url'],'category_id'=> $categoryId];
    }else{
      $catWithTypes[$categoryId]['types'][]=['id'=> $row['id'],'name'=> $row['name'],'url'=> $row['url'],'category_id'=> $categoryId];
    }
  }
  $result['categories'] = array_values($catWithTypes);

} else {
  $result['error'] = true;$result['code'] = 405;$result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link) mysqli_close($link);
http_response_code($result['code']);
unset($result['code']);
echo json_encode($result);