<?php
require_once 'upload_validation.php';
require_once 'file_naming.php';
require_once 'image_converter.php';  // Add this line

function insertMediaFile($conn, $user_id, $file_name, $file_type) {
    $stmt = $conn->prepare("
        INSERT INTO media (user_id, file_name, file_type, upload_date, upload_time)
        VALUES (:user_id, :file_name, :file_type, CURDATE(), CURTIME())
    ");
    
    $stmt->execute([
        ':user_id' => $user_id,
        ':file_name' => $file_name,
        ':file_type' => $file_type
    ]);
    
    return $conn->lastInsertId();
}

function associateMediaWithLog($conn, $mediaId, $entryId) {
    $stmt = $conn->prepare("
        INSERT INTO log_media (media_id, entry_id)
        VALUES (:media_id, :entry_id)
    ");
    
    $stmt->bindParam(':media_id', $mediaId, PDO::PARAM_INT);
    $stmt->bindParam(':entry_id', $entryId, PDO::PARAM_INT);
    $stmt->execute();
}

function generateVideoThumbnail($video_path, $thumbnail_path) {
    // Thumbnails are now generated client-side
    return false;
}

/**
 * Process uploaded media with WebP conversion
 */
function processMediaUpload($tmp_name, $original_filename, $file_type, $user_id) {
    // Generate unique filename
    $unique_filename = generateUniqueFilename($original_filename, $user_id);
    $upload_path = "uploads/" . $unique_filename;
    
    // Move uploaded file to uploads directory
    if (!move_uploaded_file($tmp_name, $upload_path)) {
        throw new Exception("Failed to move uploaded file");
    }
    
    // If it's an image, generate WebP version
    if (strpos($file_type, 'image/') === 0 && $file_type !== 'image/webp') {
        convertToWebP($upload_path);
    }
    
    // If it's a video, generate thumbnail
    if (strpos($file_type, 'video/') === 0) {
        $thumbnail_dir = "uploads/thumbnails/";
        if (!file_exists($thumbnail_dir)) {
            mkdir($thumbnail_dir, 0755, true);
        }
        
        $thumbnail_path = $thumbnail_dir . pathinfo($unique_filename, PATHINFO_FILENAME) . ".jpg";
        generateVideoThumbnail($upload_path, $thumbnail_path);
    }
    
    return $unique_filename;
}
?>