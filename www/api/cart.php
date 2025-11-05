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
  $result = ['error' => false, 'code' => 200, 'message' => $infoMessages['reqSuccess']];//Создание массива с ответом Ок
  $db_connect_response = dbConnect(); $link = $db_connect_response['link'];//Подключение к БД
  
  if ($db_connect_response['error'] == true || !$link) {
    $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . $db_connect_response['message']; goto endRequest;
  }
  $result = checkToken($link, $result, getallheaders(),true);
  if ($result['error']) {goto endRequest;}
  if ($result['userId'] && $result['userPassword']){$userId = $result['userId'];$userPwd = $result['userPassword'];unset($result['userId'],$result['userPassword']); }
 
  $result = getCart($link,$result,$userId);
  if ($result['error']) {goto endRequest;}
  $products = $result['userCart']['items'];
  $createdAt = $result['userCart']['createdAt'];
  $updatedAt = $result['userCart']['updatedAt'];
  unset($result['userCart']);

  if (isset($_GET["cartCount"]) && boolval($_GET["cartCount"]) === true){
    $result = calculateCartCount($result, $products);
    if ($result['error']) {goto endRequest;}
    goto endRequest;
  }

  if (count($products)===0){
    $result = formatUserCart($result,$product,$createdAt,$updatedAt);
    goto endRequest;
  }

  $result = checkProducts($link, $result, $products, $reqLanguage);
  if ($result['error']) {
    if (isset($result['cartAction']) && $result['cartAction']==='clear'){
      $result['error']=false;$result['code']=200; unset($result['messages'],$result['cartAction']);
      $result = clearUserCart($link,$result,$userId);
      $result = formatUserCart($result,[],null,time());
      $result['error']=true; $result['code']=200;$result['message']=$infoErrors['cartClearedBySystem'];
    }
    goto endRequest;
  }//Чистим корзину если найдены неизвестные товары и выводим результат с ошибкой
  
  if (isset($result['cartAction']) && $result['cartAction'] ==='fix'){
    $productsList = $result["productsChecked"]; unset($result["productsChecked"],$result['cartAction']);
    $checkedProductsList = $result["products"];unset($result["products"]);
    if (count($productsList)>0 && count($productsList)<count($products)){
      $result = updateUserCart($link,$result,$userId,$productsList,$createdAt,time());
      $result = formatUserCart($result,$checkedProductsList,$createdAt,time());
      $result['error']=true; $result['code']=200;$result['message']=$infoErrors['cartClearedBySystem'];
      goto endRequest;
    }//доп проверка проблем в корзине
  }//в корзине найдены неопознанные товары. Корзина будет перезаписана только известными

  $checkedProductsList = $result["products"];unset($result["products"],$result["productsChecked"]);

  $result = formatUserCart($result, $checkedProductsList,$createdAt, $updatedAt);
  if ($result['error']) {goto endRequest;}

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
  $result = formatUserCart($result,[],null,time());
  if ($result['error']) goto endRequest; //на всякий случай
} else {
  $result['error']=true; $result['code'] = 405; $result['message'] = $errors['MethodNotAllowed'];
}

endRequest:
if (!empty($link)) mysqli_close($link);
if (isset($result['cartAction'])) unset($result['cartAction']);//удаление внутреннего флага
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