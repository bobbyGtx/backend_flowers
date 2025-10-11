<?php
function getProductInfo($link, $result, $productId, $languageTag=''){

  //переделать запрос на явные поля!
  include 'variables.php';
  $funcName = 'favoritesRequest_func';

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!$productId) {$result['error']=true; $result['message'] = $errors['productIdNotFound'] . "($funcName)"; goto endFunc;}

  $sql = "SELECT `id`,`name$languageTag` as `name`,`price`,`image`,`type_id`,`lightning$languageTag` as `lightning`,`humidity$languageTag` as `humidity`,`temperature$languageTag` as `temperature`,`height`,`diameter`,`url`,`count`,`disabled` FROM `products` WHERE `id` = $productId;";
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;
  }

  if (mysqli_num_rows($sqlResult)===0){$result['error']=true; $result['code']=400;$result['message']=$errors['productNotFound'] . "($funcName)";goto endFunc;}

  $result['row'] = mysqli_fetch_assoc($sqlResult);

  endFunc:
  return $result;
}//Получение инфо о товаре по id

function getProducts($link, $result, $getReq, $languageTag=''){
  include 'variables.php';
  $funcName = 'getProducts_func';

  if (empty($result) || $result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  
  $filterSQL='';
  $sortSQL = '';

  if (is_array($getReq) && count($getReq) > 0) {
    
    $types = $getReq['types'];
    $diameterFrom = $getReq['diameterFrom'];
    $diameterTo = $getReq['diameterTo'];
    $heightFrom = $getReq['heightFrom'];
    $heightTo = $getReq['heightTo'];
    $priceFrom = $getReq['priceFrom'];
    $priceTo = $getReq['priceTo'];
    $sort = $getReq['sort'];
    $page = $getReq['page'];

    $filters=[];
    $sortSQL = '';
    
    if (!empty($types)&& is_array($types) && count($types)>0){
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
      switch($sort){
        case "price-asc":
          $sortSQL = " ORDER BY p.price ASC";
          break;
        case "price-desc":
          $sortSQL = " ORDER BY p.price DESC";
          break;
        case "name-asc":
          $sortSQL = " ORDER BY p.name$languageTag ASC";
          break;
        case "name-desc":
          $sortSQL = " ORDER BY p.name$languageTag DESC";
          break;
        default:
          $result['error']=true; $result['code']=500; $result['message'] = $dataErr['sortRuleNotRec'] . "($funcName)";
          goto endFunc;
      }
    }

    if(count($filters) > 0){
      $filterSQL = ' WHERE ' .implode("AND", $filters);
    }
    
  }//Обработка параметров запроса

  $baseSQL = "SELECT p.id, p.name$languageTag, p.price, p.image, p.height, p.diameter, p.url, p.type_id, t.name$languageTag as typeName, t.url as typeUrl FROM products p INNER JOIN types t ON p.type_id = t.id";
  $sql = "$baseSQL$filterSQL$sortSQL;";
  
  try{
    $sqlResult = mysqli_query($link, $sql);
  } catch (Exception $e){
    $emessage = $e->getMessage();
    $result['error']=true; $result['code']=500; $result['message']=$errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;
  }
  
  if(mysqli_num_rows($sqlResult) == 0){
    $result['response'] = ['totalCount'=>0,'items'=>[]];
    goto endFunc;
  }
  $items = mysqli_fetch_all($sqlResult, MYSQLI_ASSOC);
  foreach($items as &$item){
    $item['type'] = ['id'=>$item['type_id'],'name'=>$item['typeName'],'url'=>$item['typeUrl']];
    unset($item['type_id'],$item['typeName'],$item['typeUrl']);
  }//преобразование типа продукта в объект
  $result['response'] = ['totalCount'=>mysqli_num_rows($sqlResult),'items'=>$items];

  /*
    SELECT p.id, p.name$languageTag, p.price, p.image, p.height, p.diameter, p.url, p.type_id, t.name$languageTag as typeName, t.url as typeUrl
    FROM products p
    INNER JOIN types t ON p.type_id = t.id
    WHERE (t.url = 'flowering' OR t.url = 'palms')
      AND p.height >= 16 AND p.height <= 100
      AND p.diameter >= 10 AND p.diameter <= 20
      AND p.price >= 14 AND p.price <= 40
    ORDER BY p.price ASC; // p.name DESC
  */
  endFunc:
  //$result['sql'] = $sql;
  //$result['get'] = $getReq;
  return $result;
}//Функция обработки запроса товаров с фильтрами
