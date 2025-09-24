<?php
//Создание корзины пользователю
function createUserCart($link,$result, $userId){
  include 'scripts/variables.php';
  $funcName = 'createUserCart_func';
  if ($result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['message'] = $errors['userIdNotFound'] . "($funcName)"; goto endFunc;}

$sql = "INSERT INTO `carts` (`id`, `user_id`, `items`, `createdAt`, `updatedAt`) VALUES (NULL, '$userId', NULL, NULL, NULL);";
  try{
  $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']="Insert request rejected by database. (UserOp->createUserCart) ($emessage))";goto endFunc;
  }
  
  endFunc:
  return $result;
}

function compileUserCart($link, $result, $userCartItems, $userId ){
  include 'scripts/variables.php';
  $funcName = 'compileUserCart_func';
  //В result уже должны быть метки даты создания и изменения
  if ($result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['message'] = $errors['dbConnectInterrupt'] . "($compileUserCart_func)"; goto endFunc;}
  if (!is_array($userCartItems)){$result['error']=true; $result['message'] = "Products not found ($funcName)"; goto endFunc;}
  if (count($userCartItems)===0){$result['items'] = []; $result['itemsInCart'] = 0;goto endFunc;}

  //Подготовка запроса информации всех товаров из корзины пользователя
  $sqlStr='';//Переменная для создания условия запроса (всё что после WHERE) 
  $j=0;
  $quantities=[];
  foreach($userCartItems as $value){
    $itemID = $value['productId'];
    settype($itemID, 'integer');
    if (preg_match('/^[0-9]+$/', $itemID)){
      if ($j===0){
        $sqlStr="`id`= $itemID";
      }else{
        $sqlStr=$sqlStr." OR `id`= $itemID";
      }
      $quantities[$itemID] = $value['quantity'];
      $j++;
    }
  }

  $sql = "SELECT `id`,`name`,`price`,`image`,`url` FROM `products` WHERE $sqlStr;";
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
        $result = updateUserCart($link, $result, $userId, NULL);
        if (!$result['error']){
          $result['message'] = 'All products from cart were not found in the database and were removed from the cart.';
        } else {goto endFunc;}
      }
      $result['items'] = []; $result['itemsInCart'] = 0;goto endFunc;
    }
  }//Если нет записи в таблице - создаем и завершаем запрос

  $rows = mysqli_fetch_all($sqlResult,MYSQLI_ASSOC);//парсинг 

  if (count($rows) <> count($userCartItems)){
    $priorityMsg = 'Some ['. count($userCartItems) - count($rows) .'] products were not found in the database and were removed from the cart.';
    $newProducts=[];
    foreach($rows as $product){
      $newProducts[]=['quantity'=>$quantities[$product['id']],'productId'=>$product['id']];
    }
    $result = updateUserCart($link, $result, $userId, $newProducts);
    unset($newProducts);
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

function updateUserCart($link, $result, $userId, $itemList, $createdAt = null, $updatedAt = null){
  include 'scripts/variables.php';
  $funcName = 'updateUserCart_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$userId) {$result['error']=true; $result['message'] = "User ID not found! ($funcName)"; goto endFunc;}
  //- Filtered - обозначает чистку корзины от не найденных в БД артиклей 
  
  if (empty($itemList) || !is_array($itemList)){
    $itemListSQL = 'NULL';
    $createdAt = 'NULL';
    $updatedAt = time();
  }else{
    $itemListSQL = json_encode($itemList);
    $updatedAt = time();
  }

  if (!empty($createdAt)){$createdAt = ",`createdAt`= $createdAt";}
  if (!empty($updatedAt)){$updatedAt = ",`createdAt`= $updatedAt";}
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
  include 'scripts/variables.php';
  $funcName = 'calculateCartCount_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!is_array($userCartItems)){$result['error']=true; $result['message'] = $errors['productsNotFound'] . "($funcName)"; goto endFunc;}
  if (count($userCartItems)<1){$result['count'] = 0; goto endFunc;}
  $counter=0;
  foreach($userCartItems as $item){
    $counter = $counter + intval($item['quantity']);
  }
  $result['count'] = $counter;
  endFunc:
  return $result;
}
function cartToOrder($link, $result, $userCartItems, $lng=''){
  include 'scripts/variables.php';
  $funcName = 'cartToOrder_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!is_array($userCartItems)){$result['error']=true; $result['message'] = $errors['productsNotFound'] . "($funcName)"; goto endFunc;}
  if (count($userCartItems)<1){$result['count'] = 0; goto endFunc;}

    //Подготовка запроса информации всех товаров из корзины пользователя
  $sqlStr='';//Переменная для создания условия запроса (всё что после WHERE) 
  $j=0;
  $quantities=[];
  foreach($userCartItems as $value){
    $itemID = $value['productId'];
    settype($itemID, 'integer');
    if (preg_match('/^[0-9]+$/', $itemID)){
      if ($j===0){
        $sqlStr="`id`= $itemID";
      }else{
        $sqlStr=$sqlStr." OR `id`= $itemID";
      }
      $quantities[$itemID] = $value['quantity'];
      $j++;
    }
  }

  $sql = "SELECT `id`,`name`,`name_en`,`name_de`,`price`,`count`,`disabled` FROM `products` WHERE $sqlStr;";
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
        $result = updateUserCart($link, $result, $userId, NULL);
        if (!$result['error']){
          $result['message'] = 'All products from cart were not found in the database and were removed from the cart.';
        } else {goto endFunc;}
      }
      $result['items'] = []; $result['itemsInCart'] = 0;goto endFunc;
    }
  }//Если нет записи в таблице - создаем и завершаем запрос

  $rows = mysqli_fetch_all($sqlResult,MYSQLI_ASSOC);//парсинг 

  if (count($rows) <> count($userCartItems)){
    $priorityMsg = 'Some ['. count($userCartItems) - count($rows) .'] products were not found in the database and were removed from the cart.';
    $newProducts=[];
    foreach($rows as $product){
      $newProducts[]=['quantity'=>$quantities[$product['id']],'productId'=>$product['id']];
    }
    $result = updateUserCart($link, $result, $userId, $newProducts);
    unset($newProducts);
  }
  $products=[]; 
  $totalPrice = 0; //$quantities - quantities
  $messages = [];
  $updatesProducts=[];//Массив для изменения остатка заказанных товаров в БД

  //Проверка товаров.
  foreach($rows as $product){
    $quantity = intval($quantities[$product['id']]);//заказанное кол-во
    $quantityInStock = intval($product['count']);//доступно на складе
    if (($quantityInStock - $quantity)<0){
      $result['error'] = true;$messages[]="not enough product (".$product['name'.$language[$lng]].") in stock.";
    }
    $updatesProducts[intval($product['id'])]=($quantityInStock - $quantity);//подготавливаем массив для изменения кол-ва товара на складе и возвращаем его
    $totalProductPrice = $quantity*intval($product['price']);
    $totalPrice += $totalProductPrice;
    $item = ['id' => $product['id'],'name'=>$product['name'.$language[$lng]],'quantity'=>$quantities[$product['id']],'price'=>$product['price'],'total'=>$totalProductPrice];
    $products[]=$item;
    $counter += $quantities[$product['id']];
  }
  if ($result['error'] && count($messages)>0){
    $result['message'] = 'Not enough goods in stock';$result['code'] = 406;$result['messages'] = $messages;goto endFunc;
  }//Если найдены нестыковки по кол-ву товаров - выходим
  $result['products'] = $products; $result['productsPrice'] = $totalPrice;
  $result['updatesProducts'] = $updatesProducts;
  if (!empty($priorityMsg)){$result['message'] = $priorityMsg;}

  endFunc:
  return $result;
}

