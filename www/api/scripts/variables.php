<?php
$productionMode = false;//верификация e-mail не обязательно в true
$productionURL = 'http://amoraflowers.atwebpages.com';//http://amoraflowers.com.xsph.ru
$frontendDevURL = 'http://localhost:4200';//Адрес активного фронтенда в режиме разработки.
$backendDevURL = "http://project.com";//Адрес папки бэкэнда на devPC
$frontendAddress = $productionMode?$productionURL:$frontendDevURL;
$frontendProductPage = 'product';
$projectUrl = $productionMode?$productionURL:$backendDevURL;//Адрес папки проекта бэкэнда
$passResetPage = 'change-password';//Адрес формы на фронтенде для ввода нового пароля
$passChangePage = 'profile';//Адрес страницы на фронтенде, на которой пользователь может сменить пароль
$confirmationScriptURL = $projectUrl.'/api/confirm.php';//путь скрипта для обработки подтверждающих запросов
$projectDir = dirname(__DIR__,2);
$settingsFile = $projectDir."/../DBSettings/dbData.json";//путь из папки scripts
$errorLogDir = $projectDir."/../logs";
$errorLogFile = $errorLogDir."/errors.log";
$storageDir = $projectDir."/../storage";//путь из папки scripts
$rateLimitDir = $storageDir.'/rate-limit';
$templatesDir = $projectDir."/api/templates/";//путь из папки scripts
$emailTemplatesDir = $projectDir."/api/templates/emails/";//путь из папки scripts

$imagesUrl = $projectUrl."/assets/";
$productsUrl = $imagesUrl."/products/";
//переменные для работы с токенами
$accessTokenHeader = 'x-access-token';
$userTableName = 'users';//название таблицы с токенами
$accTokenField = 'accessToken';//название поля
$accTokenLifeField = 'accTokenEndTime';//название поля
$refreshTokenField = 'refreshToken';//название поля
$refrTokenLifeField = 'refrTokenEndTime';//название поля
$accTokenLength = 100;
$refreshTokenLength = 120;
$accTokenLife = 600000;
$refrTokenLife = 2629743;
$operationTokenLength = 200;//токены для сброса пароля, верификации E-Mail и смены E-Mail. Время жизни в UserOpTypes ENUM
//opTokenLifeTimes in UserOpTypes enum

$rateLimit = 30;//КД для операций типа сброс пароля
$maxIncorrectLogins=3;//Количество неудачных попыток логина до блокировки
$incorrectLoginsBlockTime = 30;//Время блокировки после $maxIncorrectLogins неудачных попыток
$endsCount = 20;//Кол-во товаров с которых появляется метка "заканчивается"
$language=['ru'=>'','en'=>'_en','de'=>'_de'];//префикс для поля в бд
$startOrderStatus = 1;//Индекс начального статуа при создании заказа
$bestProductsCount = 8;//Количество лучших товаров
$productsPerPage = 9;//Параметр для пагинации. Кол-во товаров на странице

//Регулярки для проверки значений
$emailRegEx = '/^(([^<>()[\].,;:\s@"]+(\.[^<>()[\].,;:\s@"]+)*)|(".+"))@(([^<>()[\].,;:\s@"]+\.)+[^<>()[\].,;:\s@"]{2,})$/iu';
$passwordRegEx = '/^.{6,}$/';
$telephoneRegEx = '/^\+[1-9]\d{11,12}$/iu';//+14155552671, +497116666777
$firstNameRegEx = '/^(?=.{2,50}$)([A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+(?:-[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+)*(?:\s[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+(?:-[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+)*)*)$/u';
$lastNameRegEx = '/^(?=.{2,50}$)([A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+(?:-[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+)*(?:\s[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+(?:-[A-ZА-ЯЁÄÖÜ][a-zа-яёßäöü]+)*)*)$/u';
$zipCodeRegEx = '/^[0-9]{5}$/';
$houseNumberRegEx = '/^\d{1,3}[A-Za-z]?$/';
$accessTokenRegEx='/^[a-zA-Z0-9]{'.$accTokenLength.'}$/';
$refreshTokenRegEx='/^[a-zA-Z0-9]{'.$refreshTokenLength.'}$/';
$opTokenRegEx='/^[a-zA-Z0-9]{'.$operationTokenLength.'}$/';

