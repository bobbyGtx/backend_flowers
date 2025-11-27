<?php

function checkUserCart($result, $link, $userId){
  include 'variables.php';
  $funcName = 'checkUserCart_func';

  if ($result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  settype($userId,"integer");
  if (!$userId || $userId===0) {$result['error']=true; $result['code']=500; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql = "SELECT `id`,`items`,`user_id` FROM `carts` WHERE `user_id`=?";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->bind_param('i', $userId);
    $stmt->execute(); 
    $response = $stmt->get_result();
    $numRows = $response->num_rows;
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}

  if ($numRows === 0){
    $result['noCart']=true;
  }else{
    $result['error']=true; $result['code']=400; $result['message'] = $errors['cartRebaseImpossible']; goto endFunc;
  }
 
  endFunc:
  return $result;
}//Проверка наличия корзины пользователя перед cartRebase

function createUserCart($link, array $result, int $userId,array|null $products = null){
  //Корзина создается при запросе корзины или при перебазировании. По наличию записи корзиныопределяется перебазирование
  include 'variables.php';
  $funcName = 'createUserCart'.'_func';
  if ($result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['code']=500; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}

  $createdAt = 'NULL';
  if ($products && is_array($products)){
    $productsStr = "'".json_encode($products)."'";
    $createdAt = time();
  }else{
    $productsStr = 'NULL';
  }

  $sql = "INSERT INTO `carts` (`id`, `user_id`, `items`, `createdAt`, `updatedAt`) VALUES (NULL, '$userId', $productsStr, $createdAt, NULL);";
  try{
  $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['insertReqRejected'] . "($funcName) ($emessage))";goto endFunc;
  }
  
  endFunc:
  return $result;
}//Создание корзины пользователю. При первом запросе корзины или при RebaseCart (post)

function checkProduct(mysqli $link, array $result, int $productId, int $quantity, $languageTag=''){
  include 'variables.php';
  $funcName = 'checkProduct_func';
  $messages=[];
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$productId){$result['error']=true; $result['code']=500; $result['message']=$errors['productIdNotFound'] . "($funcName)";}
  if (($quantity<0)){$result['error']=true; $result['code']=500; $result['message']=$errors['quantityNotFound'] . "($funcName)";}
  
  //Если удаляем продукт, то не проверяем наличие его в базе
  if ($quantity === 0 ) {$result['product']=["id"=>$productId,"quantity"=>0];goto endFunc;}
  //Делаем запрос всех товаров из списка
  $sql = "SELECT `id`,`name$languageTag` AS `name`,`price`,`image`,`url`,`count`,`disabled`FROM `products` WHERE `id` = $productId;";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->execute(); 
    $response = $stmt->get_result();
    $numRows = $response->num_rows;
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}
  if ($response->num_rows===0){
    $result['error']=true; $result['code']=400; $result['message']=$errors['productNotFound'] . "id=[$productId]"; goto endFunc;
  }//Продукт не найден в таблице продуктов
  
  $product = $response->fetch_assoc();//парсинг
  if (intval($product['id'])<>$productId){
    $result['error']=true; $result['code']=500;$result['message']=$dbError['unexpResponse'] . "($funcName)"; goto endFunc;
  }//В ответе идентификатор не найден или отличается от запрошенного

  if ($product['disabled']){
    $result['error']=true; $result['code']=400;$result['message']=$infoErrors['productNotAvailable'];
    goto endFunc;
  }//Товар не доступен

  if ((intval($product['count']) - $quantity)<0){
    $messages[]=$infoErrors['notEnoughtGoods'] . ' There are ' .$product['count']. " units of this product in stock out of $quantity.";
  }//Предупреждение: Товара не достаточно
  $product['quantity']=$quantity;
  $result['product'] = $product;
  endFunc:
  if (count($messages)>0) $result['messages'] = array_values($mesages);
  return $result;
}//Проверка наличия товаров в базе и достаточности на складе

