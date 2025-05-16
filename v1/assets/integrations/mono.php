<?php
namespace Mono;


use cUtils\cUtils;

class Mono
{
    private static $BASE_URL;
    private static $MONO_TOKEN;
    private static $INIT_RE_URL; // Redirect Url for initializations

    private static function init()
    {
        if (self::$BASE_URL === null) {
            self::$BASE_URL    = cUtils::config('MONO_BASE_URL');
            self::$MONO_TOKEN  = cUtils::config('MONO_SEC_KEY');
            self::$INIT_RE_URL = cUtils::config('INIT_RE_URL');
        }
    }

    // Initiate linking an account.
    public static function initialize($name, $email, $user_id)
    {
        self::init();

        // Prepare the payload
        $payload = json_encode([
            "customer" => [
                "name" => $name,
                "email" => $email
            ],
            "scope" => "auth",
            "meta" => [
                "ref" => "user".$user_id.$email . $user_id
            ],
            "redirect_url" => self::$INIT_RE_URL
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::$BASE_URL . "initiate",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "content-type: application/json",
                "mono-sec-key: ". self::$MONO_TOKEN
            ],
            CURLOPT_POSTFIELDS => $payload
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        // Handle cURL error
        if ($response === false || $curlError) {
            return cUtils::returnData(true, null, 'Failed to initiate: ' . $curlError, false, $httpCode ?: 500);
        }

        $decoded = json_decode($response, true);
        $success = $httpCode >= 200 && $httpCode < 300;
        return cUtils::returnData($success, $success ? 'Initiation successfully.' : 'Failed to Initiate', $decoded, true, $httpCode);
    }



    // Get account account id - to confirm account linking - Final authentication
    public static function auth($code)
    {
        self::init();

        // Prepare the payload
        $payload = json_encode([
            "code" => $code
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::$BASE_URL . "auth",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "accept: application/json",
                "mono-sec-key: ". self::$MONO_TOKEN
            ],
            CURLOPT_POSTFIELDS => $payload
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        // Handle cURL error
        if ($response === false || $curlError) {
            return cUtils::returnData(true, null, 'Failed to add account: ' . $curlError, false, $httpCode ?: 500);
        }

        $decoded = json_decode($response, true);
        $success = $httpCode >= 200 && $httpCode < 300;
        return cUtils::returnData($success, $success ? 'Account added successfully.' : 'Failed to add account', $decoded, true, $httpCode);
    }
}