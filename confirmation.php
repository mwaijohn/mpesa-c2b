<?php
/**
 * confirmation.php - Endpoint for M-Pesa C2B Confirmation with SQLite
 */

// Required for database operations
require_once 'db_connection.php';

// Log all incoming requests for debugging
function logRequest($filename, $data) {
    $logFile = 'logs/' . $filename;
    $timestamp = date('Y-m-d H:i:s');
    $logData = $timestamp . ' - ' . json_encode($data) . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }
    
    file_put_contents($logFile, $logData, FILE_APPEND);
}

// Get the request data
$request = file_get_contents('php://input');
$requestData = json_decode($request, true);

// Log the incoming request
logRequest('confirmation_requests.log', $requestData);

// Connect to database
$db = getDbConnection();

// Default response - Acknowledge receipt of the transaction
$response = [
    'ResultCode' => 0,
    'ResultDesc' => 'Confirmation received successfully'
];

// Process the transaction
if (isset($requestData['TransID']) && isset($requestData['TransAmount'])) {
    // Extract transaction details
    $transactionId = $requestData['TransID'] ?? '';
    $transactionType = $requestData['TransactionType'] ?? '';
    $transAmount = $requestData['TransAmount'] ?? 0;
    $businessShortCode = $requestData['BusinessShortCode'] ?? '';
    $billRefNumber = $requestData['BillRefNumber'] ?? '';
    $invoiceNumber = $requestData['InvoiceNumber'] ?? '';
    $orgAccountBalance = $requestData['OrgAccountBalance'] ?? '';
    $thirdPartyTransID = $requestData['ThirdPartyTransID'] ?? '';
    $msisdn = $requestData['MSISDN'] ?? '';
    $firstName = $requestData['FirstName'] ?? '';
    $middleName = $requestData['MiddleName'] ?? '';
    $lastName = $requestData['LastName'] ?? '';
    $transTime = $requestData['TransTime'] ?? '';
    
    // Format transaction time from Safaricom format (YYYYMMDDHHmmss) to SQLite format (YYYY-MM-DD HH:mm:ss)
    $formattedTransTime = '';
    if (!empty($transTime) && strlen($transTime) == 14) {
        $formattedTransTime = substr($transTime, 0, 4) . '-' . 
                              substr($transTime, 4, 2) . '-' . 
                              substr($transTime, 6, 2) . ' ' . 
                              substr($transTime, 8, 2) . ':' . 
                              substr($transTime, 10, 2) . ':' . 
                              substr($transTime, 12, 2);
    }
    
    try {
        // First, check if this transaction already exists to avoid duplicates
        $checkStmt = $db->prepare("SELECT id FROM mpesa_transactions WHERE trans_id = :trans_id");
        $checkStmt->bindValue(':trans_id', $transactionId, SQLITE3_TEXT);
        $checkResult = $checkStmt->execute();
        $existingTransaction = $checkResult->fetchArray(SQLITE3_ASSOC);
        
        if (!$existingTransaction) {
            // Transaction doesn't exist yet, insert it
            $stmt = $db->prepare("INSERT INTO mpesa_transactions (
                trans_id, trans_time, trans_amount, business_shortcode, 
                bill_ref_number, invoice_number, org_account_balance, 
                third_party_trans_id, msisdn, first_name, middle_name, 
                last_name, transaction_type, created_at
            ) VALUES (
                :trans_id, :trans_time, :trans_amount, :business_shortcode,
                :bill_ref_number, :invoice_number, :org_account_balance,
                :third_party_trans_id, :msisdn, :first_name, :middle_name,
                :last_name, :transaction_type, datetime('now')
            )");
            
            $stmt->bindValue(':trans_id', $transactionId, SQLITE3_TEXT);
            $stmt->bindValue(':trans_time', $formattedTransTime, SQLITE3_TEXT);
            $stmt->bindValue(':trans_amount', $transAmount, SQLITE3_FLOAT);
            $stmt->bindValue(':business_shortcode', $businessShortCode, SQLITE3_TEXT);
            $stmt->bindValue(':bill_ref_number', $billRefNumber, SQLITE3_TEXT);
            $stmt->bindValue(':invoice_number', $invoiceNumber, SQLITE3_TEXT);
            $stmt->bindValue(':org_account_balance', $orgAccountBalance, SQLITE3_TEXT);
            $stmt->bindValue(':third_party_trans_id', $thirdPartyTransID, SQLITE3_TEXT);
            $stmt->bindValue(':msisdn', $msisdn, SQLITE3_TEXT);
            $stmt->bindValue(':first_name', $firstName, SQLITE3_TEXT);
            $stmt->bindValue(':middle_name', $middleName, SQLITE3_TEXT);
            $stmt->bindValue(':last_name', $lastName, SQLITE3_TEXT);
            $stmt->bindValue(':transaction_type', $transactionType, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            
            if ($result) {
                logRequest('confirmation_success.log', [
                    'message' => 'Transaction saved successfully',
                    'transactionId' => $transactionId
                ]);
                
                // Update customer account or order status
                updateCustomerAccount($db, $billRefNumber, $transAmount, $transactionId);
            } else {
                logRequest('confirmation_errors.log', [
                    'message' => 'Failed to save transaction',
                    'error' => $db->lastErrorMsg(),
                    'transactionId' => $transactionId
                ]);
            }
        } else {
            logRequest('confirmation_duplicates.log', [
                'message' => 'Duplicate transaction received',
                'transactionId' => $transactionId
            ]);
        }
    } catch (Exception $e) {
        logRequest('confirmation_exceptions.log', [
            'message' => 'Exception occurred',
            'error' => $e->getMessage(),
            'transactionId' => $transactionId
        ]);
    }
}

// Helper function to update customer account or order
function updateCustomerAccount($db, $accountNumber, $amount, $transactionId) {
    try {
        // Start transaction for account update
        $db->exec('BEGIN TRANSACTION');
        
        // First get current balance
        $balanceStmt = $db->prepare("SELECT balance FROM accounts WHERE account_number = :account_number");
        $balanceStmt->bindValue(':account_number', $accountNumber, SQLITE3_TEXT);
        $balanceResult = $balanceStmt->execute();
        $accountData = $balanceResult->fetchArray(SQLITE3_ASSOC);
        
        if ($accountData) {
            $currentBalance = $accountData['balance'];
            $newBalance = $currentBalance + $amount;
            
            // Update account balance
            $updateStmt = $db->prepare("
                UPDATE accounts 
                SET balance = :new_balance, 
                    last_payment_date = datetime('now'),
                    last_payment_amount = :amount,
                    last_transaction_id = :trans_id,
                    updated_at = datetime('now')
                WHERE account_number = :account_number
            ");
            
            $updateStmt->bindValue(':new_balance', $newBalance, SQLITE3_FLOAT);
            $updateStmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
            $updateStmt->bindValue(':trans_id', $transactionId, SQLITE3_TEXT);
            $updateStmt->bindValue(':account_number', $accountNumber, SQLITE3_TEXT);
            
            $updateResult = $updateStmt->execute();
            
            if ($updateResult) {
                $db->exec('COMMIT');
                logRequest('account_updates.log', [
                    'message' => 'Account updated successfully',
                    'accountNumber' => $accountNumber,
                    'oldBalance' => $currentBalance,
                    'newBalance' => $newBalance,
                    'amount' => $amount
                ]);
            } else {
                $db->exec('ROLLBACK');
                logRequest('account_update_errors.log', [
                    'message' => 'Failed to update account',
                    'accountNumber' => $accountNumber,
                    'error' => $db->lastErrorMsg()
                ]);
            }
        } else {
            $db->exec('ROLLBACK');
            logRequest('account_update_errors.log', [
                'message' => 'Account not found during update',
                'accountNumber' => $accountNumber
            ]);
        }
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        logRequest('account_exceptions.log', [
            'message' => 'Exception updating account',
            'error' => $e->getMessage(),
            'accountNumber' => $accountNumber
        ]);
    }
}

// Close database connection
$db->close();

// Return response to Safaricom
header('Content-Type: application/json');
echo json_encode($response);
?>