function checkProducts(mysqli $link, array $result,array $products,$languageTag=''){
  //Проверяет наличие продуктов в базе и возвращает готовый массив со всеми данными для вывода пользователю
  include 'variables.php';
  $funcName = 'checkProducts_func';
  $messages=[];

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if(empty($products) || !is_array($products) || count($products)===0) {
   $result['error']=true; $result['code']=400; $result['message']=$dataErr['notRecognized'] . "($funcName)"; goto endFunc;
  }
  $productIds=[];
  foreach ($products as $product) {$productIds[]=$product["id"];}

  //Динамически создаем плейсхолдеры (?, ?, ?, ?)
  $placeholders = implode(',', array_fill(0, count($products), '?'));
  //Определяем типы параметров (все ID — целые числа) iiiii...
  $types = str_repeat('i', count($products));
  //Формируем запрос
  $sql = "SELECT `id`,`name$languageTag` AS `name`,`price`,`image`,`url`,`count`,`disabled`
  FROM `products`  
  WHERE id IN ($placeholders)
  ORDER BY `id` ASC";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->bind_param($types, ...$productIds);
    $stmt->execute(); 
    $response = $stmt->get_result();
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}
  $numRows = $response->num_rows;
  if ($numRows==0){$result['error']=true;$result['message']=$errors['productsNotFound']; $result['cartAction']='clear'; goto endFunc;}
  if ($numRows!==count($products)){$messages[] = $infoErrors['someProductsRemoved']; $result['cartAction']='fix';}

  $items = $response->fetch_all(MYSQLI_ASSOC);
  $productsChecked=[];
  foreach ($items as &$item){
    if ($result['error']) continue;//если получили ошибку - пропускаем всё остальное
    $itemIndex = array_search($item['id'],array_column($products,"id"),true);
    if ($itemIndex !== false){
      $item["quantity"] = $products[$itemIndex]["quantity"];
      $productsChecked[]=["id"=>$item["id"],"quantity"=>$item["quantity"]];
      if ($item["quantity"] > $item["count"]) $messages['notEnoughtGoods'] = $infoErrors['notEnoughtGoods'];
      if ($item['disabled'])$messages['notAvailable'] = $infoErrors['productNotAvailable'];
    }else{
      $result['error']=true;$result['code']=500;$result['message']=$dbError['unexpResponse'] . "($funcName)";
    }
  }
  if ($result['error']) goto endFunc;
  if (count($items)<1){$result['error']=true;$result['code']=400;$result['message']=$errors['productsNotFound'] . "($funcName)";goto endFunc;}
  $result["products"]=$items;
  $result["productsChecked"]=$productsChecked;

  endFunc:
  if (count($messages)>0) $result['messages']=array_values($messages);
  return $result;//Возвращаем массив продуктов и колличества имеющихся в базе айдишников + сообщения обработки
}//Функция проверки массива товаров на наличие в базе перед добавлением в корзину (проверка id)

function formatUserCart(array $result, array $products, $createdAt, $updatedAt):array{
  include 'scripts/variables.php';
  $funcName = 'formatUserCart_func';
  if ($result['error']){goto endFunc;}

  if (!is_array($products)){$result['error']=true; $result['code']=400;$result['message'] = $errors['productNotFound'] . "($funcName)"; goto endFunc;}
  if (count($products)===0){
    $result['cart']=["count"=>0,"createdAt"=>intval($createdAt),"updatedAt"=>intval($updatedAt), "items"=>[]];
    goto endFunc;
  }

  $items=[];
  $totalCount=0;
  $totalAmount = 0;
  foreach ($products as $product) {
    if ($product['disabled']===0){
      $item['quantity'] = $product['quantity'];
      $totalCount+=$product['quantity'];
      $totalAmount+=$product['quantity']*$product['price'];
    }else{
      $item['quantity']=0;
    }
    unset($product['quantity']);
    $item['product'] = $product;
    $items[]=$item;
  }
  $result['cart']=["count"=>$totalCount,"amount"=>$totalAmount,"createdAt"=>intval($createdAt),"updatedAt"=>intval($updatedAt), "items"=>$items];
  endFunc:

  return $result;
}

