<?php

function cartToOrder($link, $result, $userCartItems, $languageTag = ''){
  include 'variables.php';
  $funcName = 'cartToOrder_func';

  if (empty($result) || $result['error']) {
    goto endFunc;
  }
  if (!$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";
    goto endFunc;
  }
  if (!is_array($userCartItems)) {
    $result['error'] = true;
    $result['message'] = $errors['productsNotFound'] . "($funcName)";
    goto endFunc;
  }
  if (count($userCartItems) < 1) {
    $result['count'] = 0;
    goto endFunc;
  }

  //Подготовка запроса информации всех товаров из корзины пользователя
  $sqlStr = '';//Переменная для создания условия запроса (всё что после WHERE) 
  $j = 0;
  $quantities = [];
  foreach ($userCartItems as $value) {
    $itemID = $value['productId'];
    settype($itemID, 'integer');
    if (preg_match('/^[0-9]+$/', $itemID)) {
      if ($j === 0) {
        $sqlStr = "`id`= $itemID";
      } else {
        $sqlStr = $sqlStr . " OR `id`= $itemID";
      }
      $quantities[$itemID] = $value['quantity'];
      $j++;
    }
  }

  $sql = "SELECT `id`,`name$languageTag` as `name`,`price`,`count`,`disabled` FROM `products` WHERE $sqlStr;";
  try {
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";
    goto endFunc;
  }

  if (mysqli_num_rows($sqlResult) === 0) {
    if ($result['error']) {
      goto endFunc;
    } else {
      if (count($userCartItems) > 0) {
        $result = updateUserCart($link, $result, $userId, NULL, NULL, NULL);
        if (!$result['error']) {
          $result['message'] = 'All products from cart were not found in the database and were removed from the cart.';
        } else {
          goto endFunc;
        }
      }
      $result['items'] = [];
      $result['itemsInCart'] = 0;
      goto endFunc;
    }
  }//Если товары из карзины не найдены в БД, чистим карзину

  $rows = mysqli_fetch_all($sqlResult, MYSQLI_ASSOC);//парсинг 

  if (count($rows) <> count($userCartItems)) {
    $priorityMsg = 'Some [' . count($userCartItems) - count($rows) . '] products were not found in the database and were removed from the cart.';
    $newProducts = [];
    foreach ($rows as $product) {
      $newProducts[] = ['quantity' => $quantities[$product['id']], 'productId' => $product['id']];
    }
    $result = updateUserCart($link, $result, $userId, $newProducts, NULL, time());
    unset($newProducts);
  }
  $products = [];
  $totalPrice = 0; //$quantities - quantities
  $messages = [];
  $updatesProducts = [];//Массив для изменения остатка заказанных товаров в БД

  //Проверка товаров.
  foreach ($rows as $product) {
    $quantity = intval($quantities[$product['id']]);//заказанное кол-во
    $quantityInStock = intval($product['count']);//доступно на складе
    if (($quantityInStock - $quantity) < 0) {
      $result['error'] = true;
      $messages[] = "Not enough product (" . $product['name' . $language[$lng]] . ") in stock.";
    }
    if ($product['disabled']) {
      $result['error'] = true;
      $messages[] = "Product (" . $product['name' . $language['en']] . ") in the cart is not available.";
    }
    $updatesProducts[intval($product['id'])] = ($quantityInStock - $quantity);//подготавливаем массив для изменения кол-ва товара на складе и возвращаем его
    $totalProductPrice = $quantity * intval($product['price']);
    $totalPrice += $totalProductPrice;
    $item = ['id' => $product['id'], 'name' => $product['name' . $language[$lng]], 'quantity' => $quantities[$product['id']], 'price' => $product['price'], 'total' => $totalProductPrice];
    $products[] = $item;
    $counter += $quantities[$product['id']];
  }

  if ($result['error'] && count($messages) > 0) {
    $result['code'] = 406;
    $result['message'] = $infoErrors['notEnoughtGoods'];
    $result['messages'] = $messages;
    goto endFunc;
  }//Если найдены нестыковки по кол-ву товаров - выходим
  $result['products'] = $products;
  $result['updatesProducts'] = $updatesProducts;
  if (!empty($priorityMsg)) {
    $result['message'] = $priorityMsg;
  }

  endFunc:
  return $result;
}
//Перенести в продуктс ОП если он будет
function updateProductsCounts($link, $result, $updatesProducts)
{
  //Функция автоматом дизейблит товар, когда его кол-во на складе = 0
  include 'scripts/variables.php';
  $funcName = 'updateProductsCounts_func';

  if (empty($result) || $result['error']) {
    goto endFunc;
  }
  if (!$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";
    goto endFunc;
  }
  if (!is_array($updatesProducts) || count($updatesProducts) === 0) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $dataErr['dataInFunc'] . "($funcName)";
    goto endFunc;
  }

  $caseSql = '';
  $ids = [];

  foreach ($updatesProducts as $id => $newCount) {
    $caseSql .= "WHEN {$id} THEN {$newCount} ";
    $ids[] = $id;
  }
  $idsSql = implode(',', $ids);
  // Финальный запрос
  $sql = "
