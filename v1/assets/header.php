<?php
session_start();

// === CORS & Preflight ===
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit();
}


// === Load Config ===
require_once('connect.php');
require_once('envDecoder.php');

use Env\Env;

// === Choose Environment File Dynamically === 
$envFile = __DIR__ . '/.env.local';
if ($_SERVER['HTTP_HOST'] === 'finflow.samayo.com.ng') {
    $envFile = __DIR__ . '/.env.production';
}

Env::load($envFile);

// === Toggle PHP Errors Based on Environment ===
if (Env::get('APP_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// === Decode Input ===
$raw = file_get_contents('php://input');
$data = [];

if (!empty($raw)) {
    $decoded = base64_decode($raw, true);
    if ($decoded !== false && base64_encode($decoded) === $raw) {
        $data = json_decode($decoded, true) ?? [];
    } else {
        $data = json_decode($raw, true) ?? [];
    }
}
