<?php
require '../../assets/header.php';
use cUtils\cUtils;
use cAuth\cAuth;


$requiredKeys = ['email', 'password'];
$optionalKeys = [];

cUtils::validatePayload($requiredKeys, $data);
$data = cUtils::arrayToObject($data);

//Logic here
$login = cAuth::userLogin($data);
if ($login->status === false){
    return cUtils::outputData(false, $login->message, $login->data, true, $login->statusCode);
}
return cUtils::outputData(true, $login->message, $login->data, true, $login->statusCode);