$regionsD = ['Baden-Württemberg','Bayern','Berlin','Brandenburg','Bremen','Hamburg','Hessen','Mecklenburg-Vorpommern','Niedersachsen','Nordrhein-Westfalen','Rheinland-Pfalz','Saarland','Sachsen','Sachsen-Anhalt','Schleswig-Holstein','Thüringen'];
//Расчёт путей на основное использование из папки api
$noFotoFileName = 'no-image.jpg';//название файла заглушки картинки
$photoDir = '../assets/';//Директория с фото. Обязательно с точки

$errors['dbConnect'] = 'DB connection Error! ';//Ошибка соединения с БД
$errors['dbConnectInterrupt'] = 'Connection with DB interrupt. ';//Ошибка соединения с БД
$errors['dbrequestSettings'] = 'Error while requesting settings from DB. ';
$errors['reqRejected']='Request rejected by database. ';
$errors['selReqRejected']='Request (SELECT) rejected by database. ';
$errors['insertReqRejected']='Request (INSERT) rejected by database. ';
$errors['updReqRejected']='Request (UPDATE) rejected by database. ';
$errors['updReqNothing']='Request (UPDATE) did not change anything.';//DumpError
$errors['delReqRejected']='Request (DELETE) rejected by database. ';
$errors['recognizeUnableDB'] = 'Unable to recognize data from database! ';
$errors['deliveryIdNotFound'] ='Delivery identifier not found!';//400, 406
$errors['paymentIdNotFound'] ='Payment method identifier not found!'; //500, 400, 406
$errors['userIdNotFound'] = 'User ID not found in function! ';//500
$critErr['productUrlNotFound'] ='Product URL not found in function! ';//500
$errors['orderIdNotFound'] = 'Order ID not found in function! ';
$errors['productIdNotFound'] = 'Product ID not found in function! ';
$errors['quantityNotFound'] = 'Quantity not found in function! ';
$errors['outputtingFuncError'] = 'Error in outputting a variable from a function! ';

$dbError['unexpResponse'] = 'Unexpected response from Database! ';//500
$dbError['cartNotFound'] = 'Critical error! User cart not found! ';
$dbError['tableNameNotFound'] = 'Critical error! Table name not found! ';
$dbError['recordNotFound'] = 'Requested Record not found! ';
$dbError['multipleRecords'] = 'Multiple records were found in the database! ';
$dbError['recordsNotFound'] = 'Requested Records not found! ';
$dbError['connectionError'] = 'DB connection Error! ';
$dbError['deliveryTypesNF'] = 'Delivery types not found in DB!';
$dbError['paymentTypesNF'] = 'Payment types not found in DB!';
$errors['productNotFound'] = 'Product not found! ';//400
$errors['productsNotFound'] = 'Products not found!';
$errors['userDataNotFound'] = 'User ID not found! ';
$errors['dataNotFound'] = 'No data was passed to the function or it was incorrect. ';
$errors['cartEmpty'] = 'User cart empty!';
$errors['unexpectedFuncResult'] = 'Unexpected result from function! ';
$errors['cartRebaseImpossible'] = 'Cart rebase impossible. User have a cart!';
$errors['allProductsCleared'] = 'All products from cart were not found in the database and were removed from the cart.';
$errors['tokenFieldNotFound'] = 'Token field name not found in fieldList.';//500

$errors['MethodNotAllowed']='Method Not Allowed';
$errors['unknownOperationType']='Unknown operation type!';//400
$errors['opTypeNotSupport']='Operation type not supported!';//400
$errors['rateOpTypeNotFound']='Rate operation type not found!';//500
$errors['emailAlreadyConfirmed']='Email address already confirmed!';//400
$errors['dataNotAcceptable'] = 'Data not acceptable!';//406

