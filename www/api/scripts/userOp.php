<?php
//Проверка токена на валидность
function checkEmail($link,$result,$email,$checkRegex=true)
{
   include 'variables.php';
   $funcName = 'checkEmail_func';
   //Нужен доп параметр для того, чтоб контролировать ошибку. Если ошибка по функции, мы можем не завершать основной скрипт
   if (empty($link)) {
      $result['error']=true; $result['code'] = 500; $result['message'] = $errors['dbConnect'] . "($funcName)"; goto endFunc;
   }
   if (empty($email)) {
      $result['error']=true; $result['code'] = 400; $result['message'] = 'Email parameter not found! ' . "($funcName)"; goto endFunc;
   }

   if ($checkRegex && !preg_match($emailRegEx, $email)){
      $result['funcError']=true; $result['message'] ='EMail not acceptable!' . "($funcName)"; goto endFunc;
   }

   $sql = "SELECT `id` FROM users WHERE email = '" . $email . "'";
   $sqlResult = mysqli_query($link, $sql);
   $numRows = mysqli_num_rows($sqlResult);
   if ($numRows <> 0) {
      $result['error']=true; $result['code'] = 400; $result['message'] = 'User with this email is already registered!'; unset($result['funcError']); goto endFunc;
   }
   endFunc:
   return $result;
}

