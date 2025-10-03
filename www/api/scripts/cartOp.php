<?php
//Создание корзины пользователю
function createUserCart($link,$result, $userId, $products = NULL){
  include 'variables.php';
  $funcName = 'createUserCart_func';
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

  $sql = "INSERT INTO `carts` (`id`, `user_id`, `items`, `createdAt`, `updatedAt`) VALUES (NULL, '$userId', '$products', $createdAt, NULL);";
  $result['sql']= $sql;
  try{
  $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']="Insert request rejected by database. (UserOp->createUserCart) ($emessage))";goto endFunc;
  }
  
  endFunc:
  return $result;
}

function checkProduct(mysqli $link, array $result, int $productId, int $quantity){
  include 'variables.php';
  $funcName = 'checkProducts_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$productId){$result['error']=true; $result['code']=500; $result['message']=$errors['productIdNotFound'] . "($funcName)";}
  if (!$quantity){$result['error']=true; $result['code']=500; $result['message']=$errors['productIdNotFound'] . "($funcName)";}

  //Делаем запрос всех товаров из списка
  $sql = "SELECT `id`,`count`,`disabled` FROM `products` WHERE `id` = $productId;";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected']."($funcName)($emessage))";
    goto endFunc;
  }

  if (mysqli_num_rows($sqlResult)===0){
    $result['error']=true; $result['code']=500; $result['message']=$errors['productNotFound'] . "id=[$productId]"; goto endFunc;
  }//Продукт не найден в таблице продуктов

  $row = mysqli_fetch_array($sqlResult,MYSQLI_ASSOC);//парсинг
  if (intval($row['id'])<>$productId){
    $result['error']=true; $result['code']=500;$result['message']=$dbError['unexpResponse'] . "($funcName)"; goto endFunc;
  }//В ответе идентификатор не найден или отличается от запрошенного

  if ($row['disabled']){
    $result['error']=true; $result['code']=400;
    $result['message']=$infoErrors['productNotAvailable'];
    goto endFunc;
  }//Товар не доступен
  if ((intval($row['count']) - $quantity)<0){
    $result['error']=true; $result['code']=400;
    $result['message']=$infoErrors['notEnoughtGoods'] . 'There are ' .$row['count']. " units of this product in stock out of $quantity.";
    goto endFunc;
  }//Товара не достаточно
  endFunc:
  return $result;
}//Проверка наличия товаров в базе и достаточности на складе

function compileUserCart($link, $result, $userCartItems, $userId, $languageTag=''){
  include 'scripts/variables.php';
  $funcName = 'compileUserCart_func';
  //В result уже должны быть метки даты создания и изменения
  if ($result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!is_array($userCartItems)){$result['error']=true; $result['message'] = $errors['productNotFound'] . "($funcName)"; goto endFunc;}
  if (count($userCartItems)===0){$result['items'] = []; $result['itemsInCart'] = 0;goto endFunc;}

  //Подготовка запроса информации всех товаров из корзины пользователя
  $sqlStr='';//Переменная для создания условия запроса (всё что после WHERE) 
  $j=0;
  $quantities=[];
  $mergedRecords = 0;//кол-во объединенных записей
  foreach($userCartItems as $value){
    $itemID = $value['productId'];
    settype($itemID, 'integer');
    if (preg_match('/^[0-9]+$/', $itemID)){
      if ($j===0){
        $sqlStr="`id`= $itemID";
      }else{
        $sqlStr=$sqlStr." OR `id`= $itemID";
      }
      if (!empty($quantities[$itemID]) && $quantities[$itemID]>0){
        $mergedRecords++;
      }
      $quantities[$itemID] += $value['quantity'];
      $j++;
    }
  }

  $sql = "SELECT `id`,`name$languageTag` AS `name`,`price`,`image`,`url` FROM `products` WHERE $sqlStr;";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected']."($funcName)($emessage))";
    goto endFunc;
  }

  if (mysqli_num_rows($sqlResult)===0){
    if ($result['error']){goto endFunc;}
    else{
      if (count($userCartItems)>0){
        $result = updateUserCart($link, $result, $userId, NULL,NULL,NULL);
        if (!$result['error']){
          $result['message'] = 'All products from cart were not found in the database and were removed from the cart.';
        } else {goto endFunc;}
      }
      $result['items'] = []; $result['itemsInCart'] = 0;goto endFunc;
    }
  }//Если нет записи в таблице - создаем и завершаем запрос

  $rows = mysqli_fetch_all($sqlResult,MYSQLI_ASSOC);//парсинг 

  if (count($rows) <> count($userCartItems)){
    //Оптимизация продуктов и их сохранение в карзине
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
      $priorityMsg = 'Some ['. count($userCartItems) - count($rows) .'] products were not found in the database and were removed from the cart.' . "($funcName)";
    } else{
      //Если есть сгруппированные товары, сообщаем о их кол-ве
      $priorityMsg = "Products were combined $mergedRecords times!";
    }
  }
  $products=[]; $counter = 0; //$quantities - quantities

  foreach($rows as $product){
    $products[]=['quantity'=>$quantities[$product['id']],'product'=>$product];
    $counter = $counter + $quantities[$product['id']];
  }
  $result['items'] = $products; $result['count'] = $counter;
  if (!empty($priorityMsg)){$result['message'] = $priorityMsg;}

  endFunc:
  return $result;
}//Функция для генерации удобного списка товаров в корзине

function updateUserCart($link, $result, $userId, $itemList, $createdAt, $updatedAt){
  include 'variables.php';
  $funcName = 'updateUserCart_func';
  //$result['itemList'] = $itemList;
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}

  //- Filtered - обозначает чистку корзины от не найденных в БД артиклей 
  
  if (empty($itemList) || !is_array($itemList)){
    $itemListSQL = 'NULL';
  }else{
    $itemListSQL = json_encode($itemList);
  }
  if (intval($createdAt)){$createdAt = ",`createdAt`= $createdAt";} else {unset($createdAt);}
  if (intval($updatedAt)){$updatedAt = ",`updatedAt`= $updatedAt";} else {unset($updatedAt);}
  //сохранение корзины
  $sql = "UPDATE `carts` SET `items`='$itemListSQL' $createdAt $updatedAt WHERE `user_id` = $userId;";
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

  $sql= "SELECT `id`,`user_id`,`items`,`createdAt`,`updatedAt` FROM `carts` WHERE `user_id` = $userId;";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName) ($emessage))";goto endFunc;
  }
  if (mysqli_num_rows($sqlResult) <> 1){$result['error']=true; $result['code']=500; $result['message']=$dbError['cartNotFound'] . "($funcName)";}
  $userCart = mysqli_fetch_array($sqlResult);//парсинг
  
  if (empty($userCart['items'])){
    $result['error']=true; $result['code']=500; $result['message']=$errors['cartEmpty'] . "($funcName)";goto endFunc;
  } //Если поле пустое, завершаем
  $userCartItems = json_decode($userCart['items'],true); //true возвращает объект как массив
  if (count($userCartItems)===0) {
    $result['error']=true; $result['code']=500; $result['message']=$errors['cartEmpty'] . "($funcName)";goto endFunc;
  } // Если список пуст (пустой массив), завершаем
  $result['userCartItems'] = $userCartItems;

  endFunc:
  return $result;
}//Получение данных корзины пользователя

function clearUserCart($link, $result, $userId){
  include 'variables.php';
  $funcName = 'clearUserCart_func';
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
