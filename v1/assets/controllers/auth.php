<?php
namespace cAuth;

use cUtils\cUtils;
use mAuth\mAuth;
use JWTHandler\JWTHandler;
use Exception; 

class cAuth {

    // User login 
    /* To do
    *  - General Optimization
    *  - Mailer class 
    */
    public static function userLogin ($data) // Email and password
    {
        try {
            if ($data == null) {
                return cUtils::returnData(false, "Email and Password required", $data, true, 400);
            }

            $checkUser = json_decode(mAuth::checkUserMail($data->email)); // 
            if ($checkUser->status == false) {
                return json_decode(cUtils::returnData(false, $checkUser->message, $data, true, $checkUser->statusCode));
            }

            if (!password_verify($data->password, $checkUser->data->password)) {
                return json_decode(cUtils::returnData(false, "Invalid account details", $data, true, 401));
            } 
            // If password is correct and user can login 

            // $ip_address = $_SERVER['REMOTE_ADDR'];
            // $user_agent = $_SERVER['HTTP_USER_AGENT'];
            // mAuth::userAuthDetails($checkUser->data->id, $access_token, $expiry_time, $ip_address, $user_agent); // Store token and expiry and others in the database - This would later be in the redis
            
            // Set auth cookies
            JWTHandler::setAuthCookies($checkUser->data->user_id, [
                'email' => $data->email,
                'role' => 'user',
                
            ]);

            // Prepare response data
            $output = [
                'user_id' => $checkUser->data->user_id,
                'email' => $data->email,
            ];

            // Send Mail - later
            return json_decode(cUtils::returnData(true, "Logged In", $output, true, 200));
        } catch (Exception $e) {
            JWTHandler::clearAuthCookies();
            return cUtils::returnData(false, $e->getMessage(), $data, true, 500);
        }
    }
    // End of method


