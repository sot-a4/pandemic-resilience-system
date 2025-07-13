
<?php
// Database configuration
//define('DB_HOST', 'localhost');
//define('DB_USERNAME', 'root');
//define('DB_PASSWORD', '');
//define('DB_NAME', 'prs_database');

// JWT configuration
//define('JWT_SECRET', 'your_jwt_secret_key_here');
//define('JWT_EXPIRY', 3600); // 1 hour

// Upload directory


// config.php
//define('UPLOAD_DIR', __DIR__ . '/uploads/');
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'prs_database');
//define('DB_NAME', 'prs_production');   it also operates perfectly


// JWT configuration - UPDATED FOR SECURITY
define('JWT_SECRET', 'SECURITY_FIX_2024_NEW_SECRET_v2_TOKEN_REFRESH');
define('JWT_EXPIRY', 3600); // 1 hour

// Upload directory
define('UPLOAD_DIR', __DIR__ . '/uploads/');

?>