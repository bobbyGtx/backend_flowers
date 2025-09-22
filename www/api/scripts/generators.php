<?php

function generate_string($strength = 16)
{
   $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';//строка допустимых символов
   $input_length = strlen($input);
   $random_string = '';
   for ($i = 0; $i < $strength; $i++) {
      $random_character = $input[mt_rand(0, $input_length - 1)];
      $random_string .= $random_character;
   }
   return $random_string;
}//$accessToken = generate_string(100);//Генерация accessToken

function generate_jpg_name($strength = 16)
{
   include 'variables.php';
   $dir =".".$photoDir;//директория с фото
   $files =[];
   foreach (glob($dir . '*.jpg') as $fileName) {
      $files[]=basename($fileName);
   }
   function generate_str($strength,$filesArr){
      $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';//строка допустимых символов
      $input_length = strlen($input);
      $random_string = '';
      for ($i = 0; $i < $strength; $i++) {
         $random_character = $input[mt_rand(0, $input_length - 1)];
         $random_string .= $random_character;
      }
      if (array_search($random_string.".jpg", $filesArr, true)!==false){
         generate_str($strength,$filesArr);
       }else{
         return $random_string;
       } 
   }
   $uniqFileName = generate_str($strength,$files);
   return $uniqFileName;
}//generate_jpg_name(20);//Генерация уникального имени файла
