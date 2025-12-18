<?php
//Подключение к БД
include 'crypt.php';
include 'variables.php';
function dbConnect(){
  global $settingsFile;
  include 'variables.php';
  $result = ['error' => false, 'message' => 'Connected!', 'link' => null];
  $dbSettings = file_get_contents($settingsFile);//Чтение файла в переменную
  if ($dbSettings) {
    $dbSetJson = json_decode($dbSettings, true);
    if ($dbSetJson['host'] && $dbSetJson['login'] && $dbSetJson['password'] && $dbSetJson['dbName'] && $dbSetJson['key']) {
      $dbSetJson['password'] = __decode($dbSetJson['password'], $dbSetJson['key']);
      $link = mysqli_connect($dbSetJson['host'], $dbSetJson['login'], $dbSetJson['password'], $dbSetJson['dbName']);
    } else {
      $result = ['error' => true, 'message' => 'Unable to recognize settings data from settings file!', 'link' => null];
    }
  } else {
    $result = ['error' => true, 'message' => 'Error opening file with settings!', 'link' => null];
  }
  if (!$result['error'] && !empty($link)) {
    mysqli_set_charset($link, "utf8mb4");//Кодировка БД
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);// Включаем генерацию ошибок для нормальной работы try catch
    $result['link'] = $link;
  }
  return $result;
}

function getSettings($link) {
  if ($link) {
    include 'variables.php';
    $sql = "SELECT * FROM settings";
    $sqlResult = mysqli_query($link, $sql);
    $row = mysqli_fetch_array($sqlResult);//парсинг
    if ($row['secretKey']) {
      return [
        'secretKey' => $row['secretKey']
      ];
    } else {
      return false;
    }
  }
}