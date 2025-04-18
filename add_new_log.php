<?php
// filepath: c:\xampp\htdocs\log\add_new_log.php
require_once 'includes/session_check.php';
require_once 'includes/constants.php';    // Include this first
require_once 'includes/db.php';
require_once 'includes/upload_validation.php';
require_once 'includes/file_naming.php';
require_once 'includes/media_functions.php';
require_once 'components/log_entry_form.php';

// Use constant value instead of redefining
$upload_dir = UPLOAD_DIR;

// Create uploads directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Create thumbnails directory if it doesn't exist
$thumbnail_dir = 'uploads/thumbnails/';
if (!file_exists($thumbnail_dir)) {
    mkdir($thumbnail_dir, 0755, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        // Insert new log entry
        $stmt = $conn->prepare("
            INSERT INTO log_entry 
            (user_id, entry_title, entry_description, entry_status, entry_date, entry_time)
            VALUES 
            (:user_id, :title, :description, 'Pending', :date, :time)
        ");

        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':title', $_POST['title']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':date', $_POST['date']);
        $stmt->bindParam(':time', $_POST['time']);
        $stmt->execute();

        $entry_id = $conn->lastInsertId();

        // Handle file uploads using the improved approach
        if (!empty($_FILES['media']['name'][0])) {
            // Log the incoming files for debugging
            error_log("Found " . count($_FILES['media']['name']) . " files to upload");
            
            foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name)) {
                    try {
                        $original_filename = $_FILES['media']['name'][$key];
                        $file_type = $_FILES['media']['type'][$key];
                        $file_size = $_FILES['media']['size'][$key];
                        
                        // Log processing each file
                        error_log("Processing file: $key - $original_filename ($file_type, $file_size bytes)");
                        
                        // Process media upload using the media_functions helper
                        $unique_filename = processMediaUpload($tmp_name, $original_filename, $file_type, $_SESSION['user_id']);
                        
                        // Log successful processing
                        error_log("Media processed: $unique_filename");
                        
                        // Insert into media table
                        $media_id = insertMediaFile($conn, $_SESSION['user_id'], $unique_filename, $file_type);
                        
                        // Associate with log entry
                        associateMediaWithLog($conn, $media_id, $entry_id);
                        
                        // Log successful database entry
                        error_log("Media added to database: ID $media_id");
                    } catch (Exception $e) {
                        error_log("Error processing file $key ($original_filename): " . $e->getMessage());
                        throw $e; // Re-throw to be caught by the outer try-catch
                    }
                }
            }
        } else {
            error_log("No files found in the request");
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Log entry added successfully!']);
        exit();

    } catch(Exception $e) {
        $conn->rollBack();
        error_log("Log entry error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// New code block to fetch log entries with media files
$stmt = $conn->prepare("
    SELECT 
        le.entry_id,
        le.entry_title,
        le.entry_description,
        le.entry_status,
        le.entry_date,
        le.entry_time,
        GROUP_CONCAT(CONCAT('uploads/', m.file_name)) as media_files
    FROM log_entry le
    LEFT JOIN log_media lm ON le.entry_id = lm.entry_id
    LEFT JOIN media m ON lm.media_id = m.media_id
    WHERE le.user_id = :user_id
    GROUP BY le.entry_id
    ORDER BY le.entry_date DESC, le.entry_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Log Entry</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/log_form.css">
    <link rel="stylesheet" href="css/file_preview.css">
    <link rel="stylesheet" href="css/video_thumbnail.css">
    <link rel="stylesheet" href="css/media_upload_button.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/file_upload.js" defer></script>
    <script src="js/video_thumbnail.js" defer></script>
    <script src="js/media_preview.js" defer></script>
</head>
<body>
    <?php 
    include 'components/side_menu.php'; 
    
    // Include and initialize toast notification component
    require_once 'components/toast_notification.php';
    initializeToast();
    ?>
    
    <div class="main-content">
        <h2>Add New Log Entry</h2>
        
        <?php
        // Use log entry form component for consistent form markup
        renderLogEntryForm([
            'title' => '',
            'description' => '',
            'date' => date('Y-m-d'),
            'time' => date('H:i'),
            'mediaFiles' => [],
            'isEdit' => false
        ]);
        ?>
        
        <!-- Progress bar outside the form for better visibility -->
        <div class="progress">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    </div>

    <script>
        // Initialize with empty selected files array
        document.addEventListener('DOMContentLoaded', function() {
            // Clear any previously selected files
            selectedFiles = [];
            
            // Initialize video thumbnails
            if (typeof generateVideoThumbnails === 'function') {
                generateVideoThumbnails();
            }
            
            // Ensure form submission is handled by the media_preview.js uploadFiles function
            document.getElementById('addLogForm').addEventListener('submit', function(event) {
                // This will call the uploadFiles function from media_preview.js
                uploadFiles(event);
            });
        });
    </script>
</body>
</html>