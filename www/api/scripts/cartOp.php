<?php
//Создание корзины пользователю
function createUserCart($link,$result, $userId, $products = NULL){
  include 'variables.php';
  $funcName = 'createUserCart'.'_func';
  if ($result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}

  $createdAt = 'NULL';
  if ($products && is_array($products)){
    $products = json_encode($products);
    $createdAt = time();
  }else{
    $products = 'NULL';
  }

  $sql = "INSERT INTO `carts` (`id`, `user_id`, `items`, `createdAt`, `updatedAt`) VALUES (NULL, '$userId', $products, $createdAt, NULL);";
  try{
  $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['insertReqRejected'] . "($funcName) ($emessage))";goto endFunc;
  }
  
  endFunc:
  return $result;
}

function checkProduct(mysqli $link, array $result, int $productId, int $quantity, $languageTag=''){
  include 'variables.php';
  $funcName = 'checkProduct_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$productId){$result['error']=true; $result['code']=500; $result['message']=$errors['productIdNotFound'] . "($funcName)";}
  if (($quantity<0)){$result['error']=true; $result['code']=500; $result['message']=$errors['quantityNotFound'] . "($funcName)";}

  if ($quantity === 0 ) goto endFunc;//Если удаляем продукт, то не проверяем наличие его в базе
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
    $result['error']=true; $result['code']=400;
    $result['message']=$infoErrors['notEnoughtGoods'] . 'There are ' .$product['count']. " units of this product in stock out of $quantity.";
    goto endFunc;
  }//Товара не достаточно
  $product['quantity']=$quantity;
  $result['product'] = $product;
  endFunc:
  return $result;
}//Проверка наличия товаров в базе и достаточности на складе

function checkProducts(mysqli $link, array $result,array $products,$languageTag=''){
  //Проверяет наличие продуктов в базе и возвращает готовый массив со всеми данными для вывода пользователю
  include 'variables.php';
  $funcName = 'checkProducts_func';
  $notFoundMessage = 'Some products were not found in the database and have been removed!';

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if(empty($products) || !is_array($products) || count($products)===0) {
   $result['error']=true; $result['code']=400; $result['message']=$dataErr['notRecognized'] . "($funcName)"; goto endFunc;
  }
  $messages = [];
  $productIds=[];
  foreach ($products as $product) {$productIds[]=$product["productId"];}

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
  if ($numRows==0){$result['error']=true;$result['code']=400;$result['message']=$errors['productsNotFound'] . "($funcName)";goto endFunc;}
  if ($numRows!==count($products)) $messages["notFound"] = $notFoundMessage;

  $items = $response->fetch_all(MYSQLI_ASSOC);
  $productsChecked=[];
  foreach ($items as &$item){
    if ($result['error']) continue;//если получили ошибку - пропускаем всё остальное
    $itemIndex = array_search($item['id'],array_column($products,"productId"),true);
    if ($itemIndex !== false){
      $item["quantity"] = $products[$itemIndex]["quantity"];
      $productsChecked[]=["productId"=>$item["id"],"quantity"=>$item["quantity"]];
    }else{
      $result['error']=true;$result['code']=500;$result['message']=$dbError['unexpResponse'] . "($funcName)";
    }
  }
  if ($result['error']) goto endFunc;
  if (count($items)<1){$result['error']=true;$result['code']=400;$result['message']=$errors['productsNotFound'] . "($funcName)";goto endFunc;}
  $result["products"]=$items;
  $result["productsChecked"]=$productsChecked;

  endFunc:
  if (count(value: $messages)>0) $result["messages"] = array_values($messages);
  return $result;//Возвращаем массив продуктов и колличества имеющихся в базе айдишников + сообщения обработки
}//Функция проверки массива товаров на наличие в базе перед добавлением в корзину (проверка id)

