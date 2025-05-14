<?php
require '../../assets/header.php';

use cUtils\cUtils;
use cAuth\cAuth;

$requiredKeys = ['email'];
$optionalKeys = [];

cUtils::validatePayload($requiredKeys, $data);
$data = cUtils::arrayToObject($data);

//Logic here
$reset = cAuth::resetPassword($data);
if ($reset->status === false){
    return cUtils::outputData(false, $reset->message, $reset->data, true, $reset->statusCode);
}
return cUtils::outputData(true, $reset->message, $reset->data, true, $reset->statusCode);
