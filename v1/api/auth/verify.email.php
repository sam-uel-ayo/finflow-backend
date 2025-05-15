<?php
require '../../assets/header.php';
require_once '../middleware/verifyJWT.php';

// Authenticate user 
$user = verifyJWT();

use cUtils\cUtils;
use cAuth\cAuth;

$requiredKeys = ['name','email'];
$optionalKeys = [];

cUtils::validatePayload($requiredKeys, $data);
$data = cUtils::arrayToObject($data);

//Logic here
$verify = cAuth::verifyEmail($data->name, $data->email);
if ($verify->status === false){
    return cUtils::outputData(false, $verify->message, $verify->data, true, $verify->statusCode);
}
return cUtils::outputData(true, $verify->message, $verify->data, true, $verify->statusCode);

