<?php
/**
 * Safaricom Daraja API: C2B (Customer to Business) Till Payments Implementation
 */

class DarajaC2B {
    private $consumer_key;
    private $consumer_secret;
    private $environment;
    private $shortcode;
    private $access_token;
    private $confirmation_url;
    private $validation_url;
    
    /**
     * Constructor with configuration options
     */
    public function __construct($config) {
        $this->consumer_key = $config['consumer_key'];
        $this->consumer_secret = $config['consumer_secret'];
        $this->environment = $config['environment']; // 'sandbox' or 'production'
        $this->shortcode = $config['shortcode']; // Your business till number
        $this->confirmation_url = $config['confirmation_url'];
        $this->validation_url = $config['validation_url'];
    }
    
    /**
     * Get OAuth access token from Safaricom
     */
    public function getAccessToken() {
        $url = $this->environment === 'production' 
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' 
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
            
        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $result = json_decode($response, true);
            $this->access_token = $result['access_token'];

            return [
                'success' => true,
                'access_token' => $result['access_token'] ?? null
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to get access token',
                'response' => $response,
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Register C2B URLs (Confirmation and Validation URLs)
     */
    public function registerUrls() {
        $url = $this->environment === 'production' 
            ? 'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl' 
            : 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl';
            
        if (!$this->access_token) {
            $this->getAccessToken();
        }
        
        $data = [
            'ShortCode' => $this->shortcode,
            'ResponseType' => 'Completed', // or 'Cancelled'
            'ConfirmationURL' => $this->confirmation_url,
            'ValidationURL' => $this->validation_url
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . curl_error($ch),
                'http_code' => $httpCode
            ];
        }
        
        curl_close($ch);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'response' => json_decode($response, true),
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Simulate C2B transaction (for testing in sandbox)
     */
    public function simulateTransaction($amount, $msisdn, $ref_number = 'TestRef') {
        if ($this->environment !== 'sandbox') {
            throw new Exception("Simulation is only available in sandbox environment");
        }
        
        $url = 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/simulate';
        
        if (!$this->access_token) {
            $this->getAccessToken();
        }
        
        $data = [
            'ShortCode' => $this->shortcode,
            'CommandID' => 'CustomerPayBillOnline', // or 'CustomerBuyGoodsOnline' for till numbers
            'Amount' => $amount,
            'Msisdn' => $msisdn, // Phone number initiating the transaction
            'BillRefNumber' => $ref_number // Reference number
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return json_decode($response);
    }
}

// Example validation URL endpoint - create in a file like validation.php
function validationEndpoint() {
    // Get the request body
    $request = file_get_contents('php://input');
    $data = json_decode($request);
    
    // Log transaction data for debugging
    file_put_contents('validation_log.txt', json_encode($data) . PHP_EOL, FILE_APPEND);
    
    // You can perform validation logic here
    // For example, check if the account number exists in your system
    
    // Return a response
    // Return 0 to accept the transaction, or any other value to reject
    $response = [
        'ResultCode' => 0,
        'ResultDesc' => 'Accepted'
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
}

// Example confirmation URL endpoint - create in a file like confirmation.php
function confirmationEndpoint() {
    // Get the request body
    $request = file_get_contents('php://input');
    $data = json_decode($request);
    
    // Log transaction data
    file_put_contents('confirmation_log.txt', json_encode($data) . PHP_EOL, FILE_APPEND);
    
    // Process the confirmed transaction
    // Example: Update your database with payment details
    
    // Sample data structure:
    // TransactionType: Pay Bill
    // TransID: The transaction ID from M-Pesa
    // TransTime: Transaction timestamp
    // TransAmount: The amount paid
    // BusinessShortCode: Your till number
    // BillRefNumber: Reference number provided by customer
    // InvoiceNumber: Additional reference
    // OrgAccountBalance: Your account balance
    // ThirdPartyTransID: Third party transaction ID
    // MSISDN: The phone number that made the payment
    // FirstName: First name of the customer
    
    // Return a response
    $response = [
        'ResultCode' => 0,
        'ResultDesc' => 'Confirmed'
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
}

// Example of how to use the class
function exampleUsage() {
    // Configuration
    $config = [
        'consumer_key' => 'YOUR_CONSUMER_KEY',
        'consumer_secret' => 'YOUR_CONSUMER_SECRET',
        'environment' => 'sandbox', // Use 'production' for live environment
        'shortcode' => '174379', // Replace with your till number
        'confirmation_url' => 'https://example.com/confirmation.php',
        'validation_url' => 'https://example.com/validation.php'
    ];
    
    try {
        // Initialize the C2B class
        $c2b = new DarajaC2B($config);
        
        // Get access token
        $token = $c2b->getAccessToken();
        echo "Access Token: " . $token . PHP_EOL;
        
        // Register URLs
        $register_result = $c2b->registerUrls();
        echo "Register URLs result: " . print_r($register_result, true) . PHP_EOL;
        
        // For sandbox testing, simulate a transaction
        if ($config['environment'] === 'sandbox') {
            $simulate_result = $c2b->simulateTransaction(
                100, // Amount
                '254708374149', // Test phone number
                'REF123' // Reference number
            );
            echo "Simulation result: " . print_r($simulate_result, true) . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    }
}
?>