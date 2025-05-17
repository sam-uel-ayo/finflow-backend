<?php
namespace cUtils;
require_once __DIR__ . '/../../vendor/autoload.php';


class cUtils {

    // Verify payload
    public static function validatePayload(array $requiredKeys, array $data, array $optionalKeys = [])
    {
        $errors = [];

        // All the keys we allow
        $validKeys = array_merge($requiredKeys, $optionalKeys);

        // Check for any totally unknown keys
        $invalidKeys = array_diff(array_keys($data), $validKeys);
        foreach ($invalidKeys as $key) {
            $errors[] = "$key is not a valid input field";
        }

        // Check each required key for presence and non-empty value
        foreach ($requiredKeys as $key) {
            // coalesce to empty string if not set, then cast to string and trim
            $value = trim((string) ($data[$key] ?? ''));
            if ($value === '') {
                $errors[] = ucfirst($key) . ' is required';
            }
        }

        if (!empty($errors)) {
            self::outputData(false, "Payload Error", $errors, true, 400);
        }
    }
    // End verify payload


    //  class
    public static function validateGetPayload(array $requiredKeys, array $data, array $optionalKeys = [])
    {
        $errors = [];

        // Combine all allowed keys
        $validKeys = array_merge($requiredKeys, $optionalKeys);

        // Find and report any unexpected keys
        $invalidKeys = array_diff(array_keys($data), $validKeys);
        foreach ($invalidKeys as $key) {
            $errors[] = "Invalid parameter: '$key'";
        }

        // Check for required fields with non-empty values
        foreach ($requiredKeys as $key) {
            $value = trim((string)($data[$key] ?? ''));
            if ($value === '') {
                $errors[] = ucfirst($key) . " is required";
            }
        }

        // If any error is found, output and halt
        if (!empty($errors)) {
            self::outputData(false, "Parameter Error", $errors, true, 400);
        }
    }




    // Output data to user/ frontend
    public static function outputData($status=false, $message=null, $data=null, $exit =false, $httpStatus) 
    {
        http_response_code($httpStatus);
        if ($data == null) {
            $data = array();
        }
        $output = array(
            'status' => $status,
            'message' => $message,
            'data' => $data,
            "statusCode" => $httpStatus
        );

        header('Content-Type: application/json');
        echo json_encode($output);

        foreach (get_defined_vars() as $var) {
            unset($var);
        }

        if ($exit == true) {
            exit();
        }
    }


    // return data to be used in program
    public static function returnData($status= false, $message=null, $data=array(), $exit = false, $httpStatus) 
    {
        $output = array(
            'status' => $status,
            'message' => $message,
            'data' => $data,
            "statusCode" => $httpStatus
        );
        return json_encode($output);

        foreach (get_defined_vars() as $var) {
            unset($var);
        }

        if ($exit == true) {
            exit();
        }
    }

    public static function arrayToObject($data)
    {
    if (is_array($data)) {
        return json_decode(json_encode($data));
    }
    return null; // Return null if $data is not an array
    }

    public static function objectToArray($data)
    {
        if (is_object($data) || is_array($data)) {
            return json_decode(json_encode($data), true);
        }
        return null; // Return null if $data is not an object or array
    }


    // validate email
    public static function validateEmail($email=null)
    {
        if ($email==null) {
            return self::returnData(false, "Email data not found", $email, true, 400);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            list($user, $domain) = explode('@', $email);
            if (checkdnsrr($domain, "MX")) {
                // echo "The email address is valid and the domain has an MX record.";
                return self::returnData(true, "The email address is valid and the domain has an MX record.", $email, true, 200);
            } else {
                // echo "The email address is valid, but the domain does not have an MX record.";
                return self::returnData(false, "The email address is valid, but the domain does not have an MX record.", $email, true, 400);
            }
        } else {
            return self::returnData(false, "The email address is not valid.", $email, true, 400);
        }
    }
    // end validate email


    // Generate User_Id
    public static function generateUserId($firstName, $lastName, $email) {
        $salt = bin2hex(random_bytes(4));; // Replace with a secret value
        $data = $firstName . $lastName . $email . $salt;
        $hash = hash('sha256', $data);
        $userId = substr($hash, 0, 4) . substr($hash, -4);
        return strtoupper($userId);
    }
    // End of method


    // Generate Email OTP
    public static function generateEmailOTP() {
        $randomOTP = bin2hex(random_bytes(4));
        $hash = hash('sha256', $randomOTP);
        $OTP = substr($hash, 0, 4) . substr($hash, -4);
        return strtoupper($OTP);
    }
    // End of method


    // Get config infomation
    public static function config(string $key, $default = null) {
        return getenv($key) ?: $_ENV[$key] ?? $default;
    }
    // End of method


    // Encrypt a string (e.g., account_id)
    public static function encryptString($input) {
        $key = self::config('ENC_KEY');
        $cipher = 'AES-128-CBC';
        $iv = substr(hash('sha256', $key), 0, 16);

        $encrypted = openssl_encrypt($input, $cipher, $key, 0, $iv);
        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }
    // End of method

    // Decrypt a string back to original
    public static function decryptString($encrypted) {
        $key = self::config('ENC_KEY');
        $cipher = 'AES-128-CBC';
        $iv = substr(hash('sha256', $key), 0, 16);

        $decoded = base64_decode(strtr($encrypted, '-_', '+/'));
        return openssl_decrypt($decoded, $cipher, $key, 0, $iv);
    }
    // End of method


}