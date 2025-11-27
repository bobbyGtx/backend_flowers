<?php
function getProductShortInfo($link, $result, $productId, $languageTag=''){
  include 'variables.php';
  $funcName = 'getProductShortInfo_func';

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$productId) {$result['error']=true; $result['code']=500; $result['message'] = $errors['productIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql = "SELECT `id`,`name$languageTag` as `name`,`price`,`image`,`url`,`count`,`disabled` FROM `products` WHERE `id` = $productId;";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->execute(); 
    $response = $stmt->get_result();
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}

  if ($response->num_rows==0){
    $result['error']=true; $result['code']=400;$result['message']=$errors['productNotFound'] . "($funcName)";goto endFunc;
  }

  $row = $response->fetch_assoc();
  if ($productId !== $row['id']){
    $result['error']=true; $result['code'] = 500; $result['message'] = "Requestet productId != returned id from DB! ($funcName)"; goto endFunc;
  }

  $row['ends'] = intval($row['count'])<$endsCount?true:false;//товар заканчивается
  $result['product'] = $row;

  endFunc:
  return $result;
}//Получение инфо о товаре по id

function getProductCount($link, $result, $productId){
  //переделать запрос на явные поля!
  include 'variables.php';
  $funcName = 'getProducCount_func';

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$productId) {$result['error']=true; $result['code']=500; $result['message'] = $errors['productIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql = "SELECT `id`,`count`,`disabled` FROM `products` WHERE `id` = $productId;";

  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->execute(); 
    $response = $stmt->get_result();
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}

  if ($response->num_rows==0){$result['error']=true; $result['code']=400;$result['message']=$errors['productNotFound'] . "($funcName)";goto endFunc;}

  $result['info'] = $response->fetch_all(MYSQLI_ASSOC);

  endFunc:
  return $result;
}//Получение инфо о товаре по id

function getProducts($link, $result, $getReq, $languageTag=''){
  include 'variables.php';
  $funcName = 'getProducts_func';

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  
  $filterSQL='';
  $sortSQL = " ORDER BY p.disabled ASC";

  if (is_array($getReq) && count($getReq) > 0) {
    if (isset($getReq['types'])){
      $types=is_array($getReq['types'])?$getReq['types']:null;
    }//Передавать параметры types: types[]=str&types[]=str
    $diameterFrom = !empty($getReq['diameterFrom'])?$getReq['diameterFrom']:null;
    $diameterTo = !empty($getReq['diameterTo'])?$getReq['diameterTo']:null;
    $heightFrom = !empty($getReq['heightFrom'])?$getReq['heightFrom']:null;
    $heightTo = !empty($getReq['heightTo'])?$getReq['heightTo']:null;
    $priceFrom = !empty($getReq['priceFrom'])?$getReq['priceFrom']:null;
    $priceTo = !empty($getReq['priceTo'])?$getReq['priceTo']:null;
    $sort = !empty($getReq['sort'])?$getReq['sort']:null;
    $page = !empty($getReq['page'])?intval($getReq['page']):1;

    
    $filters=[];
    
    if (!empty($types) && is_array($types) && count($types)>0){
      if (count($types)==1){
        $filters[] = "t.url = '$types[0]'";
      }else{
        $i=0;
        $accStr = '';
        foreach ($types as $type){
          if ($i==0){
            $accStr="t.url = '$type'";
          }else{
            $accStr=$accStr." OR t.url = '$type'";
          }
          $i++;
        }
        $filters[] = "($accStr)";//t.url = 'flowering' или (t.url = 'flowering' OR t.url = 'palms')
      }
    }//обработка фильтра по типам
    //---------диаметр
    if (!empty($diameterFrom)){
      settype($diameterFrom,"integer");
      $filters[] = "p.diameter >= $diameterFrom";
    }//обработка минимального диаметра
    if (!empty($diameterTo)){
      settype($diameterTo,"integer");
      $filters[] = "p.diameter <= $diameterTo";
    }//обработка максимального диаметра
    //---------высота
    if (!empty($heightFrom)){
      settype($heightFrom,"integer");
      $filters[] = "p.height >= $heightFrom";
    }//обработка минимальной высоты
    if (!empty($heightTo)){
      settype($heightTo,"integer");
      $filters[] = "p.height <= $heightTo";
    }//обработка максимальной высоты
    //---------Цена
    if (!empty($priceFrom)){
      settype($priceFrom,"integer");
      $filters[] = "p.price >= $priceFrom";
    }//обработка минимальной цены
    if (!empty($priceTo)){
      settype($priceTo,"integer");
      $filters[] = "p.price <= $priceTo";
    }//обработка максимальной цены

    //---------сортировка price-asc,price-desc,name-asc,name-desc
    
    if (!empty($sort)){
      $sort = strtolower($sort);
      switch($sort){
        case "price-asc":
          $sortSQL = "$sortSQL, p.price ASC";
          break;
        case "price-desc":
          $sortSQL = "$sortSQL, p.price DESC";
          break;
        case "name-asc":
          $sortSQL = "$sortSQL, p.name$languageTag ASC";
          break;
        case "name-desc":
          $sortSQL = "$sortSQL, p.name$languageTag DESC";
          break;
        default:
          $result['error']=true; $result['code']=500; $result['message'] = $dataErr['sortRuleNotRec'] . "($funcName)";
          goto endFunc;
      }
    }

    if(count($filters) > 0){
      $filterSQL = ' WHERE ' .implode(" AND ", $filters);
    }
  }//Обработка параметров запроса

   
  if (isset($page) && (!empty($page) || $page >0 )) $offset = ($page-1)*$productsPerPage; 
  else {$offset=0;$page=1;} 
  
  $baseSQL = "SELECT 
  p.id,
  p.name$languageTag,
  p.price, 
  p.image,
  p.lightning$languageTag as lightning,
  p.humidity$languageTag as humidity,
  p.temperature$languageTag as temperature,
  p.height, 
  p.diameter, 
  p.url, 
  p.count,
  p.disabled,
  p.type_id, 
  t.name$languageTag as typeName, 
  t.url as typeUrl,
  t.category_id
  FROM products p INNER JOIN types t ON p.type_id = t.id";
  $sql = "$baseSQL$filterSQL$sortSQL LIMIT $offset, $productsPerPage;";

  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->execute(); 
    $response = $stmt->get_result();
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}
  
  if ($response->num_rows==0){
        $result['response'] = ['page'=>1,'totalPages'=>1, 'totalProducts'=>0, 'products'=>[]];goto endFunc;
  }
  
  // Получаем результат
  $items = $response->fetch_all(MYSQLI_ASSOC);
  foreach($items as &$item){
    $item['type'] = ['id'=>$item['type_id'],'name'=>$item['typeName'],'url'=>$item['typeUrl']];
    unset($item['type_id'],$item['typeName'],$item['typeUrl']);
  }//преобразование типа продукта в объект

  // Считаем общее количество товаров
  $totalResult = mysqli_query($link, "SELECT COUNT(*) AS total FROM products p INNER JOIN types t ON p.type_id = t.id$filterSQL");
  $totalRow = mysqli_fetch_assoc($totalResult);
  $total = intval($totalRow['total']);
  // Считаем количество страниц
  $totalPages = ceil($total / $productsPerPage);

  $result['response'] = ['page'=>$page,'totalPages'=>$totalPages,'totalProducts'=>$total,'products'=>$items];
  
  endFunc:
  return $result;
}//Функция обработки запроса товаров с фильтрами

