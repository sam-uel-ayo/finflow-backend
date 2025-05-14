<?php
require '../../assets/header.php';
require_once '../middleware/verifyJWT.php';

// Authenticate user 
$user = verifyJWT();


use cUtils\cUtils;
use cAuth\cAuth;

$requiredKeys = ['email', 'otp'];
$optionalKeys = [];

cUtils::validatePayload($requiredKeys, $data);
$data = cUtils::arrayToObject($data);

$data->user_id = $user->sub;

//Logic here
$isverified = cAuth::verifyEmailCallback($data);
if ($isverified->status === false){
    return cUtils::outputData(false, $isverified->message, $isverified->data, true, $isverified->statusCode);
}
return cUtils::outputData(true, $isverified->message, $isverified->data, true, $isverified->statusCode);

