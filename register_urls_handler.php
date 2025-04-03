<?php
/**
 * register_urls_handler.php - Backend handler for URL registration form
 * 
 * This script processes the form submission and makes the actual API call to
 * Safaricom Daraja API to register C2B URLs.
 */

// Allow cross-origin requests if needed (adjust as necessary)
header('Content-Type: application/json');
include_once 'daraja_c2b.php';
include_once 'config.php';


// Get the JSON data from the request
$requestData = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$requiredFields = [
    'consumer_key',
    'consumer_secret',
    'shortcode',
    'environment',
    'validation_url',
    'confirmation_url'
];

foreach ($requiredFields as $field) {
    if (empty($requestData[$field])) {
        echo json_encode([
            'success' => false,
            'error' => "Missing required field: $field"
        ]);
        exit;
    }
}

// Extract data
$consumerKey = $requestData['consumer_key'];
$consumerSecret = $requestData['consumer_secret'];
$shortcode = $requestData['shortcode'];
$environment = $requestData['environment'];
$validationUrl = $requestData['validation_url'];
$confirmationUrl = $requestData['confirmation_url'];
$responseType = $requestData['response_type'] ?? 'Completed';

// Log request for debugging (exclude sensitive data)
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'shortcode' => $shortcode,
    'environment' => $environment,
    'validation_url' => $validationUrl,
    'confirmation_url' => $confirmationUrl,
    'response_type' => $responseType
];

// Create logs directory if needed
if (!is_dir('logs')) {
    mkdir('logs', 0755, true);
}

file_put_contents('logs/url_registration.log', json_encode($logData) . PHP_EOL, FILE_APPEND);


//set up configurations

$config = [
    'consumer_key' => MPESA_CONSUMER_KEY,
    'consumer_secret' => MPESA_CONSUMER_SECRET,
    'environment' => $environment, // Use 'production' for live environment
    'shortcode' => $shortcode, // Replace with your till number
    'confirmation_url' => $confirmationUrl,
    'validation_url' => $validationUrl
];

$c2b = new DarajaC2B($config);
$tokenResult = $c2b->getAccessToken();
// Step 1: Get OAuth access token

// Execute the API calls

if (!$tokenResult['success']) {
    echo json_encode([
        'success' => false,
        'stage' => 'authentication',
        'error' => $tokenResult['error'] ?? 'Failed to get access token',
        'details' => $tokenResult
    ]);
    exit;
}

// $accessToken = $tokenResult['access_token'];
// $registerResult = registerUrls($accessToken, $shortcode, $responseType, $validationUrl, $confirmationUrl, $environment);
$registerResult = $c2b->registerUrls();
if ($registerResult['success']) {
    // Log success (exclude sensitive data)
    $successLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'shortcode' => $shortcode,
        'environment' => $environment,
        'result' => $registerResult['response']
    ];
    file_put_contents('logs/url_registration_success.log', json_encode($successLog) . PHP_EOL, FILE_APPEND);

    $simulate_result = $c2b->simulateTransaction(
        100, // Amount
        '254710883976', // Test phone number
        'REF123' // Reference number
    );
    file_put_contents('logs/simulate.log', json_encode($simulate_result) . PHP_EOL, FILE_APPEND);

} else {
    // Log failure (exclude sensitive data)
    $failureLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'shortcode' => $shortcode,
        'environment' => $environment,
        'error' => $registerResult['error'] ?? 'URL registration failed',
        'http_code' => $registerResult['http_code']
    ];
    file_put_contents('logs/url_registration_failures.log', json_encode($tokenResult) . PHP_EOL, FILE_APPEND);
}

// Return the final result
echo json_encode([
    'success' => $registerResult['success'],
    'environment' => $environment,
    'shortcode' => $shortcode,
    'result' => $registerResult['response'] ?? null,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>