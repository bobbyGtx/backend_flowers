<?php
function checkConfirmationToken($result,mysqli $link, string $token, UserOpTypes $operation){
  global $errors, $opTokenRegEx, $dbError, $opErrors;
  include_once 'enums.php';
  include_once 'variables.php';
  $funcName = 'checkConfirmationToken_func';
  if ($result['error']){goto endFunc;}
  if (!$link) {$result['error']=true; $result['code']=500; $result['message'] = $errors['dbConnectInterrupt'] . "($funcName)"; goto endFunc;}
  if (!preg_match($opTokenRegEx,$token)){
    $result['error']=true; $result['code'] = 400;$result['message'] = 'Verification token has an invalid format.'; goto endFunc;
  }

  $idFieldName = 'id';
  $user_IdFieldName = 'user_id';
  $newEmailFieldName = 'newEmail';
  $tokenFieldName = $operation->tokenField();
  $createdAtFieldName = $operation->timeField();

  $sql = "
  SELECT $idFieldName, $user_IdFieldName, $newEmailFieldName,$tokenFieldName as token,$createdAtFieldName as createdAt
  FROM user_operations
  WHERE $tokenFieldName = ?";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $response = $stmt->get_result();
    $numRows = $response->num_rows;
    $stmt->close();
  } catch (Exception $e) {$eMessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($eMessage))";goto endFunc;}
  if ($numRows===0){$result['error']=true;$result['code']=400;$result['message']=$opErrors['linkNotValid'];goto endFunc;}
  $row = $response->fetch_assoc();

  if (!isset($row[$idFieldName]) || !isset($row[$user_IdFieldName]) || ($operation===UserOpTypes::changeEmail && !isset($row[$newEmailFieldName]))){
    $result['error']=true; $result['code']=500; $result['message'] = $dbError['unexpResponse'];goto endFunc;
  }
  $result['opRecord'] = $row;

  if ($operation->tokenLifeTime()>0 & ($row['createdAt'] + $operation->tokenLifeTime())<time()){
    $result['error']=true; $result['code']=403;
    $result['message'] = $opErrors['opTokenOutOfDate'];
    goto endFunc;
  }
  //file_put_contents(__DIR__ . '/debug.log', print_r($row, true), FILE_APPEND);
  endFunc:
  return $result;
}

function clearConfirmationField($result,mysqli $link,$record_id, UserOpTypes $operation){
  global $critErr, $errors;
  include_once 'enums.php';
  include_once 'variables.php';
  $funcName = 'clearConfirmationField_func';

  if ($result['error'] && $result['code']!==403 && $result['code']!==406)goto endFunc;//В этой функции допустим $result['error'] при $result['code']=403 и 406;
  if (empty($record_id)){
    $result['error']=true; $result['code']=500;
    $result['message'] = $critErr['recordIdNotFound'];
    goto endFunc;
  }

  $tokenFieldName = $operation->tokenField();
  $createdAtFieldName = $operation->timeField();

  if ($operation === UserOpTypes::changeEmail) $fields[]='newEmail';

  $sql = "UPDATE user_operations ";
  $sql.=$operation === UserOpTypes::changeEmail?"SET $tokenFieldName = NULL, $createdAtFieldName = NULL, newEmail = NULL ":"SET $tokenFieldName = NULL, $createdAtFieldName = NULL ";
  $sql.= "WHERE id = ?";
  try {
    $stmt = $link->prepare($sql);
    if (!$stmt) {throw new Exception($link->error);}
    $stmt->bind_param('i', $record_id);
    $stmt->execute();
    $numRows = $stmt->affected_rows;
    $stmt->close();
  } catch (Exception $e) {$emessage = $e->getMessage();$result['error'] = true;$result['code'] = 500;$result['message'] = $errors['selReqRejected'] . "($funcName)($emessage))";goto endFunc;}

  if ($numRows <> 1){
    $errorDump=$errors['updReqNothing']."($funcName). Table[user_operations] Record ID = $record_id ";
    file_put_contents(__DIR__ . '../logs/debug.log', print_r($errorDump, true), FILE_APPEND);
  }

  endFunc:
  return $result;
}