function getProductInfo($link, $result, $productUrl, $languageTag=''){
  include 'variables.php';
  $funcName = 'getProductInfo_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$productUrl) {$result['error']=true; $result['code']=500; $result['message'] = $errors['productUrlNotFound'] . "($funcName)"; goto endFunc;}
  $productUrl = strtolower($productUrl);

  $sql = "SELECT 
  p.id,
  p.name$languageTag,
  p.price,
  p.image,
  p.type_id,
  p.lightning$languageTag,
  p.humidity$languageTag,
  p.temperature$languageTag,
  p.height,
  p.diameter,
  p.url,
  p.count,
  p.disabled,
  t.name$languageTag as typeName,
  t.url as typeUrl,
  t.category_id
  FROM products p 
  INNER JOIN types t ON p.type_id = t.id
  WHERE p.url = ?";

  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    // Привязываем параметр (s = string)
    $stmt->bind_param("s", $productUrl);
    $stmt->execute(); 
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}
  // Получаем результат
  $resultSet = $stmt->get_result();
  // Проверка на пустой результат
  if ($resultSet->num_rows === 0) {$result['error'] = true;$result['code'] = 400;$result['message'] = $errors['productNotFound'];goto endFunc;}
  // Берём только ассоциативный массив
  $product = $resultSet->fetch_assoc();

  $product['type'] = ['id'=>$product['type_id'],'name'=>$product['typeName'],'url'=>$product['typeUrl']];
  unset($product['type_id'],$product['typeName'],$product['typeUrl']);

  // Кладём в итоговый массив
  $result['product'] = $product;
  // Освобождаем ресурсы
  $stmt->close();
 
  endFunc:
  //$result['sql'] = $sql;
  return $result;
}//Получение информации об одном товаре