function updateProductsCounts($link, $result, $updatesProducts){
  include 'scripts/variables.php';
  $funcName = 'updateProductsCounts_func';

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  
  if (!is_array($updatesProducts)|| count($updatesProducts)===0){
    $result['error'] = true; $result['code'] = 501; $result['message'] = "The array of changes to the number of products was not found. ($funcName)"; goto endFunc;
  }

  // Формируем часть CASE WHEN для запроса
  $caseSql = '';
  $ids = [];
  foreach ($updatesProducts as $id => $newCount) {
    $caseSql .= "WHEN {$id} THEN {$newCount} ";
    $ids[] = $id;
  }
  // Превращаем массив id в строку для WHERE
  $idsSql = implode(',', $ids);
  

  // Финальный запрос
  $sql = "UPDATE products SET count = CASE id $caseSql END WHERE id IN ($idsSql)";
  try {
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['updReqRejected']."($funcName)($emessage))";
    goto endFunc;
  } 
  if (empty(mysqli_affected_rows($link))||mysqli_affected_rows($link)<1){
    $result['error']=true; $result['code']=500; $result['message']="error changing goods in the warehouse! ($funcName)";
    goto endFunc;
  }
  if (mysqli_affected_rows($link)<>count($updatesProducts)){
    $result['error']=true; $result['code']=500; $result['message']="Critical error! Failed to update all records in the database! ($funcName)";
    goto endFunc;
  }//Тут бы откатить изменения. Изменено строк меньше чем запросили на входе в функцию

  endFunc:
  return $result;
}