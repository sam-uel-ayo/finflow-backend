<?php
require '../../assets/header.php';
require_once '../middleware/verifyJWT.php';

// Authenticate user 
$user = verifyJWT();

use cUtils\cUtils;
use cAuth\cAuth;


// Convert GET array to object
$data = (object) $_GET;
$data->user_id = $user->sub;

// Logic here
$status = cAuth::onboardingStatus($data);
if ($status === false) {
    return cUtils::outputData(false, "Failed to fetch onboarding status", null, true, 500);
}
return cUtils::outputData(true, $status->message, $status->data, true, $status->statusCode);
