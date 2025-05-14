<?php
namespace JWTHandler;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use cUtils\cUtils;
use Exception;

class JWTHandler
{
    private static $accessSecret;
    private static $refreshSecret;
    private static $issuer;
    private static $audience;
    private static $domain;

    public static $accessTokenExpiry = 3600;    // 1 hour
    public static $refreshTokenExpiry = 604800; // 7 days

    private static $initialized = false;

    // Initializes the JWTHandler with config values from cUtils.
    private static function init()
    {
        if (self::$initialized) return;

        self::$accessSecret  = cUtils::config('JWT_ACCESS_SECRET');
        self::$refreshSecret = cUtils::config('JWT_REFRESH_SECRET');
        self::$issuer        = cUtils::config('JWT_ISSUER', 'finflow.samayo.com.ng');
        self::$audience      = cUtils::config('JWT_AUDIENCE', 'finflow.samayo.com.ng');
        self::$domain        = cUtils::config('COOKIE_DOMAIN', $_SERVER['HTTP_HOST']);

        self::$initialized = true;
    }
    // End of method: Configuration values loaded


    /**
     * Generates a signed JWT access token with user ID and optional user data.
     *
     * @param int|string $userId
     * @param array $userData
     * @return string JWT access token
     */
    public static function generateAccessToken($userId, $userData = [])
    {
        self::init(); 
        
        $payload = [
            "iss"  => self::$issuer,
            "aud"  => self::$audience,
            "iat"  => time(),
            "exp"  => time() + self::$accessTokenExpiry,
            "sub"  => $userId,
            "data" => $userData,
            "type" => "access"
        ];

        return JWT::encode($payload, self::$accessSecret, 'HS256');
    }
    // End of method: Access token created and returned


    /**
     * Generates a signed JWT refresh token using the user ID.
     *
     * @param int|string $userId
     * @return string JWT refresh token
     */
    public static function generateRefreshToken($userId)
    {        
        self::init(); 
        
        $payload = [
            "iss" => self::$issuer,
            "aud" => self::$audience,
            "iat" => time(),
            "exp" => time() + self::$refreshTokenExpiry,
            "sub" => $userId,
            "jti" => bin2hex(random_bytes(16)),
            "type" => "refresh"
        ];

        return JWT::encode($payload, self::$refreshSecret, 'HS256');
    }
    // End of method: Refresh token created and returned


    /**
     * Sets secure HTTP-only cookies for access and refresh tokens.
     *
     * @param int|string $userId
     * @param array $userData
     * @return array Contains access_token and refresh_token
     */
    public static function setAuthCookies($userId, $userData = [])
    {
        self::init(); 

        try {
            $accessToken  = self::generateAccessToken($userId, $userData);
            $refreshToken = self::generateRefreshToken($userId);

            $isSecure = cUtils::config('APP_ENV') === 'production';

            $accessCookieOptions = [
                'expires'  => time() + self::$accessTokenExpiry,
                'path'     => '/',
                'domain'   => self::$domain,
                'secure'   => $isSecure,
                'httponly' => $isSecure, // stricter in production
                'samesite' => $isSecure ? 'Strict' : 'Lax',
            ];

            $refreshCookieOptions = $accessCookieOptions;
            $refreshCookieOptions['expires'] = time() + self::$refreshTokenExpiry;

            setcookie('access_token', $accessToken, $accessCookieOptions);
            setcookie('refresh_token', $refreshToken, $refreshCookieOptions);

            return [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
            ];
        } catch (Exception $e) {
            error_log('JWTHandler Error: ' . $e->getMessage());
            return [
                'access_token'  => null,
                'refresh_token' => null,
            ];
        }
    }
    // End of method: Tokens stored in cookies and returned


    /**
     * Validates a JWT and checks its type (access or refresh).
     *
     * @param string $token
     * @param bool $isRefresh
     * @return object Decoded JWT payload
     * @throws Exception
     */
    public static function validateToken($token, $isRefresh = false)
    {
        self::init(); 
        
        $secret = $isRefresh ? self::$refreshSecret : self::$accessSecret;
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));

        $expectedType = $isRefresh ? 'refresh' : 'access';
        if (($decoded->type ?? null) !== $expectedType) {
            throw new Exception("Invalid token type", 401);
        }

        return $decoded;
    }
    // End of method: Token validated and returned


    /**
     * Uses refresh token from cookie to generate new access and refresh tokens.
     *
     * @return array New access and refresh tokens
     * @throws Exception
     */
    public static function refreshTokens()
    {
        $refreshToken = $_COOKIE['refresh_token'] ?? null;
        if (!$refreshToken) {
            throw new Exception('Refresh token missing', 401);
        }

        $decoded = self::validateToken($refreshToken, true);

        // (Optional) Validate jti if using a token blacklist

        $userId   = $decoded->sub;
        $userData = (array) ($decoded->data ?? []);

        return self::setAuthCookies($userId, $userData);
    }
    // End of refreshTokens(): New tokens generated and cookies updated


    /**
     * Clears the authentication cookies from the client.
     */
    public static function clearAuthCookies()
    {
        self::init();

        $past = time() - 3600;
        $isSecure = cUtils::config('APP_ENV') === 'production';

        $cookieOptions = [
            'expires'  => $past,
            'path'     => '/',
            'domain'   => self::$domain,
            'secure'   => $isSecure,
            'httponly' => $isSecure,
            'samesite' => $isSecure ? 'Strict' : 'Lax',
        ];

        setcookie('access_token', '', $cookieOptions);
        setcookie('refresh_token', '', $cookieOptions);
    }
    // End of clearAuthCookies(): Cookies expired and removed
}
