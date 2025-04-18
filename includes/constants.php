<?php
// Database constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'log');

// File upload constants - these should only be defined here
define('MAX_FILE_SIZE', 5 * 1024 * 1024 * 1024); // 5GB
define('UPLOAD_DIR', 'uploads/');
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'video/mp4',
    'video/mov',
    'video/mkv',
    'video/quicktime',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

// User roles
define('ROLE_STUDENT', 1);
define('ROLE_SUPERVISOR', 2);
define('ROLE_ADMIN', 3);

// Entry status
define('STATUS_PENDING', 'pending_review');
define('STATUS_REVIEWED', 'reviewed');
?>