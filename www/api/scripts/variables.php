<?php
$settingsFile = "../../DBSettings/dbData.json";//путь из папки scripts
//переменные для работы с токенами
$userTableName = 'users';//название таблицы с токенами
$accTokenField = 'accessToken';//название поля
$accTokenLifeField = 'accTokenEndTime';//название поля
$refreshTokenField = 'refreshToken';//название поля
$refrTokenLifeField = 'refrTokenEndTime';//название поля
$accTokenLenght = 100;
$refrTokenLenght = 120;
$accTokenLife = 12120;
$refrTokenLife = 2629743;
$endsCount = 20;//Кол-во товаров с которых появляется метка "заканчивается"
$language=['ru'=>'','en'=>'_en','de'=>'_de'];
$startOrderStatus = 1;//Индекс начального статуа при создании заказа

//Регулярки для проверки значений
$emailRegEx = '/^(([^<>()[\].,;:\s@"]+(\.[^<>()[\].,;:\s@"]+)*)|(".+"))@(([^<>()[\].,;:\s@"]+\.)+[^<>()[\].,;:\s@"]{2,})$/iu';
$passwordRegEx = '/^.{6,}$/iu';
$telephoneRegEx = '/^\+[1-9]\d{1,14}$/iu';//+14155552671, +497116666777
$firstNameRegEx = '/^(?=.{2,50}$)([A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+(?:-[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+)*(?:\s[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+(?:-[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+)*)*)$/';
$lastNameRegEx = '/^(?=.{2,50}$)([A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+(?:-[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+)*(?:\s[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+(?:-[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+)*)*)$/';
$zipCodeRegEx = '/^[0-9]{5}$/';
$accessTokenRegEx='/^[a-zA-Z0-9]{'.$accTokenLenght.'}$/';
$refreshTokenRegEx='/^[a-zA-Z0-9]{'.$refrTokenLenght.'}$/';
$regionsD = ['Baden-Württemberg','Bayern','Berlin','Brandenburg','Bremen','Hamburg','Hessen','Mecklenburg-Vorpommern','Niedersachsen','Nordrhein-Westfalen','Rheinland-Pfalz','Saarland','Sachsen','Sachsen-Anhalt','Schleswig-Holstein','Thüringen'];
//Рассчёт путей на основное использование из папки api
$noFotoFileName = 'no-image.jpg';//название файла заглушки картинки
$photoDir = '../assets/';//Директория с фото. Обязательно с точки

$errors['dbConnect'] = 'DB connection Error! ';//Ошибка соединения с БД
$errors['dbConnectInterrupt'] = 'Connection with DB interrupt. ';//Ошибка соединения с БД
$errors['dbrequestSettings'] = 'Error while requesting settings from DB. ';
$errors['reqRejected']='Request rejected by database. ';
$errors['selReqRejected']='Request (SELECT) rejected by database. ';
$errors['insertReqRejected']='Request (INSERT) rejected by database. ';
$errors['updReqRejected']='Request (UPDATE) rejected by database. ';
$errors['delReqRejected']='Request (DELETE) rejected by database. ';
$errors['recognizeUnableDB'] = 'Unable to recognize data from database! ';

$errors['userIdNotFound'] = 'User ID not found! ';
$errors['productNotFound'] = 'Product not found! ';
$errors['productsNotFound'] = 'Products not found! ';
$errors['userDataNotFound'] = 'User ID not found! ';

$errors['MethodNotAllowed']='Method Not Allowed';

$dataErr['notRecognized'] = 'Request parameters not recognized! ';
$dataErr['dataInFunc'] = 'Error in data passed to the function! ';
$infoErrors['delivNotPos'] = 'Selected Delivery Type not possible now!';