
<?php
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);

define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'prs_production');

define('JWT_SECRET', 'SECURITY_FIX_2024_NEW_SECRET_v2_TOKEN_REFRESH');
define('JWT_EXPIRY', 3600);

define('UPLOAD_DIR', __DIR__ . '/uploads/');
?>
