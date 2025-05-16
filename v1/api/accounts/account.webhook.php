<?php
require '../../assets/header.php';
require_once '../middleware/verifyMono.php';

use cUtils\cUtils;
use cAccounts\cAccounts;

// Authenticate Mono
$user = verifyMono();

$data = cUtils::arrayToObject($data);

// Initialize default response
$response = [
    'status' => false,
    'message' => 'Invalid payload',
    'data' => null
];

// Validate payload
if ($data && isset($data->event) && isset($data->data)) {
    switch ($data->event) {
        case 'mono.events.account_connected':
            $response = cAccounts::handleAccountConnected($data->data);
            error_log("account_connected");
            break;

        case 'mono.events.account_updated':
            $response = cAccounts::handleAccountUpdated($data->data);
            error_log("account_updated");
            break;

        case 'mono.events.account_reauthorized':
            $response = cAccounts::handleAccountReauthorized($data->data);
            error_log("account_reauthorized");
            break;

        default:
            $response = [
                'status' => false,
                'message' => 'Unhandled event type',
                'data' => null
            ];
            error_log("Unhandled event type: " . $data->event);
            break;
    }
} else {
    error_log("Invalid or missing payload");
}

// Always return HTTP 200 OK
http_response_code(200);
$responsePayload = [
    'success' => $response->status,
    'message' => $response->message,
    'data' => $response->data
];

error_log("Webhook response: " . json_encode($responsePayload));
exit;
