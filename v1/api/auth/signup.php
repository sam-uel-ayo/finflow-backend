<?php
require '../../assets/header.php';
use cUtils\cUtils;
use cAuth\cAuth;


$requiredKeys = ['first_name', 'last_name', 'email', 'password', 'cpassword'];
$optionalKeys = [];

cUtils::validatePayload($requiredKeys, $data);
$data = cUtils::arrayToObject($data);

//Logic here
$signup = cAuth::userSignup($data);
if ($signup->status === false){
    return cUtils::outputData(false, $signup->message, $signup->data, true, $signup->statusCode);
}
return cUtils::outputData(true, $signup->message, $signup->data, true, $signup->statusCode);

