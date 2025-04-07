<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entry_id'])) {
    try {
        // Begin transaction
        $conn->beginTransaction();

        // First, get all media files associated with this entry
        $stmt = $conn->prepare("
            SELECT m.media_id, m.file_name 
            FROM media m 
            INNER JOIN log_media lm ON m.media_id = lm.media_id 
            WHERE lm.entry_id = :entry_id
        ");
        $stmt->bindParam(':entry_id', $_POST['entry_id'], PDO::PARAM_INT);
        $stmt->execute();
        $media_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Delete the actual files from uploads directory
        foreach ($media_files as $media) {
            // Delete main media file
            $file_path = "uploads/" . $media['file_name'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Delete related media records
        $stmt = $conn->prepare("
            DELETE lm, m 
            FROM log_media lm 
            INNER JOIN media m ON lm.media_id = m.media_id 
            WHERE lm.entry_id = :entry_id
        ");
        $stmt->bindParam(':entry_id', $_POST['entry_id'], PDO::PARAM_INT);
        $stmt->execute();

        // Delete the log entry
        $stmt = $conn->prepare("
            DELETE FROM log_entry 
            WHERE entry_id = :entry_id AND user_id = :user_id
        ");
        $stmt->bindParam(':entry_id', $_POST['entry_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>