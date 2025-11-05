<?php
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: OPTIONS, POST, PATCH, GET, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


$method = $_SERVER['REQUEST_METHOD'];
include 'scripts/variables.php';
include 'scripts/languageOp.php';
$reqLanguage = languageDetection(getallheaders());//Определение запрашиваемого языка и возврат приставки

if ($method === 'OPTIONS') {
  http_response_code(200);//ответ на пробный запрос
  return;
}elseif ($method === 'POST') {
  //Функция создана для добавления корзины товаров, созданной без авторизации, но после успешной авторизации. Это значит что записи с кол-вом 0 можно просто отсеять
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/tokensOp.php';//Проверка токена
  include 'scripts/cartOp.php';
  //Создание массива с ответом Ок
  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['reqSuccess']];

  //Обработка входных данных products[{"productId": "x","quantity": "y"},{"productId": "x","quantity": "y"}]
  $postData = json_decode(file_get_contents('php://input'), true);//получение запроса и парсинг
  $productsPost = $postData['products'];
  if(empty($productsPost) || !is_array($productsPost) || count($productsPost)===0) {
   $result['error']=true; $result['code']=400; $result['message']=$dataErr['notRecognized']; goto endRequest;
  }
  $products = [];//массив для отфильтрованных продуктов
  foreach ($productsPost as $product) {
    if (isset($product['quantity']) && isset($product['productId']) && intval($product['quantity'])>0 && intval($product['productId'])>0){
      $findedIndex = array_search(intval($product['productId']),array_column($products,'productId'),true);
      if ($findedIndex===false){
        $products[]=["productId"=>intval($product['productId']),"quantity"=>intval($product['quantity'])];
      }else{
        $products[$findedIndex]=["productId"=>intval($product['productId']),"quantity"=>intval($product['quantity'])];
      }
    }
  }
  if (count($products)===0){
    $result['error']=true; $result['code']=400; $result['message']=$dataErr['notRecognized']; goto endRequest;
  }
  unset($productsPost);

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;}

  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) {goto endRequest;}
  if ($result['userId'] && $result['userPassword']){$userId = $result['userId'];$userPwd = $result['userPassword'];unset($result['userId'],$result['userPassword']); }

  //Проверка товаров перед добавлением в корзину
  $result = checkProducts($link,$result,$products, $reqLanguage);//reuslt['products'],reuslt['productsChecked'] & result['messages']?
  if ($result['error']){goto endRequest;}
  $products = $result['products'];//Проверенные продукты готовые для добавления в корзину
  $productsChecked = $result['productsChecked'];//Проверенные продукты для передачи в функцию добавления в базу
  unset($result['productsChecked'],$result['products']);
  if (count($products)<1){$result['error']=true;$result['code']=400;$result['message']=$errors['productsNotFound']; goto endRequest;}

  $result = updateUserCart($link,$result,$userId,$productsChecked, time(),NULL);
  if ($result['error']){goto endRequest;}
  
  $result = formatUserCart($result, $products, time(),null);
  
  //$result = compileUserCart($link,$result,$userCartItems, $userId, $reqLanguage);
  //if ($result['error']){goto endRequest;}
} elseif ($method === 'PATCH') {
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/tokensOp.php';//Проверка токена
  include 'scripts/cartOp.php';

  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['reqSuccess']];//Создание массива с ответом Ок

  //{"productId": 1, "quantity": 2}
  //Обработка входных данных
  $postData = file_get_contents('php://input');//получение запроса
  $postDataJson = json_decode($postData, true);//парсинг параметров запроса
  settype($postDataJson['productId'], 'integer');//защита от инъекции
  $postProductId = $postDataJson['productId'];
  settype($postDataJson['quantity'], 'integer');//защита от инъекции
  $postQuantity = $postDataJson['quantity'];
  if ($postProductId<1){$result['error']=true; $result['code'] = 400; $result['message'] = 'Request parameters (productId) not recognized!'; goto endRequest;}
  if ($postQuantity<0){$result['error']=true; $result['code'] = 400; $result['message'] = 'Request parameters (quantity) not recognized!'; goto endRequest;}

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {$result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;}

  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) {goto endRequest;}
  if ($result['userId'] && $result['userPassword']){$userId = $result['userId'];$userPwd = $result['userPassword'];unset($result['userId'],$result['userPassword']);}

  //Проверка товара перед добавлением в корзину
  $result = checkProduct($link,$result,$postProductId,$postQuantity, $reqLanguage);
  if ($result['error']){goto endRequest;}
  $product = $result['product']; unset($result['product']);

  //Получение корзины пользователя для объединения новых со старыми товарами
  $result = getCart($link, $result, $userId);
  if ($result['error']){goto endRequest;}

  $userCartItems = $result['userCart']['items'];
  $createdAt =  $result['userCart']['createdAt'];
  $updatedAt = $result['userCart']['updatedAt'];

  if (count($userCartItems) === 0 ){
    if ($postQuantity>0){
      //корзина только создана, если количество товара больше 0
      array_push($userCartItems,["quantity"=>$postQuantity,"productId"=>$postProductId]);
      $createdAt = time();
      $updatedAt = null;
    }
  }else{
    $itemIndex = array_search($postProductId,array_column($userCartItems,'productId'),true);
    if ($itemIndex === false){
      if ($postQuantity>0) {
        array_push($userCartItems,["quantity"=>$postQuantity,"productId"=>$postProductId]);
        $updatedAt = time();
      }
    }else{
      if ($postQuantity===0){
        unset($userCartItems[$itemIndex]);$userCartItems=array_values($userCartItems);
      }else{
        $userCartItems[$itemIndex]["quantity"]=$postQuantity;
      }
      $updatedAt = time();
    }
  }

  unset($result['userCart']);
  $result = updateUserCart($link, $result, $userId, $userCartItems, $createdAt, $updatedAt);
  if ($result["error"]){goto endRequest;}


  if (count($userCartItems)===0){
    $result = formatUserCart($result, [],$createdAt,$updatedAt);
    goto endRequest;
  }//Если удалили последний товар из корзины - формируем ответ и выходим

  if (count($userCartItems)===1 && $userCartItems[0].['productId']===$postProductId){
    $result = formatUserCart($result, [$product],$createdAt,$updatedAt);
    goto endRequest;
  } //Если единственный продукт в корзине, это добавленный - выводим его в ответ без запроса

  $result = checkProducts($link,$result,$userCartItems,$reqLanguage);
  if ($result["error"]){goto endRequest;}
  $products=$result["products"];
  unset($result["products"],$result["productsChecked"]);

  $result = formatUserCart($result,$products,$createdAt,$updatedAt);
  if ($result["error"]){goto endRequest;}
  
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
      if (isset($_GET["cartCount"])){
        unset($result['items']); 
        unset($result['createdAt']); 
        unset($result['updatedAt']); 
        goto endRequest;
      }// Обработка запроса количества товара
      $result['items'] = []; $result['itemsInCart'] = 0;goto endRequest;
      }
  }//Если нет записи в таблице - создаем ответ и завершаем запрос

  $row = mysqli_fetch_array($sqlResult);//парсинг 
  if (isset($_GET["cartCount"])){
    unset($result['items']); 
    unset($result['createdAt']); 
    unset($result['updatedAt']); 
    if (empty($row['items'])){goto endRequest;} //Если поле пустое, завершаем
    $userCartItems = json_decode($row['items'],true); //true возвращает объект как массив
    if (count($userCartItems)===0) {goto endRequest;} // Если список пуст (пустой массив), завершаем
    $result = calculateCartCount($link, $result, $userCartItems);
    goto endRequest;
  }// Обработка запроса количества товара

  //$result['createdAt']= $row['createdAt'];
  //$result['updatedAt'] = $row['updatedAt'];
  $result['createdAt']= is_null($row['createdAt'])?$row['createdAt']:date("Y-m-d H:i:s", $row['createdAt']);
  $result['updatedAt'] = is_null($row['updatedAt'])?$row['updatedAt']:date("Y-m-d H:i:s", $row['updatedAt']);
  if (empty($row['items'])){
    goto endRequest;
  }else{
    $userCartItems = json_decode($row['items'],true); //true возвращает объект как массив
    if (count($userCartItems)===0) {goto endRequest;}
  }
  $result = compileUserCart($link, $result,$userCartItems, $userId,$reqLanguage);
  if ($result['error']){goto endRequest;}

  

} elseif ($method === 'DELETE'){
  include 'scripts/connectDB.php';//Подключение к БД + модуль шифрования + настройки
  include 'scripts/tokensOp.php';//Проверка токена
  include 'scripts/cartOp.php';//Проверка токена

  $result = ['error' => false, 'code' => 200, 'message' => 'Cart cleared'];//Создание массива с ответом Ок

  $db_connect_response = dbConnect(); $link = $db_connect_response['link']; //Подключение к БД
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;
  } else $settings = getSettings($link);//Получение ключа шифрования.

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
  $result = clearUserCart($link, $result, $userId);
  if ($result['error']) goto endRequest; //на всякий случай

} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if (!empty($link)) mysqli_close($link);
http_response_code($result['code']); unset($result['code']);
echo json_encode($result);

 /* Out {
    "items": [
        {
            "product": {
                "id": "6660c46d5bd7273d906cdcc2",
                "name": "Анакампсерос руфесценс Санрайз",
                "url": "anakampseros_rufestsens_sanraiz",
                "image": "1-1.jpg",
                "price": 15
            },
            "quantity": 2
        },
        {
            "product": {
                "id": "6660c46d5bd7273d906cdcbf",
                "name": "Цветущие маммилярии",
                "url": "tsvetushchie_mammilyarii",
                "image": "0-1.jpg",
                "price": 17
            },
            "quantity": 3
        },
        {
            "product": {
                "id": "6660c46d5bd7273d906cdcc0",
                "name": "Пахицереус Прингля",
                "url": "pakhitsereus_pringlya",
                "image": "0-2.jpg",
                "price": 24
            },
            "quantity": 3
        },
        {
            "product": {
                "id": "6660c46d5bd7273d906cdcc1",
                "name": "Эхинокактус Грузона",
                "url": "ekhinokaktus_gruzona",
                "image": "0-3.jpg",
                "price": 24
            },
            "quantity": 1
        }
    ]
}*/