    // User Signup - register
    public static function userSignup ($data) // Email, Password, Confirm password, First name, Last name as an object for validation of correct input
    {
        try{
            if ($data == null) {
                return cUtils::returnData(false, "No data found", $data, true, 400);
            }

            // Email checks
            $checkMail = json_decode(mAuth::checkMail($data->email));
            if ($checkMail->status == false) {
                return json_decode(cUtils::returnData(false, $checkMail->message, $data, true, $checkMail->statusCode));
            }
            $validMail = json_decode(cUtils::validateEmail($data->email));
            if ($validMail->status == false) {
                return json_decode(cUtils::returnData(false, $validMail->message, $data, true, $validMail->statusCode));
            }

            // Password checks
            if (strlen($data->password) < 8) {
                return json_decode(cUtils::returnData(false, "Password cannot be less than 8 characters", $data, true, 400));
            }
            if (!preg_match('~[0-9]+~', $data->password)) {
                return json_decode(cUtils::returnData(false, "Password must contain a number", $data, true, 400));
            }
            if (!preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $data->password)) {
                return json_decode(cUtils::returnData(false, "Password must contain character", $data, true, 400));
            }
            if ($data->password != $data->cpassword) {
                return json_decode(cUtils::returnData(false, "Passwords does not match", $data, true, 400));
            }
            $password = password_hash($data->password, PASSWORD_DEFAULT);

            // Naming checks
            if (!preg_match("/^[a-zA-Z'-]+$/", $data->first_name)) {
                return json_decode(cUtils::returnData(false, "Invalid First name format", $data, true, 400));
            }
            if (!preg_match("/^[a-zA-Z'-]+$/", $data->last_name)) {
                return json_decode(cUtils::returnData(false, "Invalid Last name format", $data, true, 400));
            }

            // Create User_Id
            $user_id = cUtils::generateUserId($data->first_name, $data->last_name, $data->email);

            // Sign Up
            $signupUser = json_decode(mAuth::userSignup($data->email, $password, $user_id, $data->first_name, $data->last_name));
            if ($signupUser->status == false) {
                return json_decode(cUtils::returnData(false, $signupUser->message, $signupUser->data, true, $signupUser->statusCode));
            }

            // Set auth cookies
            JWTHandler::setAuthCookies($user_id, [
                'email' => $data->email,
                'role' => 'customer',
                
            ]);
            
            //Send Mail - Account created (Welcome)
            self::verifyEmail($data->email);
            //Send Mail - Verify email

            $output = ["user_id" => $user_id, "email" => $data->email];
            return json_decode(cUtils::returnData(true, "Signup successful", $output, true, 201));
        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), null, true, $e->getCode() ?: 500);
        }
    }
    // Method End


    // Verify Email 
    public static function verifyEmail($data)
    {
        try {
            if ($data = null){
                return json_decode(cUtils::returnData(true, "Data not found", $data, true, 400));
            }

            $OTP = cUtils::generateEmailOTP();

            // Set in redis DB later (Instead of cookie)
            $cookieOptions = [
                'expires' => time() + 300, // 5 minutes
                'path' => '/',
                'domain' => "finflow.samayo.com.ng",
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ];
            setcookie('email_verify_OTP', $OTP, $cookieOptions);
            // Send Email - OTP Sent
            return json_decode(cUtils::returnData(true, "Verification Email Sent", $data, true, 200));
        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), null, true, $e->getCode() ?: 500);
        }
    }
    // End of method


    // Verify Email callback
    public static function verifyEmailCallback ($data)
    {
        try {
            if ($data == null){
                throw new Exception("Data not found", 400);
            }

            $OTP = $_COOKIE['email_verify_OTP'];
            if (!$OTP){
                throw new Exception("Expired Token", 400);
            }
            if ($OTP !== $data->otp){
                throw new Exception("Invalid OTP", 400);
            }

            // Update verified status
            $isVerified = json_decode(mAuth::verifyEmail($data->email));
            if ($isVerified->status == false){
                throw new Exception($isVerified->message, $isVerified->statusCode);
            } 

            return json_decode(cUtils::returnData(true, $isVerified->message, $data, true, $isVerified->statusCode));
        } catch (Exception $e) {
            return json_decode(cUtils::returnData(false, $e->getMessage(), $data, true, $e->getCode() ?: 500));
        }
    }


    // Get User Personal information
    public static function userProfile ($token)
    {
        try {
            if ($token == null) {
                return cUtils::returnData(false, $token, true);
            }

            $getProfile = json_decode(mAuth::userProfile($token)); // 
            if ($getProfile->status == false) {
                return json_decode(cUtils::returnData(false, $getProfile->message, $token, true));
            }

            return json_decode(cUtils::returnData(true, $getProfile->message, $getProfile->data, true));

        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), $token, true);
        }
    }
    // Method End


    // Edit/Add User infomation
    public static function editProfile ($data) // firstname , lastname, phonenumber, dob, user_id
    {
        try {
            if ($data == null) {
                return cUtils::returnData(false, "Input not found", $data, true, 400);
            }

            if (!preg_match("/^[a-zA-Z'-]+$/", $data->first_name)) {
                return json_decode(cUtils::returnData(false, "Invalid First name format", $data, true, 400));
            }
            if (!preg_match("/^[a-zA-Z'-]+$/", $data->last_name)) {
                return json_decode(cUtils::returnData(false, "Invalid Last name format", $data, true, 400));
            }
            if (!preg_match("/^\+?[\d\s\-\(\)]{10,}$/", $data->phone_number)) {
                return json_decode(cUtils::returnData(false, "Invalid Phone number format", $data, true, 400));
            }

            $editProfile = json_decode(mAuth::editProfile($data->first_name, $data->last_name, $data->dob, $data->phone_number, $data->user_id)); 
            if ($editProfile->status == false) {
                return json_decode(cUtils::returnData(false, $editProfile->message, $editProfile->data, true, $editProfile->statusCode));
            }

            return json_decode(cUtils::returnData(true, $editProfile->message, $editProfile->data, true, $editProfile->statusCode));
        } catch (Exception $e) {
            return json_decode(cUtils::returnData(false, $e->getMessage(), $data, true, 500));
        }
    }
    // Method End


    // Change password
    public static function changePassword ($data) // email, oldpassword, password, confirm password
    {
        try {
            if ($data == null) {
                throw new Exception("Data not found", 400);
            }

            $checkUser = json_decode(mAuth::resetInfo($data->email)); 
            if (!password_verify($data->oldpassword, $checkUser->data->password)) {
                throw new Exception("Old Password Incorrect", 400);
            }
        
            // Check if the old password matches the new password
            if (password_verify($data->password, $checkUser->data->password)) {
                throw new Exception("New password cannot be the same as the old password", 400);
            }
            
            if (strlen(trim($data->password)) < 8) {
                throw new Exception("Password cannot be less than 8 characters", 400);
            }

            if (!preg_match('~[0-9]+~', trim($data->password))) {
                throw new Exception("Password must contain a number", 400);
            }
            if (!preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', trim($data->password))) {
                throw new Exception("Password must contain character", 400);
            }
            if (trim($data->password != trim($data->confirm_password))) {
                throw new Exception("Password do not match", 400);
            }
            $password = password_hash($data->password, PASSWORD_DEFAULT);

            $changePassword = json_decode(mAuth::changePassword ($data->email, $password));
            if ($changePassword->status == false) {
                throw new Exception($changePassword->message, $changePassword->statusCode);
            }
            return json_decode(cUtils::returnData(true, "Password set", $data, true, 200));
        } catch (Exception $e) {
            return json_decode(cUtils::returnData(false, $e->getMessage(), $data, true, $e->getCode() ?: 500));
        }
    }
    // Method End


    // Reset password
    public static function resetPassword ($data) // email
    {
        try {
            if ($data == null) {
                throw new Exception("Data not found", 400);
            }

            $OTP = bin2hex(random_bytes(3));
            $hashOTP = password_hash($OTP, PASSWORD_DEFAULT);
            $expiryTime = time() + 900;

            // Set in redis later
            $resetPassword = json_decode(mAuth::resetPassword($data->email, $hashOTP, $expiryTime));
            if ($resetPassword->status == false) {
                throw new Exception($resetPassword->message, $resetPassword->statusCode);
            }
            echo $OTP;
            // Send Mail
            //Mailer::resetPassword($data->email, $OTP); Send link - $resetUrl = "https://mart.sheda.ng/reset-password-callback?email=" . urlencode($data->email) . "&otp=" . urlencode($OTP);
            return json_decode(cUtils::returnData(true, $resetPassword->message . " Check the mail sent to your email address to set new password.", $data, true, 200));
        } catch (Exception $e) {
            return json_decode(cUtils::returnData(false, $e->getMessage(), $data, true, $e->getCode() ?: 500));
        }
    }
    // End of method


    // Change password
    public static function resetPasswordCallback ($data) // email, OTP, password, confirm password
    {
        try {
            if ($data == null) {
                throw new Exception("Data not found", 400);
            }

            $checkMailReset = json_decode(mAuth::resetInfo($data->email));
            $currentTime = time();
            if ($currentTime > $checkMailReset->data->reset_expiry) { 
                throw new Exception("Expired OTP", 400);
            }
            if (!password_verify($data->OTP, $checkMailReset->data->reset_OTP)) {
                throw new Exception("Invalid OTP", 400);
            }


            if (strlen(trim($data->password)) < 8) {
                throw new Exception("Password cannot be less than 8 characters", 400);
            }
            if (password_verify(trim($data->password), $checkMailReset->data->password)){
                throw new Exception("Password cannot be the same as Old password", 400);
            }
            if (!preg_match('~[0-9]+~', trim($data->password))) {
                throw new Exception("Password must contain a number", 400);
            }
            if (!preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', trim($data->password))) {
                throw new Exception("Password must contain character", 400);
            }
            if (trim($data->password != trim($data->confirm_password))) {
                throw new Exception("Password do not match", 400);
            }
            $password = password_hash($data->password, PASSWORD_DEFAULT);

            $changePassword = json_decode(mAuth::changePassword ($data->email, $password));
            if ($changePassword->status == false) {
                throw new Exception($changePassword->message, $changePassword->statusCode);
            }
            return json_decode(cUtils::returnData(true, "Password set", $data, true, 200));
        } catch (Exception $e) {
            return json_decode(cUtils::returnData(false, $e->getMessage(), $data, true, $e->getCode() ?: 500));
        }
    }
    // Method End 
}
