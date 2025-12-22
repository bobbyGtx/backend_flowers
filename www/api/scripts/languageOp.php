<?php

function languageDetection($reqHeaders){
  include 'variables.php';
  $defaultLanguage= 'ru';
  $headers = array_change_key_case($reqHeaders, CASE_LOWER);
  if (!isset($headers['x-language']) && !isset($headers['lng'])) return '';
  $lng = isset($headers['x-language'])?strtolower($headers['x-language']): strtolower($headers['lng']);
  $requestLanguage = isset($language[$lng])?$language[$lng]:'';
  return $requestLanguage;
}//Проверка запрашиваемого языка и возврат корректировки _en, _de ...