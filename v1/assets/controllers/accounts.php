<?php
namespace cAccounts;

use cUtils\cUtils;
use mAuth\mAuth;
use mAccount\mAccount;
use Mono\Mono;
use Exception; 


class cAccounts 
{
    // Initialize Account Linking
    public static function initialize($data)
    {
        try {
            if ($data == null) {
                throw new Exception("Data not found", 400);
            }

            $getUserKyc = json_decode(mAuth::onboardingStatus($data->user_id));
            if ($getUserKyc->status == false) {
                throw new Exception("Initiation Failed". $getUserKyc->message, $getUserKyc->statusCode);
            }

            if ($getUserKyc->data->kyc_status !== "verified"){
                throw new Exception("Complete your KYC before adding an account", 403);
            }


            $getUserInfo = json_decode(mAccount::userInfo($data->user_id));
            if ($getUserInfo-> status == false){
                throw new Exception("Initiation Faileds ". $getUserInfo->message, $getUserInfo->statusCode);
            }

            $name = $getUserInfo->data->first_name ." ".$getUserInfo->data->last_name;
            $phone_number = $getUserInfo->data->phone_number;
            $intializeLinking =  json_decode(Mono::initialize($name, $phone_number, $data->user_id));
            if ($intializeLinking-> status == false){
                throw new Exception("Initiation Failedk ". $intializeLinking->message, $intializeLinking->statusCode);
            }
            
            $saveCustomerID = json_decode(mAccount::saveCustomerID($data->user_id, $intializeLinking->data->data->customer));// Save customer ID from mono to identify user later
            if ($saveCustomerID == false){
                return json_decode(cUtils::returnData(false, $saveCustomerID->message, $saveCustomerID->data, true, $saveCustomerID->statusCode));
            }
            return json_decode(cUtils::returnData(true, "Initialize Started", $intializeLinking->data, true, 200));
        } catch (Exception $e) {
            return json_decode(cUtils::returnData(false, $e->getMessage(), $intializeLinking->data, true, $e->getCode() ?: 500));
        }
    }



    // Finalize Account Linking
    public static function verifyInitialization($data)
    {
        try {
            if ($data == null) {
                throw new Exception("Data not found", 400);
            }

            $accountID = json_decode(Mono::auth($data->code));
            if ($accountID->status == false) {
                throw new Exception("Failed". $accountID->message, $accountID->statusCode);
            }

            $addToDB = json_decode(mAccount::linkedAccount($accountID->data, $data->user_id));
            if ($addToDB-> status == false){
                error_log("Account linking successful but failed to add to db for user ". $data->user_id. "account_id = " . $accountID->data->id);
                throw new Exception($addToDB->message, $addToDB->statusCode);
            }

            return json_decode(cUtils::returnData(true, "Account added successfully", null, true, 200));
        } catch (Exception $e) {
            return json_decode(cUtils::returnData(false, $e->getMessage(), $accountID->data, true, $e->getCode() ?: 500));
        }
    }


    // Finalize Account Linking
    public static function handleAccountConnected($data)
    {
        try {
            if ($data == null) {
                throw new Exception("Data not found", 400);
            }

            $addToDB = json_decode(mAccount::linkedAccount($data->id, $data->customer)); // Add accountID to DB - JUST LINKED 
            if ($addToDB-> status == false){
                error_log("Account linking successful but failed to add to db for user with customerID ". $data->customer);
                throw new Exception($addToDB->message, $addToDB->statusCode);
            }

            return json_decode(cUtils::returnData(true, "Account added successfully", null, true, 200));
        } catch (Exception $e) {
            return json_decode(cUtils::returnData(false, $e->getMessage(), $data, true, $e->getCode() ?: 500));
        }
    }



    // Finalize Account Connection and add to DB
    public static function handleAccountUpdated($data)
    {
        try {
            if ($data == null) {
                throw new Exception("Data not found", 400);
            }
            // Extract all fields from the webhook payload
            $meta = $data->meta ?? null;
            $account = $data->account ?? null;

            if (!$meta || !$account) {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Malformed data object']);
                throw new Exception("Mallformed data object", 400);
                exit;
            }

            // Accessing meta fields
            $dataStatus = $meta->data_status ?? null;
            $authMethod = $meta->auth_method ?? null;

            // Accessing account fields
            $accountId     = $account->_id ?? null;
            $accountName   = $account->name ?? null;
            $accountNumber = $account->accountNumber ?? null;
            $currency      = $account->currency ?? null;
            $balance       = $account->balance ?? null;
            $accountType   = $account->type ?? null;
            $bvn           = $account->bvn ?? null;
            $authUsed      = $account->authMethod ?? null;

            // Institution sub-fields
            $institution = $account->institution ?? null;
            $institutionName     = $institution->name ?? null;
            $institutionBankCode = $institution->bankCode ?? null;
            $institutionType     = $institution->type ?? null;


            $addToDB = json_decode(mAccount::accountUpdate($accountId, $dataStatus, $institutionBankCode, $institutionName, $accountName, $accountNumber, $accountType, $balance, $currency)); // Add account info to DB - JUST LINKED
            if ($addToDB-> status == false){
                error_log("Account update successful but failed to add to db for user with account ID ->". $accountId. $addToDB->message);
                throw new Exception($addToDB->message, $addToDB->statusCode);
            }

            return json_decode(cUtils::returnData(true, "Account added successfully", null, true, 200));
        } catch (Exception $e) {
            return json_decode(cUtils::returnData(false, $e->getMessage(), null, true, $e->getCode() ?: 500));
        }
    }


    // Reauthenthicate a user
    public static function handleAccountReauthorized($data)
    {
        try {
            if ($data == null) {
                throw new Exception("Data not found", 400);
            }
            // Extract all fields from the webhook payload
            $meta = $data->meta ?? null;
            $account = $data->account ?? null;

            if (!$meta || !$account) {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Malformed data object']);
                throw new Exception("Mallformed data object", 400);
                exit;
            }

            // Accessing meta fields
            $dataStatus = $meta->data_status ?? null;
            $authMethod = $meta->auth_method ?? null;

            // Accessing account fields
            $accountId     = $account->_id ?? null;
            $accountName   = $account->name ?? null;
            $accountNumber = $account->accountNumber ?? null;
            $currency      = $account->currency ?? null;
            $balance       = $account->balance ?? null;
            $accountType   = $account->type ?? null;
            $bvn           = $account->bvn ?? null;
            $authUsed      = $account->authMethod ?? null;

            // Institution sub-fields
            $institution = $account->institution ?? null;
            $institutionName     = $institution->name ?? null;
            $institutionBankCode = $institution->bankCode ?? null;
            $institutionType     = $institution->type ?? null;


            $addToDB = json_decode(mAccount::accountReauthorized($accountId, $dataStatus, $institutionBankCode, $institutionName, $accountName, $accountNumber, $accountType, $balance, $currency)); // Add account info to DB - JUST LINKED
            if ($addToDB-> status == false){
                error_log("Account reauth successful but failed to add to db for user with account ID ->". $accountId. $addToDB->message);
                throw new Exception($addToDB->message, $addToDB->statusCode);
            }

            return json_decode(cUtils::returnData(true, "Account added successfully", null, true, 200));
        } catch (Exception $e) {
            return json_decode(cUtils::returnData(false, $e->getMessage(), null, true, $e->getCode() ?: 500));
        }
    }
}
