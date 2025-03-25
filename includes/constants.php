<?php
// Database constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'log');

// File upload constants
define('MAX_FILE_SIZE', 5 * 1024 * 1024 * 1024); // 5GB
define('UPLOAD_DIR', 'uploads/');
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'video/mp4',
    'video/mov',
    'video/mkv'
]);

// User roles
define('ROLE_STUDENT', 1);
define('ROLE_SUPERVISOR', 2);

// Entry status
define('STATUS_PENDING', 'pending_review');
define('STATUS_REVIEWED', 'reviewed');
?>