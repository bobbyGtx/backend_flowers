<?php

//Проверяем тип запроса, обрабатываем только POST
if ($_SERVER["REQUEST_METHOD"] == "POST" || $_SERVER["REQUEST_METHOD"] == "GET") {
  $to = 'bobbygtx@gmail.com';//Почта получателя данных формы, если несколько - то через запятую

//Получаем параметры посланные с JS
  $them = 'Hello';//Тема письма

//Создаем переменную с содержанием письма
  $content = ' оставил заявку на бронирование для человек. Его телефон: ';

  //Для отправки письма должен быть установлен заголовок Content-type
  $headers = 'MIME-Version: 1.0' . "\r\n";
  $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
  //Дополнительные заголовки
  $headers .= 'From: AmoraFlowers <no-reply@aflowers.com>' . "\r\n"; //Почта для обратного ответа. ФИО можно удалить или указать фирму


  //1 параметр - получатель. 2 - тема письма. 3 - текст. 4 - заголовки
  $success = mail($to, $them, $content, $headers);//Отправка письма

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