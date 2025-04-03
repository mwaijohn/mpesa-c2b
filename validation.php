<?php
/**
 * validation.php - Endpoint for M-Pesa C2B Validation with SQLite
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
logRequest('validation_requests.log', $requestData);

// Connect to database
$db = getDbConnection();

// Default response - Accept the transaction
$response = [
    'ResultCode' => 0,
    'ResultDesc' => 'Accepted'
];

// Example validation logic
if (isset($requestData['BillRefNumber']) && isset($requestData['TransAmount'])) {
    // Extract important details
    $accountNumber = $requestData['BillRefNumber'];
    $amount = $requestData['TransAmount'];
    $phoneNumber = $requestData['MSISDN'] ?? '';
    
    // Example: Check if account exists in your system
    $stmt = $db->prepare("SELECT id FROM accounts WHERE account_number = :account_number");
    $stmt->bindValue(':account_number', $accountNumber, SQLITE3_TEXT);
    $result = $stmt->execute();
    $account = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$account) {
        // Account doesn't exist - reject the transaction
        $response = [
            'ResultCode' => 1, // C2B rejection code
            'ResultDesc' => 'Rejected: Account not found'
        ];
        
        logRequest('validation_rejected.log', [
            'reason' => 'Account not found',
            'accountNumber' => $accountNumber,
            'request' => $requestData
        ]);
    }
    
    // Optional: Additional validations
    // Example: Check minimum amount
    if ($amount < 10) {
        $response = [
            'ResultCode' => 1,
            'ResultDesc' => 'Rejected: Amount too low'
        ];
        
        logRequest('validation_rejected.log', [
            'reason' => 'Amount too low',
            'amount' => $amount,
            'request' => $requestData
        ]);
    }
}

// Close database connection
$db->close();

// Return response to Safaricom
header('Content-Type: application/json');
echo json_encode($response);
?>