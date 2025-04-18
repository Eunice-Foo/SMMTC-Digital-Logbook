<?php
// First check if mod_rewrite is enabled (this can be moved elsewhere or removed if not needed)
if (function_exists('apache_get_modules') && !in_array('mod_rewrite', apache_get_modules())) {
    error_log("mod_rewrite is not enabled. Some features may not work properly.");
}

// Check if constants.php already included these
if (!defined('MAX_FILE_SIZE')) {
    // Set maximum file size to 5GB (5 * 1024 * 1024 * 1024 bytes)
    define('MAX_FILE_SIZE', 5 * 1024 * 1024 * 1024);
}

// Define upload directory if not already defined
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', "uploads/");
}

// Always initialize the variable from the constant
$upload_dir = UPLOAD_DIR;

// Check if directory exists, if not create it
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Verify directory is writable
if (!is_writable($upload_dir)) {
    error_log("Upload directory is not writable: $upload_dir");
}

/**
 * Validate file size
 * 
 * @param int $fileSize Size of the file in bytes
 * @return bool Whether file size is valid
 */
function validateFileSize($fileSize) {
    return $fileSize <= MAX_FILE_SIZE;
}

/**
 * Validate file type
 * 
 * @param string $fileType MIME type of the file
 * @return bool Whether file type is valid
 */
function validateFileType($fileType) {
    $allowed_types = [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        
        // Videos
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo',
        
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    return in_array($fileType, $allowed_types);
}
?>