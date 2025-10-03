<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];
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
  "entrance": "1",
  "apartment": "13",
  "comment": "вава"
  }
   */
  //Ошибки
  /*
  400 - доставка не возможна
  406 - Not Acceptable (основной запрос не содержит некоторых данных!)(Не достаточно товаров на складе. cartOp_func), 
  500 - ошибки входящих данных в функции
   */
  
  $result = ['error' => false, 'code' => 200, 'message' => 'Order placed!'];//Создание массива с ответом Ок
  //Обработка входных данных
  $postData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($postData, true);//парсинг параметров запроса
  $messages = [];//Массив для ошибок
  $incOrder = [];//Переменная для сбора входящих данных заказа
  if (!empty($postDataJson['deliveryType']) && intval($postDataJson['deliveryType'])>0){
    $incOrder['deliveryTypeId'] = intval($postDataJson['deliveryType']);
  } else {$result['error']=true; $messages[] = 'Invalid deliveryType';}
  if (!empty($postDataJson['firstName']) && (preg_match($firstNameRegEx, $postDataJson['firstName']))){
    $incOrder['firstName']=$postDataJson['firstName'];
  } else {$result['error']=true; $messages[] = 'Invalid First Name!';}
  if (!empty($postDataJson['lastName']) && (preg_match($lastNameRegEx, $postDataJson['lastName']))){
    $incOrder['lastName']=$postDataJson['lastName'];
  } else {$result['error']=true; $messages[] = 'Invalid Last Name!';}
  if (!empty($postDataJson['phone']) && (preg_match($telephoneRegEx, $postDataJson['phone']))){
    $incOrder['phone']=$postDataJson['phone'];
  } else {$result['error']=true; $messages[] = 'Invalid Phone!';}
  if (!empty($postDataJson['email']) && (preg_match($emailRegEx, $postDataJson['email']))){
    $incOrder['email']=$postDataJson['email'];
  } else {$result['error']=true; $messages[] = 'Invalid Email!';}
  if (!empty($postDataJson['paymentType']) && intval($postDataJson['paymentType'])>0){
    $incOrder['paymentTypeId'] = intval($postDataJson['paymentType']);
  } else {$result['error']=true; $messages[] = 'Invalid Payment Type';}
  if (!empty($postDataJson['comment'])){$incOrder['comment']=$postDataJson['comment'];}
//Обязательные параметры проверены. Подключаемся к базе и проверяем токен для проверки остальных
  if (!$result['error']){
    $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
    if ($db_connect_response['error'] == true || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;}
    $result = checkToken($link, $result, getallheaders(),true);
    if ($result['error']) {goto endRequest;}
    else {
      if ($result['userId'] && $result['userPassword']){
        $userId = $result['userId'];unset($result['userId']);
        $userPwd = $result['userPassword'];unset($result['userPassword']);
      }else{
      $result['error']=true; $result['code'] = 500; $result['message'] = 'User data not found in record! Critical error.'; goto endRequest;
      }//Проверка наличия логина и пароля
    }
    
    //Запрос инфо о доставке и обработка ответа
    $result = getDeliveryInfo($link, $result, $incOrder['deliveryTypeId'],$requestLanguage, true, true);
    if ($result['error']){goto endRequest;}
    $selectedDelivery = $result['selectedDelivery']; unset($result['selectedDelivery']);
    $needAddress = intval($selectedDelivery['addressNeed']);
  }

  //Проверка адреса если она необходима по доставке
  $address=[];
  if ($needAddress){
    if (!empty($postDataJson['zip']) && (preg_match($zipCodeRegEx, $postDataJson['zip']))){
      $address['zip']=$postDataJson['zip'];
    } else {$result['error']=true; $messages[] = 'Invalid ZIP Code';}
    if (!empty($postDataJson['region']) && in_array($postDataJson['region'], $regionsD)){
      $address['region']=$postDataJson['region'];
    } else {$result['error']=true; $messages[] = 'Invalid Region';}
    if (!empty($postDataJson['city'])){$address['city']=$postDataJson['city'];} else {$result['error']=true; $messages[] = 'Invalid Сity!';}
    if (!empty($postDataJson['street'])){$address['cistreetty']=$postDataJson['street'];} else {$result['error']=true; $messages[] = 'Invalid Street!';}
    if (!empty($postDataJson['house'])){$address['house']=$postDataJson['house'];} else {$result['error']=true; $messages[] = 'Invalid House!';}
    if (!empty($postDataJson['entrance'])){$address['entrance']=$postDataJson['entrance'];}
    if (!empty($postDataJson['apartment'])){$address['apartment']=$postDataJson['apartment'];}
  } else {$address=null;}

  //Проверка правильности и доступности метода оплаты
  $result = checkPayment($link,$result, $incOrder['paymentTypeId'],$reqLanguage);
  if ($result['error']){goto endRequest;}

  if (count($messages)>0) {
    $result['code'] = 406;$result['message'] = 'Data not Acceptable!'; $result['messages'] = $messages; goto endRequest;//error 406: unacceptable format
  }//Если есть ошибки данных - выводим их
 
  //Начинаем обработку карзины пользователя
/*-----Получение списка товаров в корзине пользователя-----*/
  $result = getCart($link, $result, $userId); //true возвращает объект как массив
  if ($result['error']){goto endRequest;}
  $userCartItems = $result['userCartItems']; unset($result['userCartItems']);

/*-----Получение всей информации о товарах в корзине, формирование массива с новыми остатками товаров на складе-----*/
  $result = cartToOrder($link,$result,$userCartItems,$reqLanguage);
  if ($result['error']){goto endRequest;}

  $orderProducts = $result['products']; unset($result['products']);//Детализированный список продуктов в карзине
  $updatesProducts = $result['updatesProducts']; unset($result['updatesProducts']); //Массив с новыми остатками товаов на складе
  if (!is_array($updatesProducts)||count($updatesProducts)===0){
    $result['error'] = true; $result['code'] = 501; $result['message'] = "The array of changes to the number of products was not found."; goto endRequest;
  }
  $order=compileOrderData($incOrder, $selectedDelivery, $address, $orderProducts, $userId);

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
  include 'scripts/cartOp.php';
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

  $result = ['error' => false, 'code' => 200, 'message' => 'Request success!', 'count' => 0];//Создание массива с ответом Ок
  //Получение корзины пользователя
  $sql = "SELECT `id`,`user_id`,`items`,`createdAt`,`updatedAt` FROM `carts` WHERE `user_id`= $userId;";
  try{
  $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']="Insert request rejected by database. (UserRegister->InsertCart) ($emessage))";goto endRequest;
  }

  $result['createdAt'] = 0;$result['updatedAt'] = 0;$result['items'] = [];//Корректировка массива с ответом Ок
  if (mysqli_num_rows($sqlResult)===0){
    $result = createUserCart($link,$result,$userId);
    if ($result['error']){
      goto endRequest;
    } else {
      if ($_GET["cartCount"]){
        unset($result['items']); 
        unset($result['createdAt']); 
        unset($result['updatedAt']); 
        goto endRequest;
      }// Обработка запроса количества товара
      $result['items'] = []; $result['itemsInCart'] = 0;goto endRequest;
      }
  }//Если нет записи в таблице - создаем ответ и завершаем запрос

  $row = mysqli_fetch_assoc($sqlResult);//парсинг 

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = 'Method Not Allowed';
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