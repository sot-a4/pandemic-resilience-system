
<?php
// Load database and JWT handler
require_once '../db.php'; // Adjust path as needed
require_once '../jwt_handler.php';

header('Content-Type: application/json');

// Get Authorization header
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["error" => "Missing Authorization header"]);
    exit;
}

// Extract the token
$authHeader = $headers['Authorization'];
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid Authorization header format"]);
    exit;
}

$token = $matches[1];

// Validate the token (make sure this function exists in jwt_handler.php)
$userData = validateJWT($token); 

if (!$userData) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid or expired token"]);
    exit;
}

// If valid, return success and user data
echo json_encode([
    "success" => true,
    "user" => $userData
]);
