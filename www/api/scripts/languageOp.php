<?php

function languageDetection($reqHeaders){
  include 'variables.php';
  $defaultLanguage= 'ru';
  if (empty($reqHeaders) || !isset($language['x-language'])) return '';
  $headers = array_change_key_case($reqHeaders, CASE_LOWER);

  $lng = strtolower($headers['x-language']);
  $requestLanguage = isset($language[$lng])?$language[$lng]:'';
  return $requestLanguage;
}//Проверка запрашиваемого языка и возврат корректировки _en, _de ...