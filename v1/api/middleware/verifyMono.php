<?php
use cUtils\cUtils;

function verifyMono(): void {
    // Replace with your actual webhook secret
    $expectedSecret = cUtils::config('MONO_WEBHOOK_SECRET');

    // Get the incoming header
    $receivedSecret = $_SERVER['HTTP_MONO_WEBHOOK_SECRET'] ?? '';

    // Compare secrets securely
    if (!hash_equals($expectedSecret, $receivedSecret)) {
        error_log("Not secure");
        http_response_code(401);
        echo json_encode([
            'status' => false,
            'message' => 'Unauthorized: Invalid webhook secret'
        ]);
        exit;
    }
}