UPDATE products
SET 
    count = CASE id
        $caseSql
    END,
    disabled = (CASE id
        $caseSql
    END = 0)
WHERE id IN ($idsSql);
";
  try {
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['updReqRejected'] . "($funcName)($emessage))";
    goto endFunc;
  }
  if (empty(mysqli_affected_rows($link)) || mysqli_affected_rows($link) < 1) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = "error changing goods in the warehouse! ($funcName)";
    goto endFunc;
  }
  if (mysqli_affected_rows($link) <> count($updatesProducts)) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = "Critical error! Failed to update all records in the database! ($funcName)";
    goto endFunc;
  }//Тут бы откатить изменения. Изменено строк меньше чем запросили на входе в функцию

  endFunc:
  return $result;
}//Обновление кол-ва продуктов в магазине исходя из заказа клиента

function compileOrderData($incOrder, $selectedDelivery, $address, $products, $userId){
  include 'scripts/variables.php';
  $order = [];
  $deliveryCost = intval($selectedDelivery['lPMinPrice']) <= intval($products['productsPrice']) ? intval($selectedDelivery['low_price']) : intval($selectedDelivery['delivery_price']);
  $order['deliveryCost'] = $deliveryCost;
  $order['deliveryType_id'] = $incOrder['deliveryTypeId'];
  $order['delivery_info'] = $address;// Адрес доставки. Для БД нужно кодировать json_encode($address,JSON_UNESCAPED_UNICODE)
  $order['firstName'] = $incOrder['firstName'];
  $order['lastName'] = $incOrder['lastName'];
  $order['paymentType_id'] = $incOrder['paymentTypeId'];
  $order['phone'] = $incOrder['phone'];
  $order['email'] = $incOrder['email'];
  $order['comment'] = $incOrder['comment'];
  $order['status_id'] = $startOrderStatus;
  $order['items'] = $products;//товары. для БД нужно кодировать json_encode($orderProducts,JSON_UNESCAPED_UNICODE)
  $order['user_id'] = intval($userId);
  $order['totalAmount'] = $deliveryCost + intval($products['productsPrice']);
  $order['createdAt'] = time();

  return $order;
}//Подготовка переменной заказа для добавления в базу и для ответа

