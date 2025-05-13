<?php
session_start();


header("Access-Control-Allow-Origin: *");  
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// If the request is an OPTIONS request, respond with a 200 status to pass the preflight check
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}
require_once('connect.php');


$data = file_get_contents('php://input');
if(base64_encode(base64_decode($data)) == $data){
    $data = !empty($data) ? json_decode(base64_decode($data), true) : [];
}else{
    $data = !empty($data) ? json_decode($data, true) : [];
}