function compileUserCart($link, $result, $userCartItems, $userId, $languageTag=''){
  include 'scripts/variables.php';
  $funcName = 'compileUserCart_func';
  //В result уже должны быть метки даты создания и изменения
  if ($result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!is_array($userCartItems)){$result['error']=true; $result['message'] = $errors['productNotFound'] . "($funcName)"; goto endFunc;}
  if (count($userCartItems)===0){$result['items'] = []; $result['itemsInCart'] = 0;goto endFunc;}
  $messages=[];
  //Подготовка запроса информации всех товаров из корзины пользователя
  $sqlStr='';//Переменная для создания условия запроса (всё что после WHERE) 
  $j=0;
  $quantities=[];
  $mergedRecords = 0;//кол-во объединенных записей
  foreach($userCartItems as $value){
    $itemID = $value['productId'];
    settype($itemID, 'integer');
    if ($itemID > 0){
      if ($j===0){
        $sqlStr="`id`= $itemID";
      }else{
        $sqlStr=$sqlStr." OR `id`= $itemID";
      }
      if (!empty($quantities[$itemID]) && $quantities[$itemID]>0){
        $mergedRecords++;
      }
      empty($quantities[$itemID])?$quantities[$itemID] = $value['quantity']:$quantities[$itemID] += $value['quantity'];
      $j++;
    }
  }

  $sql = "SELECT `id`,`name$languageTag` AS `name`,`price`,`image`,`url`,`count`,`disabled` 
  FROM `products` 
  WHERE $sqlStr;";

  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->execute(); 
    $response = $stmt->get_result();
    $numRows = $response->num_rows;
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}

  if ($numRows===0){
    if (count($userCartItems)>0){
      $result = updateUserCart($link, $result, $userId, NULL,NULL,NULL);
      if (!$result['error']){
        $result['message'] = 'All products from cart were not found in the database and were removed from the cart.';
      } else {goto endFunc;}
    }
    $result['items'] = []; $result['itemsInCart'] = 0;goto endFunc;
  
  }//Если нет записи в таблице - создаем и завершаем запрос

  $rows = $response->fetch_all(MYSQLI_ASSOC);//парсинг 

  if (count($rows) <> count($userCartItems)){
    //Оптимизация продуктов и их сохранение в корзине
    $newProducts=[];
    foreach($rows as $product){
      $newProducts[]=['quantity'=>$quantities[$product['id']],'productId'=>$product['id']];
    }
    $result = updateUserCart($link, $result, $userId, $newProducts, NULL, time());
    unset($newProducts);
    //Выводим сообщение о причине группировки
    $recordsLost = count($userCartItems) - count($rows) - $mergedRecords;
    if ($recordsLost>0){
      //Если есть не найденные данные, сообщаем о их кол-ве
      $messages[] = 'Some ['. count($userCartItems) - count($rows) .'] products were not found in the database and were removed from the cart.' . "($funcName)";
    } else{
      //Если есть сгруппированные товары, сообщаем о их кол-ве
      $messages[] = "Products were combined $mergedRecords times!";
    }
  }
  $products=[]; $counter = 0; //$quantities - quantities

  foreach($rows as $product){
    $products[]=['quantity'=>$quantities[$product['id']],'product'=>$product];
    $counter = $counter + $quantities[$product['id']];
  }
  $result['items'] = $products; $result['count'] = $counter;
  if (count($messages)>0){$result['messages'] = is_array($result['messages'])? array_merge($result['messages'],$messages):$messages;}

  endFunc:
  return $result;
}//Функция для генерации необходимого списка товаров в корзине

function formatUserCart(array $result, array $products, $createdAt, $updatedAt):array{
  include 'scripts/variables.php';
  $funcName = 'formatUserCart_func';
  if ($result['error']){goto endFunc;}
  if (!is_array($products)){$result['error']=true; $result['message'] = $errors['productNotFound'] . "($funcName)"; goto endFunc;}
  if (count($products)===0){
    $result['items'] = [];
    $result['count'] = 0;
    $result['createdAt']=intval($createdAt);
    $result['updatedAt']=intval($updatedAt);
    goto endFunc;}

  $items=[];
  $totalCount=0;
  foreach ($products as $product) {
    $item['quantity'] = $product['quantity'];
    $totalCount+=$product['quantity'];
    unset($product['quantity']);
    $item['product'] = $product;
    $items[]=$item;
  }
  $result['count'] = $totalCount;
  $result['createdAt']=intval($createdAt);
  $result['updatedAt']=intval($updatedAt);
  $result['items']=$items;
  endFunc:

  return $result;
}

function updateUserCart($link, $result, $userId, $itemList, $createdAt, $updatedAt){
  include 'variables.php';
  $funcName = 'updateUserCart_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}

  //- Filtered - обозначает чистку корзины от не найденных в БД артиклей 
  
  if (is_array($itemList) && count($itemList)>0){
    $itemList = array_values($itemList);
    $itemListSQL = "'".json_encode($itemList)."'";
  }elseif(is_array($itemList) && count($itemList)===0){
    $itemListSQL = 'NULL';
    $createdAt = 'NULL';
    $updatedAt = time();
  }else{
    $itemListSQL = NULL;
  }

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

function calculateCartCount($link, $result, $userCartItems){
  include 'variables.php';
  $funcName = 'calculateCartCount_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
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
  if (!$userId) {$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}
  settype($userId,"integer");
  if (empty($userId) ||$userId<1){$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}

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
  if ($numRows > 1){$result['error']=true; $result['code']=500; $result['message']=$dbError['multipleRecords'] . "($funcName)";}
  $userCart = $response->fetch_assoc();//парсинг
  if (empty($userCart['items'])){$result['userCart'] = ["items"=>[],"createdAt"=> $userCart["createdAt"],"updatedAt"=> $userCart["updatedAt"]];goto endFunc;} //Если поле пустое, завершаем
  $userCartItems = json_decode($userCart['items'],true); //true возвращает объект как массив
  
  foreach ($userCartItems as $cartItem) {
    if (empty($cartItem['quantity']) || empty($cartItem['productId'])) unset($cartItem);
  }//ремонт массива в случае проблем с данными. Просто удаляем поврежденные записи
  $result['userCart'] = ["items"=>array_values($userCartItems),"createdAt"=> $userCart["createdAt"],"updatedAt"=> $userCart["updatedAt"]];

  endFunc:
  return $result;
}//Получение данных корзины пользователя

function clearUserCart($link, $result, $userId){
  include 'variables.php';
  $funcName = 'clearUserCart'.'_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}
  
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
