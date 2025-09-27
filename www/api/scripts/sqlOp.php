<?php

function prepareInsertSQL($result, $tableName, $assArray){
  //Подготовка переменных $sql, $types, $values для добавления записи в таблицу методом подготовленного выражения
  include 'scripts/variables.php';
  $funcName = 'prepareInsertSQL_func';
  if (empty($result) || $result['error']){goto endFunc;}
  if (empty($tableName)){$result['error']=true; $result['code']=500;$result['message']=$dbError['tableNameNotFound'] . "($funcName)";goto endFunc;}
  if (empty($assArray) || !is_array($assArray) || count($assArray)<1){
    $result['error']=true; $result['code']=500;$result['message']="No data was passed to the function or it was incorrect. ($funcName)";goto endFunc;
  }

  $columns = implode(", ", array_keys($assArray));// deliveryCost, deliveryType_id, delivery_info...
  $placeholders = implode(", ", array_fill(0, count($assArray), '?')); // ?, ?, ?, ? ...

  $sql = "INSERT INTO $tableName ($columns) VALUES ($placeholders)";
  // Определяем типы данных (s = string, i = integer, d = double, b = blob)
  $types = '';
  $values = [];
  
  foreach ($assArray as $value) {
    if (is_int($value)) {
        $types .= 'i';
    } elseif (is_float($value)) {
        $types .= 'd';
    } else {
        $types .= 's';
    }
    $values[] = $value;
  }
  $result['data'] = ['sql'=>$sql, 'types' => $types, 'values'=>$values];

  //$stmt = mysqli_prepare($link, $sql);
  //mysqli_stmt_bind_param($stmt, $types, ...$values);
  //mysqli_stmt_execute($stmt)

  endFunc:
  return $result;
}//Получение инфо о выбранном методе оплаты.
