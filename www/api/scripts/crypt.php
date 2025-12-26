<?php
//$cryptedText = __encode($password, 'key'); //зашифровать
//$decryptedText = __decode($cryptedText, 'key'); //расшифровать
function __encode($unencoded, $key):string {
    $string = base64_encode($unencoded); // Переводим в base64
    $len = strlen($string);
    $chars = []; // Массив символов для новой строки

    for ($i = 0; $i < $len; $i++) {
        $hash = md5(md5($key . $string[$i]) . $key);
        $chars[] = $hash[3] . $hash[6] . $hash[1] . $hash[2];
    }

    return implode('', $chars);
}//Функция кодирования строки

function __decode($encoded, $key):string {
    $strofsym = "qwertyuiopasdfghjklzxcvbnm1234567890QWERTYUIOPASDFGHJKLZXCVBNM=";
    $len = strlen($strofsym);

    // Подготовка карты "шаблон => символ"
    $replaceMap = [];
    for ($x = 0; $x < $len; $x++) {
        $char = $strofsym[$x];
        $hash = md5(md5($key . $char) . $key);
        $pattern = $hash[3] . $hash[6] . $hash[1] . $hash[2];
        $replaceMap[$pattern] = $char;
    }

    $decodedStr = strtr($encoded, $replaceMap);

    return base64_decode($decodedStr);
}//Функция декодирования строки