$dataErr['notRecognized'] = 'Request parameters not recognized!';//Code 500
$dataErr['dataInFunc'] = 'Error in data passed to the function!';
$dataErr['sortRuleNotRec'] = 'The sorting rule is not recognized!';

$critErr['userDNotFound'] ='Critical error! User data not found in record.';
$critErr['userIdNotFound'] ='Critical error! User ID not found in record.';//500
$critErr['recordIdNotFound'] ='Critical error! Record ID not found.';//500
$critErr['UserOpNotFound'] ='Critical error! Selected user operation type (enum) not processed.';//500
$critErr['openFileError'] ='Critical error! Unable to open file.';//500

$infoErrors['delivNotPos'] = 'Selected Delivery Type not possible now!';//400
$infoErrors['paymentNotPos'] = 'Selected Payment method not possible now!';
$infoErrors['notEnoughtGoods'] = 'Not enough goods in stock.';
$infoErrors['createOrderError'] = 'Create order error.';
$infoErrors['productNotAvailable'] = 'Requested product is currently unavailable!';
$infoErrors['nothingToChange'] = 'Nothing to change!';
$infoErrors['cartClearedBySystem'] = 'Cart has been cleared by the system.';
$infoErrors['someProductsRemoved'] = 'Unrecognized products were removed.';//400

$errors['emailIsBusy'] = 'E-Mail is busy!';//400, 406
$errors['emailNotValid'] ='Email not valid!';//406
$errors['emailNotRecognized'] = 'E-Mail not recognized!';//500, 400

$authError['loginOrPassNC'] ='Login or password not correct!';
$authError['emailNotFound'] ='E-mail not found in DB!';//401, 400
$authError['wrongPassword'] ='Password wrong!';//401
$authError['emailNotValid'] ='Email not valid!';//401, 400
$authError['passwortNotCorrect'] ='Password not acceptable!';//401

$authError['refrTokenInvalid'] = 'Refresh token invalid!';//401
$authError['refrTokenOutOfDate'] = 'Refresh token out of date!';//401
$authError['accTokenNotFound'] = 'Access token not found or has not valid format!';//401
$authError['refrTokenNotFound'] = 'Refresh token not found or has not valid format!';//401
$authError['accTokenInvalid'] = 'Access token invalid!';//401
$authError['accTokenOutOfDate'] = 'Access token out of date!';//401
$authError['tooManyFailedLogins'] = 'Too many failed login attempts. Try later.';//429
$opErrors['opTokenInvalid'] = 'Operation token invalid!';//500, 400
$opErrors['opTokenNotFound'] = 'Operation token not found!';//500
$opErrors['confTokenNotFound'] = 'Confirmation token not found!';//400
$opErrors['opTokenOutOfDate'] = 'Operation token out of date!';//400,403
$opErrors['timeStampNotFound'] = 'Timestamp not found';//500
$opErrors['EmailTemplateNotFound'] = 'Email template not found';//500
$opErrors['linkNotValid'] = 'Link not valid';//400
$opErrors['newPasswordNotRecognized'] ='New password not recognized!';//400
$opErrors['newPasswortNotValid'] ='New password is too simple!';//406
$opErrors['passwordsNotMatch'] ="Passwords don't match!";//400

$infoMessages['reqSuccess'] = 'Request success!';
$infoMessages['emailSent'] = 'Email has been sent!';
$infoMessages['сartRebased'] = 'Cart has been rebased!';
$infoMessages['recordChanged'] = 'Record changed!';
$infoMessages['recordDeleted'] = 'Record deleted!';
$infoMessages['passwordNotChanged'] = 'The password has not been changed!';
$infoMessages['passwordChanged'] = 'The password has been changed!';
$infoMessages['userBlocked'] = 'User blocked!';//403