function createOrder($link, $result, $order){
  include 'scripts/variables.php';
  $funcName = 'createOrder_func';
  if (empty($result) || $result['error']) {
    goto endFunc;
  }
  if (!$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";
    goto endFunc;
  }
  if (empty($order) || !is_array($order) || count($order) < 1) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $dataErr['dataInFunc'] . "($funcName)";
    goto endFunc;
  }

  if (!empty($order['items']) && is_array($order['items']) && count($order['items']) > 0) {
    //Преобразуем в строку json для сохранения в БД
    $order['items'] = json_encode($order['items'], JSON_UNESCAPED_UNICODE);
  } else {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['productsNotFound'] . "($funcName)";
    goto endFunc;
  }//Если нет товаров для добавления, выходим с ошибкой

  if (!empty($order['delivery_info']) && is_array($order['delivery_info']) && count($order['delivery_info']) > 0) {
    $order['delivery_info'] = json_encode($order['delivery_info'], JSON_UNESCAPED_UNICODE);
  }//Преобразуем в строку json для сохранения в БД

  $result = prepareInsertSQL($result, 'orders', $order);
  if ($result['error']) {
    goto endFunc;
  }
  $stmtData = $result['data'];
  unset($result['data']);

  try {
    $stmt = mysqli_prepare($link, $stmtData['sql']);
    mysqli_stmt_bind_param($stmt, $stmtData['types'], ...$stmtData['values']);
    mysqli_stmt_execute($stmt);
    $newOrderId = mysqli_insert_id($link);
    mysqli_stmt_close($stmt);
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['insertReqRejected'] . "($funcName) ($emessage))";
    goto endFunc;
  }

  if (empty($newOrderId) && $newOrderId < 1) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = "Problem with OrderID. Creating Order record in DB impossible.($funcName) ($emessage))";
    goto endFunc;
  }
  $result['newOrderId'] = $newOrderId;

  endFunc:
  return $result;
}//добавление заказа в таблицу

function getOrder($link, $result, $orderId, $languageTag = ''){
  include 'scripts/variables.php';
  $funcName = 'getOrder_func';
  if (empty($result) || $result['error']) {
    goto endFunc;
  }
  if (!$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";
    goto endFunc;
  }
  if (!$orderId || intval($orderId) < 1) {
    $result['error'] = true;
    $result['message'] = $errors['userIdNotFound'] . "($funcName)";
    goto endFunc;
  }
  settype($userId, 'integer');

  $sql = "SELECT 
    orders.*,
    delivery_types.deliveryType$languageTag as deliveryType,delivery_types.addressNeed,
    payment_types.paymentType$languageTag as paymentType,statuses.statusName$languageTag as statusName
    FROM orders 
    LEFT OUTER JOIN statuses ON orders.status_id = statuses.id 
    LEFT OUTER JOIN delivery_types ON orders.deliveryType_id = delivery_types.id
    LEFT OUTER JOIN payment_types ON orders.paymentType_id = payment_types.id
    WHERE orders.id = $orderId";
  try {
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e) {
    $emessage = $e->getMessage();
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";
    goto endFunc;
  }

  if (mysqli_num_rows($sqlResult) === 0) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $dbError['recordNotFound'] . "($funcName)";
  }

  $row = mysqli_fetch_array($sqlResult);//парсинг
  $order = [];
  $order['id'] = $row['id'];
  $order['deliveryCost'] = $row['deliveryCost'];
  $order['deliveryType_id'] = $row['deliveryType_id'];
  $order['deliveryType'] = $row['deliveryType' . $language[$lng]];
  !empty($row['delivery_info']) ? $order['delivery_info'] = json_decode($row['delivery_info']) : NULL;
  $order['firstName'] = $row['firstName'];
  $order['lastName'] = $row['lastName'];
  $order['phone'] = $row['phone'];
  $order['email'] = $row['email'];
  $order['paymentType_id'] = $row['paymentType_id'];
  $order['paymentType'] = $row['paymentType' . $language[$lng]];
  $order['comment'] = $row['comment'];
  $order['status_id'] = $row['status_id'];
  $order['statusName'] = $row['statusName' . $language[$lng]];
  $order['items'] = json_decode($row['items']);
  $order['createdAt'] = $row['createdAt'];
  $order['updatedAt'] = $row['updatedAt'];
  $order['totalAmount'] = $row['totalAmount'];

  $result['order'] = $order;


  endFunc:
  return $result;

  /*{
    "id": 1,
    "deliveryCost": 10,
    "deliveryType_id": 1,
    "deliveryType": "self",
    "delivery_info": {
         "zip": "66119",
         "city": "Saarbrücken",
         "house": "10",
         "region": "Saarland",
         "cistreetty": "Rubensstr."
     },
    "firstName": "Владимир",
    "lastName": "Волобуев",
    "phone": "380668000709",
    "email": "bob@gmail.com",
    "paymentType_id": 1,
    "paymentType": "self",
    "comment": "comment",
    "status_id": 1,
    "statusName": "new",
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
    "createdAt": "2025-09-22T18:01:12.856Z",
    "updatedAt": "2025-09-22T18:01:12.856Z",
    "totalAmount": 76
}*/
}//получение информации о заказах