function updateUserCart($link, $result, $userId, $itemList, $createdAt, $updatedAt){
  include 'variables.php';
  $funcName = 'updateUserCart_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['code']=500; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}

  //- Filtered - обозначает чистку корзины от не найденных в БД артиклей 
  
  if (is_array($itemList) && count($itemList)>0){
    $itemList = array_values($itemList);
    $itemListSQL = "'".json_encode($itemList)."'";
  }elseif(is_array($itemList) && count($itemList)===0){
    $itemListSQL = 'NULL';
    $createdAt = 'NULL';
    $updatedAt = time();
  }else{
    $itemListSQL = 'NULL';
  }
  if ($createdAt===0 && $updatedAt===0) $createdAt = time();
  if (empty($createdAt) || is_null($createdAt)){
    $createdAt=', createdAt=NULL';
  }else $createdAt = ", createdAt= $createdAt";
  if (empty($updatedAt) || is_null($updatedAt)){
    $updatedAt=', updatedAt=NULL';
  }else $updatedAt = ", updatedAt= $updatedAt";

  //сохранение корзины
  $sql = "UPDATE carts SET items = $itemListSQL$createdAt$updatedAt WHERE user_id = $userId;";
  //$result['sql']=$sql;
  try{
  $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['updReqRejected'] . "($funcName) ($emessage))";goto endFunc;
  }

  endFunc:
  return $result;
}

function calculateCartCount($result, $userCartItems){
  include 'variables.php';
  $funcName = 'calculateCartCount_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!is_array($userCartItems)){$result['error']=true; $result['message'] = $dataErr['dataInFunc'] . "($funcName)"; goto endFunc;}
  if (count($userCartItems)<1){$result['count'] = 0; goto endFunc;}
  $counter=0;
  foreach($userCartItems as $item){
    $counter = $counter + intval($item['quantity']);
  }
  $result['count'] = $counter;
  endFunc:
  return $result;
}
function getCart($link, $result, $userId){
  include 'variables.php';
  $funcName = 'getCart_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['code']=500; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}
  settype($userId,"integer");
  if (empty($userId) ||$userId<1){$result['error']=true; $result['code']=500; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql= "SELECT `id`,`user_id`,`items`,`createdAt`,`updatedAt` FROM `carts` WHERE `user_id` = $userId;";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->execute(); 
    $response = $stmt->get_result();
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}
  $numRows = $response->num_rows;
  if ($numRows === 0){
    $result = createUserCart($link,$result,$userId,null);
    $result['userCart'] = ["items"=>[],"createdAt"=> 0,"updatedAt"=> 0];goto endFunc;
  }//Создаем запись корзины для пользователя
  $userCart = $response->fetch_assoc();//парсинг
  if (empty($userCart['items'])){$result['userCart'] = ["items"=>[],"createdAt"=> intval($userCart["createdAt"]),"updatedAt"=> intval($userCart["updatedAt"])];goto endFunc;} //Если поле пустое, завершаем
  $userCartItems = json_decode($userCart['items'],true); //true возвращает объект как массив
  
  foreach ($userCartItems as $cartItem) {
    if (empty($cartItem['quantity']) || empty($cartItem['id'])) unset($cartItem);
  }//ремонт массива в случае проблем с данными. Просто удаляем поврежденные записи
  $result['userCart'] = ["items"=>array_values($userCartItems),"createdAt"=> intval($userCart["createdAt"]),"updatedAt"=> intval($userCart["updatedAt"])];

  endFunc:
  return $result;
}//Получение данных корзины пользователя

function clearUserCart($link, $result, $userId){
  include 'variables.php';
  $funcName = 'clearUserCart'.'_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['code']=500; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}
  
  $updatedAt=time();//Добавление временой метки
  $sql = "UPDATE `carts` SET `items`=NULL,`updatedAt`= $updatedAt ,`createdAt`= NULL WHERE `user_id` = $userId;";

  try{
  $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['delReqRejected'] . "($funcName) ($emessage))";goto endFunc;
  }
  
  endFunc:
  return $result;
}
