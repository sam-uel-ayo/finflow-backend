<?php
namespace mAuth;

use Database\Database;
use cUtils\cUtils;
use PDO;
use Exception;
use PDOException;


class mAuth {

    //  Check and get details with email - Login/change password 
    public static function checkUserMail ($email)
    {
        try {
            $query = "SELECT user_id, password,is_verified, kyc_status, has_transaction_pin FROM users WHERE email = :email";
            $stmt = Database::getConnection()->prepare($query);

            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            if(!$stmt->execute()){
                throw new Exception("Failed");
            }

            if ($stmt->rowCount() < 1){
                return cUtils::returnData(false, "Invalid account details", $email, true, 400); // Email doesn't exist
            }

            $results = $stmt->fetch(PDO::FETCH_ASSOC);
            return cUtils::returnData(true,"Logged In", $results, true, 200); // Results is user_id and password

        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), $email, true, 500);
        }
    } 
    // End of method 


    // Check if email doesn't exist and can be used
    public static function checkMail($email)
    {
        try {
            $query = "SELECT user_id FROM users WHERE email = :email";
            $stmt = Database::getConnection()->prepare($query);

            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Select failed: " . ($errorInfo[2] ?? "Unknown error"), 500);
            }

            if ($stmt->rowCount() > 0){
                return cUtils::returnData(false, "Email already registered. Please login", $email, false, 400); // Email exist - can't use
            } else {
                return cUtils::returnData(true, null, $email, false, 200); // Email doesn't exist - can use
            }

        } catch (PDOException $pdoEx) {
            return cUtils::returnData(false, "Database error: " . $pdoEx->getMessage(), null, true, 500);
        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), null, true, $e->getCode() ?: 500);
        }
    } 
    // End of method


    // User Signup
    public static function userSignup($email, $password, $user_id, $firstname, $lastname, $auth=null)
    {
        $conn = Database::getConnection();
        try {
            $conn->beginTransaction();
    
            $stmt1 = $conn->prepare("INSERT INTO users (email, password, user_id, auth_type) VALUES (:email, :password, :user_id, IFNULL(:auth, DEFAULT(auth_type)))");
            $stmt1->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt1->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt1->bindParam(':user_id', $user_id, PDO::PARAM_STR);
            $stmt1->bindParam(':auth', $auth, PDO::PARAM_STR);
            if (!$stmt1->execute()) {
                throw new Exception("Failed to insert into users");
            }
    
            $stmt2 = $conn->prepare("INSERT INTO users_details (user_id, first_name, last_name) VALUES (:user_id, :first_name, :last_name)");
            $stmt2->bindParam(':user_id', $user_id, PDO::PARAM_STR);
            $stmt2->bindParam(':first_name', $firstname, PDO::PARAM_STR);
            $stmt2->bindParam(':last_name', $lastname, PDO::PARAM_STR);
            if (!$stmt2->execute()) {
                throw new Exception("Failed to insert into users_details");
            }
    
            $conn->commit();
            return cUtils::returnData(true, "Account created", $user_id, true, 201);
        } catch (Exception $e) {
            // Rollback the transaction if something went wrong
            $conn->rollBack();
            return cUtils::returnData(false, "Something went wrong: " . $e->getMessage(), [], true, 500);
        }
    }
    // End of method


    // Change password
    public static function verifyEmail ($email) 
    {
        try {
            $query = "UPDATE users SET is_verified=1 WHERE email=:email";
            $stmt = Database::getConnection()->prepare($query);

            $stmt->bindParam(':email', $email, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new Exception("Something went wrong. Try again", 500);
            }

            return cUtils::returnData(true, "Email verified", [], true, 200);
        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), null, true, $e->getCode() ?: 500);
        }
    }
    // End of method


    // Reset password
    public static function resetPassword($email, $resetOTP, $resetExpiry) 
    {
        try {
            $query = "UPDATE users SET reset_OTP=:reset_OTP, reset_expiry=:reset_expiry WHERE email=:email";
            $stmt = Database::getConnection()->prepare($query);

            $stmt->bindParam(':reset_OTP', $resetOTP);
            $stmt->bindParam(':reset_expiry', $resetExpiry);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);


            if (!$stmt->execute()) {
                throw new Exception("Something went wrong. Try again", 500);
            }
            return cUtils::returnData(true, "Reset successfull", [], true, 200);
        } catch (PDOException $e) {
            return cUtils::returnData(false, "Database error: " . $e->getMessage(), null, true, 500);
        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), null, true, $e->getCode() ?: 500);
        }
    }
    // Method End


    //  Check and get details with email - Login/change password 
    public static function resetInfo($email)
    {
        try {
            $query = "SELECT password, reset_OTP, reset_expiry FROM users WHERE email = :email";
            $stmt = Database::getConnection()->prepare($query);

            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            if(!$stmt->execute()){
                throw new Exception("Something went wrong. Try again", 500);
            }

            if ($stmt->rowCount() < 1){
                throw new Exception("Email not found", 400);
            }

            $results = $stmt->fetch(PDO::FETCH_ASSOC);
            return cUtils::returnData(true,"Reset Details", $results, true, 200);
        } catch (PDOException $e) {
            return cUtils::returnData(false, "Database error: " . $e->getMessage(), null, true, 500);
        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), null, true, $e->getCode() ?: 500);
        }
    } 
    // End of method


    // Get User Personal information
    public static function userProfile($token)
    {
        try {
            $query = "SELECT * FROM users_details WHERE token = :token";
            $stmt = Database::getConnection()->prepare($query);

            $stmt->bindParam(':$token', $token, PDO::PARAM_STR);
            if(!$stmt->execute()){
                return cUtils::returnData(false, "Something went wrong, try again", null, true);
            }

            if ($stmt->rowCount() < 1){
                return cUtils::returnData(false, "Profile not found", $token, false); // No profile found - Not possible though
            } 

            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            return cUtils::returnData(true, "User found", $profile, false); // 
            
        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), null, true);
        }
    }
    // End method


    // Edit/Add User infomation
    public static function editProfile ($first_name, $last_name, $birth_date, $phone_number, $user_id)
    {
        try {
            $query = "UPDATE users_details SET first_name = :first_name, last_name = :last_name, birth_date = :birth_date, phone_number = :phone_number WHERE user_id = :user_id";
            $stmt = Database::getConnection()->prepare($query);

            $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
            $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
            $stmt->bindParam(':birth_date', $birth_date, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                return cUtils::returnData(false, "Failed to update, try again", [], true, 500);
            }

            return cUtils::returnData(true, "Profile Updated", [], true, 200);

        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), [], true, 500);
        }
    }
    // Method End


    // Change password
    public static function changePassword ($email, $password) 
    {
        try {
            $query = "UPDATE users SET password=:password WHERE email=:email";
            $stmt = Database::getConnection()->prepare($query);

            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new Exception("Something went wrong. Try again", 500);
            }

            return cUtils::returnData(true, "Password changed ", [], true, 200);
        } catch (PDOException $e) {
            return cUtils::returnData(false, "Database error: " . $e->getMessage(), null, true, 500);
        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), null, true, $e->getCode() ?: 500);
        }
    }
    // Method End


    // Store user authentication details - user_id, access_token, expiry_time, ip_address, $user_agent - should later go to redis
    public static function userAuthDetails ($user_id, $access_token, $expiry_time, $ip_address, $user_agent)
    {
        try {
            $query = "INSERT INTO users_auth_log (user_id, access_token, expiry_time, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
            $stmt = Database::getConnection()->prepare($query);

            $stmt->execute([$user_id, $access_token, $expiry_time, $ip_address, $user_agent]);

        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), $user_id, true);
        }
    }
    // Method End


    // Get user authentication details
    public static function getAuthDetails ($user_id, $access_token)
    {
        try {
            $query = "SELECT * FROM users_auth_log WHERE user_id = ? AND token = ? AND expiry > NOW()";
            $stmt = Database::getConnection()->prepare($query);
            
            $stmt->execute([$user_id, $access_token]);
            $result = $stmt->fetchObject();
    
            if (!$result) {
                return false;
            }
            return $result;
            
        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), $user_id, true);
        }
    }
    // Method End
}