function prepareOrderData($link, $result, $reqLanguage, $postDataJson)
{
  include 'scripts/variables.php';
  include 'deliveryOp';
  $funcName = 'prepareOrderData' . '_func';
  if (empty($result) || $result['error']) {
    goto endFunc;
  }
  if (!$link) {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";
    goto endFunc;
  }
  if (!$postDataJson || !is_array($postDataJson) || count($postDataJson) < 1) {
    $result['error'] = true;
    $result['message'] = $dataErr['notRecognized'] . "($funcName)";
    goto endFunc;
  }

  $messages = [];//Массив для ошибок
  $incOrder = [];//Переменная для сбора входящих данных заказа
  if (!empty($postDataJson['deliveryType']) && intval($postDataJson['deliveryType']) > 0) {
    $incOrder['deliveryTypeId'] = intval($postDataJson['deliveryType']);
  } else {
    $result['error'] = true;
    $messages[] = 'Invalid deliveryType';
  }
  if (!empty($postDataJson['firstName']) && (preg_match($firstNameRegEx, $postDataJson['firstName']))) {
    $incOrder['firstName'] = $postDataJson['firstName'];
  } else {
    $result['error'] = true;
    $messages[] = 'Invalid First Name!';
  }
  if (!empty($postDataJson['lastName']) && (preg_match($lastNameRegEx, $postDataJson['lastName']))) {
    $incOrder['lastName'] = $postDataJson['lastName'];
  } else {
    $result['error'] = true;
    $messages[] = 'Invalid Last Name!';
  }
  if (!empty($postDataJson['phone']) && (preg_match($telephoneRegEx, $postDataJson['phone']))) {
    $incOrder['phone'] = $postDataJson['phone'];
  } else {
    $result['error'] = true;
    $messages[] = 'Invalid Phone!';
  }
  if (!empty($postDataJson['email']) && (preg_match($emailRegEx, $postDataJson['email']))) {
    $incOrder['email'] = $postDataJson['email'];
  } else {
    $result['error'] = true;
    $messages[] = 'Invalid Email!';
  }
  if (!empty($postDataJson['paymentType']) && intval($postDataJson['paymentType']) > 0) {
    $incOrder['paymentTypeId'] = intval($postDataJson['paymentType']);
  } else {
    $result['error'] = true;
    $messages[] = 'Invalid Payment Type';
  }
  if (!empty($postDataJson['comment'])) {
    $incOrder['comment'] = $postDataJson['comment'];
  }
  //------------Проверка доставки------------
  if (!$result['error']) {
    //Запрос инфо о доставке и обработка ответа
    $result = getDeliveryInfo($link, $result, $incOrder['deliveryTypeId'], $requestLanguage, true, true);
    if ($result['error']) {
      goto endFunc;
    }
    $selectedDelivery = $result['selectedDelivery'];
    unset($result['selectedDelivery']);
    $needAddress = intval($selectedDelivery['addressNeed']);
  }//Делаем доп запросы только если нет ошибок

  //Проверка адреса если она необходима по доставке
  $address = [];
  if ($needAddress) {
    if (!empty($postDataJson['zip']) && (preg_match($zipCodeRegEx, $postDataJson['zip']))) {
      $address['zip'] = $postDataJson['zip'];
    } else {
      $result['error'] = true;
      $messages[] = 'Invalid ZIP Code';
    }
    if (!empty($postDataJson['region']) && in_array($postDataJson['region'], $regionsD)) {
      $address['region'] = $postDataJson['region'];
    } else {
      $result['error'] = true;
      $messages[] = 'Invalid Region';
    }
    if (!empty($postDataJson['city'])) {
      $address['city'] = $postDataJson['city'];
    } else {
      $result['error'] = true;
      $messages[] = 'Invalid Сity!';
    }
    if (!empty($postDataJson['street'])) {
      $address['cistreetty'] = $postDataJson['street'];
    } else {
      $result['error'] = true;
      $messages[] = 'Invalid Street!';
    }
    if (!empty($postDataJson['house'])) {
      $address['house'] = $postDataJson['house'];
    } else {
      $result['error'] = true;
      $messages[] = 'Invalid House!';
    }
    if (!empty($postDataJson['entrance'])) {
      $address['entrance'] = $postDataJson['entrance'];
    }
    if (!empty($postDataJson['apartment'])) {
      $address['apartment'] = $postDataJson['apartment'];
    }
  } else {
    $address = null;
  }

  //Если есть ошибки данных - выводим их
  if (count($messages) > 0) {
    $result['code'] = 406;
    $result['message'] = $errors['dataNotAcceptable'] . "($funcName)";
    $result['messages'] = $messages;
    goto endFunc;
  }

  //------------Проверка доступности метода оплаты------------
  $result = checkPayment($link, $result, $incOrder['paymentTypeId'], $reqLanguage);
  if ($result['error']) {
    goto endFunc;
  }

  //------------Вывод данных------------
  if (is_array($incOrder) && count($incOrder) > 0) {
    $result['incOrder'] = $incOrder;
  } else {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['outputtingFuncError'] . "[incOrder]($funcName)";
  }//Перед выводом проверяем переменную
  if (is_array($selectedDelivery) && count($selectedDelivery) > 0) {
    $result['selectedDelivery'] = $selectedDelivery;
  } else {
    $result['error'] = true;
    $result['code'] = 500;
    $result['message'] = $errors['outputtingFuncError'] . "[selectedDelivery]($funcName)";
  }//Перед выводом проверяем переменную

  if (is_array($address) && count($address) > 0)
    $result['address'] = $address;


  endFunc:
  return $result;
  //error 406: unacceptable format

}//Проверка входящих данных и подготовка

