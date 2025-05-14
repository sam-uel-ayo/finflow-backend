<?php
require '../../assets/header.php';
require_once '../middleware/verifyJWT.php';

// Authenticate user 
$user = verifyJWT();
if (!is_object($user)) {
    // $user contains the error response already formatted
    echo json_encode($user);
    exit;
}
use cUtils\cUtils;
use cAuth\cAuth;

$requiredKeys = ['email', 'old_password', 'password', 'confirm_password'];
$optionalKeys = [];

cUtils::validatePayload($requiredKeys, $data);
$data = cUtils::arrayToObject($data);

//Logic here
$changePassword = cAuth::changePassword($data);
if ($changePassword->status === false){
    return cUtils::outputData(false, $changePassword->message, $changePassword->data, true, $changePassword->statusCode);
}
return cUtils::outputData(true, $changePassword->message, $changePassword->data, true, $changePassword->statusCode);

