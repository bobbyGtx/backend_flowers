<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Access-Token, X-Language");

$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';
include 'scripts/languageOp.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки

if ($method === 'OPTIONS') {
  http_response_code(200);//ответ на пробный запрос
  return;
} elseif ($method === 'POST') {
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/tokensOp.php';//Проверка токена
  include 'scripts/deliveryOp.php';
  include 'scripts/paymentOp.php';
  include 'scripts/cartOp.php';
  include 'scripts/orderOp.php';
  include 'scripts/sqlOp.php';
  /*

  {
    "deliveryType": "self",
    "firstName": "Владимир",
    "lastName": "Волобуев",
    "fatherName": "Эдуардович",
    "phone": "380668000709",
    "paymentType": "cardToCourier",
    "email": "bob@gmail.com",
    "comment": "Нет коммента"
  }
  {
  "deliveryType": "delivery",
  "firstName": "Владимир",
  "lastName": "Волобуев",
  "phone": "380668000709",
  "email": "bob@gmail.com",
  "paymentType": "cardOnline",
  "zip": "Königsberger Str.",
  "region": "Königsberger Str.",
  "city": "Saarbrucken",
  "street": "Königsberger Str.",
  "house": "2",
  "comment": "вава"
  }
   */

  //Ошибки

  /*
  400 - доставка не возможна
  406 - Not Acceptable (основной запрос не содержит некоторых данных!)(Не достаточно товаров на складе. cartOp_func), 
  500 - ошибки входящих данных в функции
   */

  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['reqSuccess']];//Создание массива с ответом Ок
  
  $postData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($postData, true);//парсинг параметров запроса

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;}
  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) {goto endRequest;}
  else {
    if ($result['userId'] && $result['userPassword']){
      $userId = $result['userId'];unset($result['userId']);
      $userPwd = $result['userPassword'];unset($result['userPassword']);
    }else{
      $result['error']=true; $result['code'] = 500; $result['message'] = $critErr['userDNotFound']; goto endRequest;
    }//Проверка наличия логина и пароля
  }

//-----Обработка входных данных-----
  $result = prepareOrderData($link, $result,$reqLanguage, $postDataJson);
  if ($result['error']) goto endRequest;
  $incOrder = $result['incOrder']; unset($result['incOrder']);
  
  $selectedDelivery = $result['selectedDelivery']; unset($result['selectedDelivery']);
  if (isset($result['address'])){$address = $result['address']; unset($result['address']);}
  
//-----Начинаем обработку карзины пользователя-----
//-----Получение списка товаров в корзине пользователя-----
  $result = getCart($link, $result, $userId);
  if ($result['error']){goto endRequest;}
  if (!$result['userCart'] || count($result['userCart'])<1 ){
    $result['error']=true; $result['code']=400; $result['message']=$errors['cartEmpty'];goto endRequest;
  }//Если корзина пустая - ошибка
  $userCartItems = $result['userCart']['items']; unset($result['userCart']);

//-----Получение всей информации о товарах в корзине, формирование массива с новыми остатками товаров на складе-----
  $result = cartToOrder($link,$result,$userCartItems,$userId,$reqLanguage);
  if ($result['error']){goto endRequest;}

  $productsData = $result['productsData']; unset($result['productsData']);//Детализированный список продуктов в карзине и общая стоимость
  $updatesProducts = $result['updatesProducts']; unset($result['updatesProducts']); //Массив с новыми остатками товаов на складе
  if (!is_array($updatesProducts)||count($updatesProducts)===0){
    $result['error'] = true; $result['code'] = 501; $result['message'] = "The array of changes to the number of products was not found."; goto endRequest;
  }
  
  $order=compileOrderData($incOrder, $selectedDelivery, $address, $productsData, $userId);
  // Выключаем автокоммит. Начинаем транзакцию
  mysqli_autocommit($link, false);
  //1) Проверить выбранное кол-во товаров на доступность и уменьшить их кол-во на складе
  $result = updateProductsCounts($link, $result, $updatesProducts);if ($result['error']===true){goto endRequest;}//учет купленных продуктов в БД
  if ($result['error'])goto endTransaction;
  //2) Сохранить запись в orders
  $result = createOrder($link, $result, $order);
  if ($result['error'])goto endTransaction;
  $newOrderId = $result['newOrderId']; unset($result['newOrderId']);
  //Чистка корзины пользователя после успешного создания заказа
  $result = clearUserCart($link, $result, $userId);
  if ($result['error']) goto endTransaction; 
  endTransaction:
  if (!$result['error']){
    mysqli_commit($link);//сохраняем изменения транзакции, если нет ошибок
  }else{
    mysqli_rollback($link);// откат изменений в случае ошибки
    goto endRequest;
  }
  //4) Сгенерировать ответ пользователю
  $result = getOrder($link, $result, $newOrderId,$reqLanguage);
  if ($result['error']) goto endRequest;


} elseif ($method === 'GET') {
  include 'scripts/connectDB.php';//Подключение к БД и настройки + модуль шифрования
  include 'scripts/tokensOp.php';//Проверка токена
  include 'scripts/orderOp.php';
  $result = ['error' => false, 'code' => 200, 'message' => 'Request success!'];//Создание массива с ответом Ок
  $priorityMsg = null; //Добавочное сообщение на случай не критической ошибка. Добавляется к ответу вместо message в конце успешной обработки соответств. запроса
  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;
  }
 
  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) {
    goto endRequest;//Если пришла ошибка - завршаем скрипт
  } else {
    if ($result['userId'] && $result['userPassword']){
      $userId = $result['userId'];
      $userPwd = $result['userPassword'];
      unset($result['userId']); unset($result['userPassword']);
    }else{
      $result['error']=true; $result['code'] = 500; $result['message'] = 'User data not found in record! Critical error.'; goto endRequest;
    }
  }

  $result = getOrders($link, $result, $userId, $reqLanguage);
  if ($result['error']) goto endRequest;


} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if ($link) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);


