<?php

//Проверяем тип запроса, обрабатываем только POST
if ($_SERVER["REQUEST_METHOD"] == "POST" || $_SERVER["REQUEST_METHOD"] == "GET") {
  //$to = 'volobuiev.volodymyr@icloud.com';
  $to = 'bobbygtx@gmail.com';

  $thema = 'Hello';

  $content = ' оставил заявку на бронирование для человек. Его телефон: ';
  $headers = 'MIME-Version: 1.0' . "\r\n";
  $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
  $headers .= 'From: AmoraFlowers <noreply@amoraflowers.atwebpages.com>' . "\r\n";


  //1 параметр - получатель. 2 - тема письма. 3 - текст. 4 - заголовки
  $success = mail($to, $thema, $content, $headers);//Отправка письма

  if ($success) {
    http_response_code(200);//отдаем код ответа 200 на http запрос
    echo "Письмо отправлено";
  } else {
    http_response_code(500);//отдаем ошибку 500 (Internal server error).
    echo "Письмо не отправлено";
  }

} else {
  http_response_code(403);
  echo
  "Данный метод запроса не поддерживается сервером!";
}