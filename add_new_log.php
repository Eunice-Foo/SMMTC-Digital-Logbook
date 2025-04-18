<?php
// filepath: c:\xampp\htdocs\log\add_new_log.php
require_once 'includes/session_check.php';
require_once 'includes/constants.php';
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

        // Handle file uploads
        if (!empty($_FILES) && isset($_FILES['media']) && is_array($_FILES['media']['name'])) {
            foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name) && is_uploaded_file($tmp_name)) {
                    try {
                        $original_filename = $_FILES['media']['name'][$key];
                        $file_type = $_FILES['media']['type'][$key];
                        $file_size = $_FILES['media']['size'][$key];
                        $file_error = $_FILES['media']['error'][$key];
                        
                        // Check for upload errors
                        if ($file_error !== UPLOAD_ERR_OK) {
                            throw new Exception("Upload error detected: {$file_error} for file {$original_filename}");
                        } else {
                            // Process media upload
                            $unique_filename = generateUniqueFilename($original_filename, $_SESSION['user_id']);
                            $upload_destination = $upload_dir . $unique_filename;
                            
                            if (!move_uploaded_file($tmp_name, $upload_destination)) {
                                throw new Exception("Failed to move uploaded file {$original_filename}");
                            }
                            
                            // Process media with helper functions
                            $media_id = insertMediaFile($conn, $_SESSION['user_id'], $unique_filename, $file_type);
                            
                            if (!$media_id) {
                                throw new Exception("Failed to insert media record into database");
                            }
                            
                            // Associate with log entry
                            associateMediaWithLog($conn, $media_id, $entry_id);
                        }
                    } catch (Exception $e) {
                        throw $e; // Re-throw to be caught by the outer try-catch
                    }
                }
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Log entry added successfully!']);
        exit();

    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}
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
            'isEdit' => false,
            'showCustomButtons' => true
        ]);
        ?>
        
        <!-- Progress bar outside the form for better visibility -->
        <div class="progress">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Clear any previously selected files
            selectedFiles = [];
            
            // Initialize video thumbnails
            if (typeof generateVideoThumbnails === 'function') {
                generateVideoThumbnails();
            }
            
            // Ensure form submission is handled by the media_preview.js uploadFiles function
            document.getElementById('addLogForm').addEventListener('submit', function(event) {
                uploadFiles(event);
            });

            // Add event handler for the cancel button
            document.querySelector('.cancel-btn').addEventListener('click', function() {
                window.location.href = 'logbook.php';
            });
        });
    </script>
</body>
</html>