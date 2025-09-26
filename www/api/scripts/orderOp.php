<?php
function cartToOrder($link, $result, $userCartItems, $lng=''){
  include 'variables.php';
  $funcName = 'cartToOrder_func';

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
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
      $result['error'] = true;$messages[]="Not enough product (".$product['name'.$language[$lng]].") in stock.";
    }
    $updatesProducts[intval($product['id'])]=($quantityInStock - $quantity);//подготавливаем массив для изменения кол-ва товара на складе и возвращаем его
    $totalProductPrice = $quantity*intval($product['price']);
    $totalPrice += $totalProductPrice;
    $item = ['id' => $product['id'],'name'=>$product['name'.$language[$lng]],'quantity'=>$quantities[$product['id']],'price'=>$product['price'],'total'=>$totalProductPrice];
    $products[]=$item;
    $counter += $quantities[$product['id']];
  }
  
  if ($result['error'] && count($messages)>0){
    $result['code'] = 406; $result['message'] = $infoErrors['notEnoughtGoods']; goto endFunc;
  }//Если найдены нестыковки по кол-ву товаров - выходим
  $products['productsPrice']=$totalPrice;//Помещаем в ответ чистую рассчитанную стоимость товаров 
  $result['products'] = array_values($products); 
  $result['updatesProducts'] = $updatesProducts;
  if (!empty($priorityMsg)){$result['message'] = $priorityMsg;}

  endFunc:
  return $result;
}

//Перенести в продуктс ОП если он будет
function updateProductsCounts($link, $result, $updatesProducts){
  include 'scripts/variables.php';
  $funcName = 'updateProductsCounts_func';

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  
  if (!is_array($updatesProducts)|| count($updatesProducts)===0){
    $result['error'] = true; $result['code'] = 500; $result['message'] = $dataErr['dataInFunc'] . "($funcName)"; goto endFunc;
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
}//Обновление кол-ва продуктов в магазине исходя из заказа клиента

function compileOrderData($incOrder, $selectedDelivery, $address, $products, $userId){
  include 'scripts/variables.php';
  $order=[];
  $deliveryCost = intval($selectedDelivery['lPMinPrice'])<=intval($products['productsPrice'])?intval($selectedDelivery['low_price']):intval($selectedDelivery['delivery_price']);
  $order['deliveryCost'] = $deliveryCost;
  $order['deliveryType_id'] =$incOrder['deliveryTypeId'];
  $order['delivery_info'] = $address;// Адрес доставки. Для БД нужно кодировать json_encode($address,JSON_UNESCAPED_UNICODE)
  $order['firstName'] =$incOrder['firstName']; 
  $order['lastName'] =$incOrder['lastName']; 
  $order['paymentType_id'] =$incOrder['paymentTypeId']; 
  $order['phone'] =$incOrder['phone']; 
  $order['email'] =$incOrder['email']; 
  $order['comment'] = $incOrder['comment'];
  $order['status_id'] = $startOrderStatus;
  $order['items'] = $products;//товары. для БД нужно кодировать json_encode($orderProducts,JSON_UNESCAPED_UNICODE)
  $order['user_id'] = $userId;
  $order['totalAmount'] = $deliveryCost + intval($products['productsPrice']);
  $order['createdAt'] =time();

  return $order;
}//Подготовка переменной заказа для добавления в базу и для ответа

function createOrder($link, $result, $order){
  include 'scripts/variables.php';
  $funcName = 'createOrder_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
 
  if (empty($order) || !is_array($order) || count($order)<1){
    $result['error']=true; $result['code']=500; $result['message'] = $dataErr['dataInFunc'] . "($funcName)";
    goto endFunc;
  }

  if (!empty($order['items']) && is_array($order['items']) && count($order['items'])>0){
    //Преобразуем в строку json для сохранения в БД
    $order['items'] = json_encode($order['items'],JSON_UNESCAPED_UNICODE);
  } else {
    $result['error']=true; $result['code']=500; $result['message'] = $errors['productsNotFound'] . "($funcName)";goto endFunc;
  }//Если нет товаров для добавления, выходим с ошибкой
  
  if (!empty($order['delivery_info']) && is_array($order['delivery_info']) && count($order['delivery_info'])>0){
    $order['delivery_info'] = json_encode($order['delivery_info'],JSON_UNESCAPED_UNICODE);
  }//Преобразуем в строку json для сохранения в БД



  endFunc:
  return $result;
}//добавление заказа в таблицу