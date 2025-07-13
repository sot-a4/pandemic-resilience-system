<?php


require_once 'db.php';
require_once 'jwt_handler.php';
require_once 'security.php';
require_once 'rate_limiter.php';

// ----------------------------
// Set Security Headers
// ----------------------------
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');  // Replace * with specific domain in production
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header("Content-Security-Policy: default-src 'self'");

// ----------------------------
// CORS Preflight Handling
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit(0);
}

// ----------------------------
// Debug Mode (Disable in Production)
// ----------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start(); // Prevent premature output

// ----------------------------
// Parse Request URI
// ----------------------------
$requestUri = $_SERVER['REQUEST_URI'];
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$cleanPath = str_replace($scriptDir, '', $requestUri);
$cleanPath = trim(parse_url($cleanPath, PHP_URL_PATH), '/');
$segments = explode('/', $cleanPath);

// ----------------------------
// Extract Resource & ID
// ----------------------------
$resource = $segments[0] ?? '';
$id = isset($segments[1]) && is_numeric($segments[1]) ? intval($segments[1]) : null;
$method = $_SERVER['REQUEST_METHOD'];

error_log("Request: $cleanPath | Method: $method | Resource: $resource | ID: " . ($id ?? 'null'));

// ----------------------------
// Rate Limiting
// ----------------------------
$conn = getConnection();
if (!checkRateLimit($conn, $resource)) {
    http_response_code(429);
    echo json_encode(["error" => "Too many requests. Please try again later."]);
    exit;
}

// ----------------------------
// Route Handling
// ----------------------------
switch ($resource) {
    case 'users':
        require_once 'endpoints/users.php';
        handleUserRequest($method, $id);
        break;

    case 'auth':
        require_once 'endpoints/auth.php';
        handleAuthRequest($method);
        break;

    case 'vaccination':
        require_once 'endpoints/vaccination.php';
        handleVaccinationRequest($method, $id);
        break;

    case 'documents':
        require_once 'endpoints/documents.php';
        handleDocumentRequest($method, $id);
        break;

    case 'audit':
        require_once 'endpoints/audit.php';
        handleAuditRequest($method, $id);
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Resource not found"]);
        break;
}

ob_end_flush(); // Finish clean output
?>