function getOrders($link, $result, $userId, $reqLanguage)
{
  include 'scripts/variables.php';
  $funcName = 'getOrders_func';
  if (empty($result) || $result['error']) {goto endFunc;}
  if (!$link) {$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['dbConnectInterrupt'] . "($funcName)";goto endFunc;}
  if (!$userId) {$result['error'] = true;$result['message'] = $errors['userIdNotFound'] . "($funcName)";goto endFunc;}

  $sql = "SELECT 
    o.id,
    o.deliveryCost,
    o.deliveryType_id, dt.deliveryType$reqLanguage as deliveryType,
    o.delivery_info,
    o.firstName, 
    o.lastName,
    o.phone,
    o.email,
    o.paymentType_id, pt.paymentType$reqLanguage as paymentType,
    o.comment,
    o.status_id, s.statusName$reqLanguage as statusName,
    o.items,
    o.user_id,
    o.totalAmount,
    o.createdAt,
    o.updatedAt
    FROM orders o
    INNER JOIN delivery_types dt ON o.deliveryType_id = dt.id
    INNER JOIN payment_types pt ON o.paymentType_id = pt.id
    INNER JOIN statuses s ON o.status_id = s.id
    WHERE `user_id` = ?";

    try{
      $stmt = $link->prepare($sql);
      if (!$stmt) {throw new Exception($link->error);}
      $stmt->bind_param("i", $userId);
      $stmt->execute(); 
      $response = $stmt->get_result();
      $stmt->close();
    }catch(Exception $e){$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}
    $orders=[];
    if ($response->num_rows==0){
      $result['orders'] = $orders;
      goto endFunc;
    }
    
    $orders = $response->fetch_all(MYSQLI_ASSOC);
    foreach ($orders as &$order) {
      !empty($order['delivery_info']) ? $order['delivery_info'] = json_decode($order['delivery_info']) : NULL;
      $order['items'] = json_decode($order['items']);
    }
    
    $result['orders'] = $orders;

  endFunc:
  return $result;
}