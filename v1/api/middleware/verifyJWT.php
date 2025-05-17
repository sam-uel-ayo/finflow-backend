<?php
use cUtils\cUtils;
use JWTHandler\JWTHandler;

function verifyJWT($options = []) {
    $options = array_merge([
        'requireAuth' => true,
        'allowedRoles' => []
    ], $options);

    try {
        // Try to get access token from cookie
        $token = $_COOKIE['access_token'] ?? null;

        // Attempt to refresh tokens if access token is missing and refresh exists
        if (!$token && isset($_COOKIE['refresh_token'])) {
            $newTokens = JWTHandler::refreshTokens();

            if (!$newTokens || empty($newTokens['access_token'])) {
                throw new Exception('Please login.', 401);
            }

            $token = $newTokens['access_token'];
        }

        // If access token still not available and auth is required
        if (!$token) {
            if ($options['requireAuth']) {
                throw new Exception('Authentication required, Please login', 401);
            }
            return null;
        }

        // Validate token
        $decoded = JWTHandler::validateToken($token);

        // Role-based access control
        if (!empty($options['allowedRoles'])) {
            $userRole = $decoded->data->role ?? 'customer';
            if (!in_array($userRole, $options['allowedRoles'])) {
                throw new Exception('Insufficient permissions', 403);
            }
        }

        return $decoded;
        
    } catch (Exception $e) {
        JWTHandler::clearAuthCookies();
        return cUtils::outputData(false, $e->getMessage(), [], true, $e->getCode() ?: 401);
    }
}
