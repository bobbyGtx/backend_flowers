<?php

function checkRateLimit($result,string $identifier, UserOpTypes $type, int|null $ttlSeconds=null) {
  global $rateLimit, $critErr, $errorLogFile, $rateLimitDir;
  include_once __DIR__ . '/../scripts/variables.php';
  include_once __DIR__ . '/../scripts/enums.php';
  $funcName = "checkRateLimit_func";

  if (empty($ttlSeconds)) $ttlSeconds = $rateLimit;

  $file = $rateLimitDir . '/' . $type->value . '.json';

  if (!file_exists($file)) file_put_contents($file, '{}', LOCK_EX);

  $key = hash('sha256', strtolower(trim($identifier)));
  $now = time();

  $fp = fopen($file, 'c+');
  if (!$fp){
    $timestamp = date('Y-m-d H:i:s');
    $error="[{$timestamp}] - {$critErr['openFileError']}. File:{$file}. FuncName:{$funcName}.";
    file_put_contents($errorLogFile, print_r($error, true), FILE_APPEND);
    goto endFunc;
  }// fail-safe

  flock($fp, LOCK_EX);

  $data = json_decode(stream_get_contents($fp), true) ?? [];

  if (count($data)>0) $data = array_filter($data, fn($t) => ($now - $t) < 3600);// очистка старых записей (1 часа)

  if (isset($data[$key]) && ($now - $data[$key]) < $ttlSeconds) {
    flock($fp, LOCK_UN);
    fclose($fp);
    $result['error'] = true;$result['code'] = 429;$result['message'] = $type->limitErrorMessage();$result['timer']= $ttlSeconds-($now - $data[$key]);goto endFunc;
  }

  $data[$key] = $now;

  ftruncate($fp, 0);
  rewind($fp);
  try {
    fwrite($fp, json_encode($data, JSON_THROW_ON_ERROR));
  } catch (JsonException $e) {
    $timestamp = date('Y-m-d H:i:s');
    $error="{[$timestamp]} - Unable to save file. ({$e}) File:{$file}. FuncName:{$funcName}.";
    file_put_contents($errorLogFile, print_r($error, true), FILE_APPEND);
  }

  flock($fp, LOCK_UN);
  fclose($fp);

  endFunc:
  return $result;
}

/**
 * @param string $identifier - email пользователя
 * @return int секунды до конца блокировки. Если 0 - но не заблокирован
 */
function checkLoginProtection(string $identifier): int {
  global $storageDir, $critErr, $errorLogFile;
  include_once __DIR__.'/../scripts/variables.php';
  $funcName = "checkLoginProtection_func";
  $file ="{$storageDir}/rate-limit/invalidLogin.json";

  if (!is_dir(dirname($file))) {
    mkdir(dirname($file), 0700, true);
  }

  if (!file_exists($file)) file_put_contents($file, '{}', LOCK_EX);

  $key = hash('sha256', strtolower(trim($identifier)));
  $now = time();

  $fp = fopen($file, 'c+');
  if (!$fp){
    $timestamp = date('Y-m-d H:i:s');
    $error="[{$timestamp}] - {$critErr['openFileError']}. File:{$file}. FuncName:{$funcName}.";
    file_put_contents($errorLogFile, print_r($error, true), FILE_APPEND);
    goto endFunc;//Если у нас ошибка открытия файла, логируем её и даем хороший ответ
  }// fail-safe
  flock($fp, LOCK_EX);

  $data = json_decode(stream_get_contents($fp), true) ?? [];

  flock($fp, LOCK_UN);
  fclose($fp);

  $entry = $data[$key] ?? [
    'attempts'     => 0,
    'lastAttempt'  => 0,
    'blockedUntil' => 0
  ];

  // если заблокирован
  if ($entry['blockedUntil'] > $now) return $entry['blockedUntil'] - $now;

  endFunc:
  return 0;
}

function registerFailedLogin(string $identifier): int {
  global $maxIncorrectLogins, $incorrectLoginsBlockTime, $rateLimitDir,$errorLogFile, $critErr;
  include_once __DIR__.'/../scripts/variables.php';
  $funcName = "registerFailedLogin_func";

  $file ="{$rateLimitDir}/invalidLogin.json";
  $maxAttempts = $maxIncorrectLogins;
  $blockSeconds = $incorrectLoginsBlockTime;
  $blockedTime = 0;//returned value

  $key = hash('sha256', strtolower(trim($identifier)));
  $now = time();

  $fp = fopen($file, 'c+');
  if (!$fp){
    $timestamp = date('Y-m-d H:i:s');
    $error="[{$timestamp}] - {$critErr['openFileError']}. File:{$file}. FuncName:{$funcName}.";
    file_put_contents($errorLogFile, print_r($error, true), FILE_APPEND);
    return 0;
  }// fail-safe
  flock($fp, LOCK_EX);

  $data = json_decode(stream_get_contents($fp), true) ?? [];

  $entry = $data[$key] ?? [
    'attempts'     => 0,
    'lastAttempt'  => 0,
    'blockedUntil' => 0
  ];

  $entry['attempts']++;
  $entry['lastAttempt'] = $now;

  if ($entry['attempts'] >= $maxAttempts) {
    $entry['blockedUntil'] = $now + $blockSeconds;
    $entry['attempts'] = 0; // сброс после блокировки
    $blockedTime = $blockSeconds;
  }

  $data[$key] = $entry;

  ftruncate($fp, 0);
  rewind($fp);
  try {
    fwrite($fp, json_encode($data, JSON_THROW_ON_ERROR));
  } catch (JsonException $e) {
    $timestamp = date('Y-m-d H:i:s');
    $error="{[$timestamp]} - Unable to save file. ({$e}) File:{$file}. FuncName:{$funcName}.";
    file_put_contents($errorLogFile, print_r($error, true), FILE_APPEND);
    $blockedTime = 0;//Если ошибка, возвращаем 0
  }

  flock($fp, LOCK_UN);
  fclose($fp);

  return $blockedTime;
}

function clearLoginProtection(string $identifier): void {
  global $storageDir, $critErr, $errorLogFile;
  include_once __DIR__.'/../scripts/variables.php';
  $funcName = "clearLoginProtection_func";
  $file ="{$storageDir}/rate-limit/invalidLogin.json";

  if (!file_exists($file)) return;
  $key = hash('sha256', strtolower(trim($identifier)));

  $fp = fopen($file, 'c+');
  if (!$fp){
    $timestamp = date('Y-m-d H:i:s');
    $error="[{$timestamp}] - {$critErr['openFileError']}. File:{$file}. FuncName:{$funcName}.";
    file_put_contents($errorLogFile, print_r($error, true), FILE_APPEND);
    return;
  }// fail-safe
  flock($fp, LOCK_EX);

  $data = json_decode(stream_get_contents($fp), true) ?? [];
  unset($data[$key]);

  ftruncate($fp, 0);
  rewind($fp);
  try{
    fwrite($fp, json_encode($data));
  }catch (JsonException $e){
    $timestamp = date('Y-m-d H:i:s');
    $error="{[$timestamp]} - Unable to save file. ({$e}) File:{$file}. FuncName:{$funcName}.";
    file_put_contents($errorLogFile, print_r($error, true), FILE_APPEND);
  }

  flock($fp, LOCK_UN);
  fclose($fp);
}