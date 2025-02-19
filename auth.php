<?php
include_once(__DIR__.'/vendor/autoload.php'); 

// require 'jwt/JWT.php';
// require 'jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Secret key for signing JWTs (keep this secret!)
const SECRET_KEY = 'your_secret_key_here';
const ALGORITHM = 'HS256';
const STATIC_API_KEY = 'your_static_api_key_here';

// Generate JWT Token
function generateJWT() {
    $payload = [
        'iss' => 'your_api_name',
        'iat' => time(),
        'exp' => time() + (60 * 60), // Token expires in 1 hour
    ];
    return JWT::encode($payload, SECRET_KEY, ALGORITHM);
}

// Verify JWT Token
function verifyJWT($token) {
    try {
        return JWT::decode($token, new Key(SECRET_KEY, ALGORITHM));
    } catch (Exception $e) {
        return false;
    }
}

// Issue token if API key is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'getToken') {
    $headers = getallheaders();
    if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== STATIC_API_KEY) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API Key']);
        exit;
    }
    echo json_encode(['token' => generateJWT()]);
    exit;
}

// Example: Protect an API route
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'protected') {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = verifyJWT($token);
    if ($decoded) {
        echo json_encode(['message' => 'Access granted']);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
    }
    exit;
}
?>