function searchProducts($link, $result, $searchStr, $languageTag=''){
  include 'variables.php';
  $funcName = 'searchProducts_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$searchStr) {$result['error']=true; $result['code']=500; $result['message'] = $dataErr['dataInFunc'] . "($funcName)"; goto endFunc;}
  $searchStr = '%' . strtolower($searchStr) . '%';

  $sql = "SELECT p.id,p.name$languageTag,p.price,p.image,p.type_id,p.lightning$languageTag,p.humidity$languageTag,p.temperature$languageTag,p.height,p.diameter,p.url,p.count,p.disabled, t.name$languageTag as typeName, t.url as typeUrl, t.category_id
    FROM products p 
    INNER JOIN types t ON p.type_id = t.id
    WHERE LOWER(p.name) LIKE LOWER(?)
      OR LOWER(p.name_en) LIKE LOWER(?)
      OR LOWER(p.name_de) LIKE LOWER(?)";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    // Привязываем параметр (s = string)
    $stmt->bind_param("sss", $searchStr,$searchStr,$searchStr);
    $stmt->execute(); 
    $response = $stmt->get_result();
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}
  // Получаем результат
  if ($response->num_rows>0){
    $products = $response->fetch_all(MYSQLI_ASSOC);
    foreach ($products as &$product) {
      $product['type'] = ['id'=>$product['type_id'],'name'=>$product['typeName'],'url'=>$product['typeUrl']];
      unset($product['type_id'],$product['typeName'],$product['typeUrl']);
    }
    $result['products'] = $products;
  }else{
    $result['products'] = [];
  }

  endFunc:
  return $result;
}//Поиск продуктов соответствующих строке запроса
function getBestProducts($link, $result, $languageTag=''){
  include 'variables.php';
  $funcName = 'getBestProducts_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  $sql = "SELECT p.id,
  p.name$languageTag as name,
  p.price,
  p.image,
  p.type_id,
  p.lightning$languageTag as lightning,
  p.humidity$languageTag as humidity,
  p.temperature$languageTag as temperature,
  p.height,p.diameter,p.url,p.count,p.disabled,t.category_id,
  t.name$languageTag as typeName,
  t.url as typeUrl,
  (p.count / p.price) AS score
  FROM products p 
  INNER JOIN types t ON p.type_id = t.id
  WHERE p.count > 0 AND p.disabled = 0
  ORDER BY score DESC
  LIMIT $bestProductsCount";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->execute(); 
    $response = $stmt->get_result();
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}
  if ($response->num_rows==0){
    $result["error"]=true; $result["code"]= 500; $result["message"] = $dbError['unexpResponse']; goto endFunc;
  }
  
  // Получаем результат
  $products = $response->fetch_all(MYSQLI_ASSOC);
  foreach ($products as &$product) {
    $product['type'] = ['id'=>$product['type_id'],'name'=>$product['typeName'],'url'=>$product['typeUrl']];
    unset($product['type_id'],$product['typeName'],$product['typeUrl'],$product['score']);
  }
  $result['products'] = $products;

  endFunc:
  return $result;
}//Получение списка лучших продуктов

function getRecommendProducts($link, $result, $categoryId=0, $productId=0, $languageTag=''){
  include 'variables.php';
  $funcName = 'getRecommendProducts_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  settype($categoryId,"integer"); if (!($categoryId >= 0)) $categoryId = 0;
  settype($productId,"integer"); if (!($productId >= 0)) $productId = 0;

  $sql = "SELECT p.id,
  p.name$languageTag as name,
  p.price,
  p.image,
  p.type_id,
  p.lightning$languageTag as lightning,
  p.humidity$languageTag as humidity,
  p.temperature$languageTag as temperature,
  p.height,p.diameter,p.url,p.count,p.disabled,t.category_id,
   t.name$languageTag as typeName,
    t.url as typeUrl
    FROM products p 
    INNER JOIN types t ON p.type_id = t.id
    INNER JOIN categories c ON t.category_id = c.id " . ($categoryId>0?"WHERE c.id = $categoryId AND p.id <> $productId AND p.count > 0 AND p.disabled=0":"WHERE p.id <> $productId AND p.count > 0 AND p.disabled=0") .
    " ORDER BY p.count DESC,RAND() LIMIT $bestProductsCount";
    
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->execute(); 
    $response = $stmt->get_result();
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}
  if ($response->num_rows==0){
    $result["error"]=true; $result["code"]= 500; $result["message"] = $dbError['unexpResponse']; goto endFunc;
  }
  
  // Получаем результат
  $products = $response->fetch_all(MYSQLI_ASSOC);
  foreach ($products as &$product) {
    $product['type'] = ['id'=>$product['type_id'],'name'=>$product['typeName'],'url'=>$product['typeUrl']];
    unset($product['type_id'],$product['typeName'],$product['typeUrl']);
  }
  $result['products'] = $products;

  endFunc:
  return $result;
}//Получение списка рекомендуемых продуктов