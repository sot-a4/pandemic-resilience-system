<?php
require_once 'config.php';

function createJWT($user_id, $role_id) {
    $issuedAt = time();
    $expiryTime = $issuedAt + JWT_EXPIRY; // Χρησιμοποιεί το config constant
    $payload = [
        'user_id' => $user_id,
        'role_id' => $role_id,
        'iat' => $issuedAt,
        'exp' => $expiryTime,
        'version' => 2  // Token version για μελλοντική ασφάλεια
    ];
    
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $header = base64_encode($header);
    
    $payload = json_encode($payload);
    $payload = base64_encode($payload);
    
    $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
    $signature = base64_encode($signature);
    
    return "$header.$payload.$signature";
}

function validateJWT($jwt) {
    $tokenParts = explode('.', $jwt);
    
    if (count($tokenParts) != 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $tokenParts;
    
    $calculatedSignature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    
    if ($calculatedSignature !== $signature) {
        return false;
    }
    
    $decodedPayload = json_decode(base64_decode($payload), true);
    
    if ($decodedPayload['exp'] < time()) {
        return false; // Token expired
    }
    
    // Έλεγχος για παλιά tokens (προαιρετικό)
    if (!isset($decodedPayload['version']) || $decodedPayload['version'] < 2) {
        return false; // Παλιό token, απαιτεί νέο login
    }
    
    return $decodedPayload;
}

function authenticate() {
    $headers = getallheaders();
    
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        die(json_encode(["error" => "No token provided"]));
    }
    
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
    $userData = validateJWT($jwt);
    
    if (!$userData) {
        http_response_code(403);
        die(json_encode(["error" => "Invalid or expired token"]));
    }
    
    return $userData;
}

function checkPermission($requiredRole) {
    $userData = authenticate();
    
    if ($userData['role_id'] < $requiredRole) {
        http_response_code(403);
        die(json_encode(["error" => "Insufficient permissions"]));
    }
    
    return $userData;
}
?>