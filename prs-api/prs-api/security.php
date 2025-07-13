
<?php
// Security helpers for input validation and sanitization

function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateDate($date) {
    $format = 'Y-m-d';
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Rate limiting


// File type validation
function isAllowedFileType($file, $allowedTypes) {
    // Check MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $fileType = $finfo->file($file['tmp_name']);
    return in_array($fileType, $allowedTypes);
}

function logAction($conn, $user_id, $action, $resource, $resource_id) {
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, entity_affected, record_id, timestamp) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("issi", $user_id, $action, $resource, $resource_id);
    $stmt->execute();
}
?>