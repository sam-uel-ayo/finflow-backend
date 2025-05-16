<?php
namespace mAccount;

use Database\Database;
use cUtils\cUtils;
use PDO;
use Exception;
use PDOException;


class mAccount
{
    //  Check and get details with user_id
    public static function userInfo ($user_id)
    {
        try {
            $query = "SELECT first_name, last_name, phone_number FROM users_details WHERE user_id = :user_id";
            $stmt = Database::getConnection()->prepare($query);

            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
            if(!$stmt->execute()){
                throw new Exception("Failed");
            }

            if ($stmt->rowCount() < 1){
                return cUtils::returnData(false, "Invalid account details", $user_id, true, 400); // Email doesn't exist
            }

            $results = $stmt->fetch(PDO::FETCH_ASSOC);
            return cUtils::returnData(true,"User Info", $results, true, 200); 

        } catch (Exception $e) {
            return cUtils::returnData(false, $e->getMessage(), $user_id, true, 500);
        }
    } 
    // End of method 


    // 
    public static function linkedAccount($accountID, $customerID)
    {
        $conn = Database::getConnection();
        try {
            $conn->beginTransaction();
    
            $stmt1 = $conn->prepare("UPDATE linked_accounts SET account_id = :account_id, link_status = 'pending' WHERE mono_customer_id = :mono_customer_id AND link_status = 'initialized'");
            $stmt1->bindParam(':mono_customer_id', $customerID, PDO::PARAM_STR);
            $stmt1->bindParam(':account_id', $accountID, PDO::PARAM_STR);

            if (!$stmt1->execute()) {
                throw new Exception("Failed to insert into linked_account");
            }
    
            $stmt2 = $conn->prepare("INSERT INTO account_balances(linked_account_id) VALUES (:linked_account_id)");
            $stmt2->bindParam(':linked_account_id', $accountID, PDO::PARAM_STR);

            if (!$stmt2->execute()) {
                throw new Exception("Failed to insert into account_balances");
            }

            $conn->commit();
            return cUtils::returnData(true, "Account added", $customerID, true, 201);
        } catch (Exception $e) {
            // Rollback the transaction if something went wrong
            $conn->rollBack();
            return cUtils::returnData(false, "Something went wrong: " . $e->getMessage(), [], true, 500);
        }
    }
    // End of method


    // Save Customer ID
    public static function saveCustomerID($user_id, $customerID)
    {
        $conn = Database::getConnection();
        try {
            $conn->beginTransaction();
    
            $stmt1 = $conn->prepare("INSERT INTO linked_accounts (user_id, mono_customer_id) VALUES (:user_id, :mono_customer_id)");
            $stmt1->bindParam(':user_id', $user_id, PDO::PARAM_STR);
            $stmt1->bindParam(':mono_customer_id', $customerID, PDO::PARAM_STR);

            if (!$stmt1->execute()) {
                throw new Exception("Failed to insert into linked_account");
            }
    
            $conn->commit();
            return cUtils::returnData(true, "Account added", $user_id, true, 201);
        } catch (Exception $e) {
            // Rollback the transaction if something went wrong
            $conn->rollBack();
            return cUtils::returnData(false, "Something went wrong: " . $e->getMessage(), [], true, 500);
        }
    }
    // End of method


    // Finalize Account Connection and add to DB
    public static function accountUpdate($account_id, $data_status, $institution_id, $institution_name, $account_name, $account_number, $account_type, $balance, $currency)
    {
        $conn = Database::getConnection();
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("
                UPDATE linked_accounts SET 
                    data_status = :data_status, 
                    institution_id = :institution_id, 
                    institution_name = :institution_name, 
                    account_name = :account_name, 
                    account_number = :account_number, 
                    account_type = :account_type,
                    link_status = 'success',
                    updated_at = NOW()
                WHERE 
                    account_id = :account_id
            ");

            $stmt->bindParam(':data_status', $data_status, PDO::PARAM_STR);
            $stmt->bindParam(':institution_id', $institution_id, PDO::PARAM_STR);
            $stmt->bindParam(':institution_name', $institution_name, PDO::PARAM_STR);
            $stmt->bindParam(':account_name', $account_name, PDO::PARAM_STR);
            $stmt->bindParam(':account_number', $account_number, PDO::PARAM_STR);
            $stmt->bindParam(':account_type', $account_type, PDO::PARAM_STR);
            $stmt->bindParam(':account_id', $account_id, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update linked_accounts");
            }

            // Insert into account_balances table
            $stmt2 = $conn->prepare("
                UPDATE account_balances SET
                    balance_amount = :balance_amount,
                    currency = :currency,
                    retrieved_at = NOW(),
                    updated_at = NOW()
                WHERE 
                    linked_account_id = :linked_account_id
            ");

            $stmt2->bindParam(':linked_account_id', $account_id, PDO::PARAM_STR);
            $stmt2->bindParam(':balance_amount', $balance, PDO::PARAM_STR);
            $stmt2->bindParam(':currency', $currency, PDO::PARAM_STR);


            if (!$stmt2->execute()) {
                throw new Exception("Failed to insert into account_balances");
            }

            $conn->commit();
            return cUtils::returnData(true, "Webhook data stored", $account_id, true, 201);
        } catch (Exception $e) {
            $conn->rollBack();
            return cUtils::returnData(false, "Something went wrong: " . $e->getMessage(), [], true, 500);
        }
    }



    // Update Account to DB
    public static function accountReauthorized($account_id, $data_status, $institution_id, $institution_name, $account_name, $account_number, $account_type, $balance, $currency)
    {
        $conn = Database::getConnection();
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("
                UPDATE linked_accounts SET 
                    data_status = :data_status, 
                    institution_id = :institution_id, 
                    institution_name = :institution_name, 
                    account_name = :account_name, 
                    account_number = :account_number, 
                    account_type = :account_type,
                    last_reauth_at = NOW(),
                    updated_at = NOW()
                WHERE 
                    account_id = :account_id 
                    AND link_status = 'success'
            ");

            $stmt->bindParam(':data_status', $data_status, PDO::PARAM_STR);
            $stmt->bindParam(':institution_id', $institution_id, PDO::PARAM_STR);
            $stmt->bindParam(':institution_name', $institution_name, PDO::PARAM_STR);
            $stmt->bindParam(':account_name', $account_name, PDO::PARAM_STR);
            $stmt->bindParam(':account_number', $account_number, PDO::PARAM_STR);
            $stmt->bindParam(':account_type', $account_type, PDO::PARAM_STR);
            $stmt->bindParam(':account_id', $account_id, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update linked_accounts");
            }

            // Insert into account_balances table
            $stmt2 = $conn->prepare("
                UPDATE account_balances SET
                    balance_amount = :balance_amount,
                    currency = :currency,
                    retrieved_at = NOW()
                WHERE 
                    linked_account_id = :linked_account_id
            ");
            $stmt2->bindParam(':linked_account_id', $account_id, PDO::PARAM_STR);
            $stmt2->bindParam(':balance_amount', $balance, PDO::PARAM_STR);
            $stmt2->bindParam(':currency', $currency, PDO::PARAM_STR);

            if ($stmt2->execute() === false || $stmt2->rowCount() === 0) {
                throw new Exception("Failed to update account_balances or no row matched");
            }

            $conn->commit();
            return cUtils::returnData(true, "Webhook data stored", $account_id, true, 201);
        } catch (Exception $e) {
            $conn->rollBack();
            return cUtils::returnData(false, "Something went wrong: " . $e->getMessage(), [], true, 500);
        }
    }
}
