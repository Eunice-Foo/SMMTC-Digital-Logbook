<?php
function insertMediaFile($conn, $userId, $fileName, $fileType) {
    $stmt = $conn->prepare("
        INSERT INTO media (user_id, file_name, file_type, upload_date, upload_time)
        VALUES (:user_id, :file_name, :file_type, CURDATE(), CURTIME())
    ");
    
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':file_name', $fileName);
    $stmt->bindParam(':file_type', $fileType);
    $stmt->execute();
    
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
    // Return false since we're not using FFmpeg anymore
    // Thumbnails will be generated client-side
    return false;
}
?>