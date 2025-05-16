<?php
require '../../assets/header.php';
require_once '../middleware/verifyJWT.php';

// Authenticate user 
$user = verifyJWT();

use cUtils\cUtils;
use cAccounts\cAccounts;

// Convert GET array to object
$data = (object) $_GET;
$data->user_id = $user->sub;

//Logic here
$initiate = cAccounts::initialize($data);
if ($initiate === false){
    return cUtils::outputData(false, $initiate->message, $initiate->data, true, $initiate->statusCode);
}
return cUtils::outputData(true, $initiate->message, $initiate->data, true, $initiate->statusCode);