/* 
запрос
deliveryCost: 10
deliveryType: "self"
email: "bob@gmail.com"
fatherName: "Эдуардович"
firstName: "Владимир"
items: [{id: "68d18ee8e3e68a8e84654cf5", name: "Сенецио Роули", quantity: 1, price: 26, total: 26},…]
lastName: "Волобуев"
paymentType: "cashToCourier"
phone: "380668000709"
status: "new"
totalAmount: 76
*/


/*ответ после оформления заказа
{
    "items": [
        {
            "id": "68d18ee8e3e68a8e84654cf5",
            "name": "Сенецио Роули",
            "quantity": 1,
            "price": 26,
            "total": 26
        },
        {
            "id": "68d18ee8e3e68a8e84654cf6",
            "name": "Сансевиерия трехпучковая Муншайн",
            "quantity": 1,
            "price": 50,
            "total": 50
        }
    ],
    "deliveryCost": 10,
    "totalAmount": 76,
    "deliveryType": "self",
    "firstName": "Владимир",
    "lastName": "Волобуев",
    "fatherName": "Эдуардович",
    "phone": "380668000709",
    "email": "bob@gmail.com",
    "paymentType": "cashToCourier",
    "status": "new",
    "createdAt": "2025-09-22T18:01:12.856Z"
}
*/

/* Get orders
[
    {
        "items": [
            {
                "id": "68c8dce13b536f9110cac7f6",
                "name": "Цветущие маммилярии",
                "quantity": 3,
                "price": 17,
                "total": 51
            },
            {
                "id": "68c8dce13b536f9110cac7f7",
                "name": "Пахицереус Прингля",
                "quantity": 2,
                "price": 24,
                "total": 48
            },
            {
                "id": "68c8dce13b536f9110cac7f8",
                "name": "Эхинокактус Грузона",
                "quantity": 1,
                "price": 24,
                "total": 24
            }
        ],
        "deliveryCost": 10,
        "totalAmount": 123,
        "deliveryType": "self",
        "firstName": "Владимир",
        "lastName": "Волобуев",
        "fatherName": "Эдуардович",
        "phone": "380668000709",
        "email": "bob@gmail.com",
        "paymentType": "cashToCourier",
        "status": "new",
        "createdAt": "2025-09-16T03:43:29.087Z"
    },
    {
        "items": [
            {
                "id": "68d18ee8e3e68a8e84654cf5",
                "name": "Сенецио Роули",
                "quantity": 1,
                "price": 26,
                "total": 26
            },
            {
                "id": "68d18ee8e3e68a8e84654cf6",
                "name": "Сансевиерия трехпучковая Муншайн",
                "quantity": 1,
                "price": 50,
                "total": 50
            }
        ],
        "deliveryCost": 10,
        "totalAmount": 76,
        "deliveryType": "self",
        "firstName": "Владимир",
        "lastName": "Волобуев",
        "fatherName": "Эдуардович",
        "phone": "380668000709",
        "email": "bob@gmail.com",
        "paymentType": "cashToCourier",
        "status": "new",
        "createdAt": "2025-09-22T18:01:12.856Z"
    }
]

*/