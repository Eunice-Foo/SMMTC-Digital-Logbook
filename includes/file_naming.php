<?php
function generateUniqueFilename($original_filename, $username) {
    // Get file extension
    $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    
    // Get original filename without extension
    $original_name = pathinfo($original_filename, PATHINFO_FILENAME);
    
    // Sanitize username (remove special characters)
    $sanitized_username = preg_replace('/[^A-Za-z0-9]/', '_', $username);
    
    // Get timestamp
    $timestamp = date('Ymd_His');
    
    // Create unique filename
    $unique_filename = $sanitized_username . '_' . $timestamp . '_' . $original_name . '.' . $file_extension;
    
    // Replace spaces with underscores
    $unique_filename = str_replace(' ', '_', $unique_filename);
    
    return $unique_filename;
}
?>