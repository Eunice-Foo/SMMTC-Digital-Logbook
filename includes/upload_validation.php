<?php
// First check if mod_rewrite is enabled
if(!in_array('mod_rewrite', apache_get_modules())) {
    die("mod_rewrite is not enabled. Please enable it in Apache configuration.");
}

// Define upload directory
$upload_dir = "uploads/";

// Set maximum file size to 5GB (5 * 1024 * 1024 * 1024 bytes)
$max_file_size = 5 * 1024 * 1024 * 1024; 

// Check if directory exists, if not create it
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Verify directory is writable
if (!is_writable($upload_dir)) {
    die("Upload directory is not writable");
}

// Add file size validation function
function validateFileSize($file_size) {
    global $max_file_size;
    if ($file_size > $max_file_size) {
        return false;
    }
    return true;
}
?>