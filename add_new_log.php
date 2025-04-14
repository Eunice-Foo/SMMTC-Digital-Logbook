<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'includes/upload_validation.php';
require_once 'includes/file_naming.php';
require_once 'includes/media_functions.php';
require_once 'components/log_entry_form.php';

// Add this line to define upload directory
$upload_dir = 'uploads/';

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
        if (!empty($_FILES['media']['name'][0])) {
            foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name)) {
                    try {
                        $original_filename = $_FILES['media']['name'][$key];
                        $file_type = $_FILES['media']['type'][$key];
                        
                        // Generate unique filename
                        $unique_filename = generateUniqueFilename($original_filename, $_SESSION['username']);
                        
                        // Remove thumbnail directory references:
                        if (!empty($file_type) && strpos($file_type, 'video/') === 0) {
                            if (!move_uploaded_file($tmp_name, $upload_dir . $unique_filename)) {
                                throw new Exception("Failed to move uploaded file");
                            }
                        } else {
                            if (!move_uploaded_file($tmp_name, $upload_dir . $unique_filename)) {
                                throw new Exception("Failed to move uploaded file");
                            }
                        }

                        // Insert into media table
                        $stmt = $conn->prepare("
                            INSERT INTO media (user_id, file_name, file_type, upload_date, upload_time)
                            VALUES (:user_id, :file_name, :file_type, CURDATE(), CURTIME())
                        ");

                        $stmt->execute([
                            ':user_id' => $_SESSION['user_id'],
                            ':file_name' => $unique_filename,
                            ':file_type' => $file_type
                        ]);

                        $media_id = $conn->lastInsertId();

                        // Insert into log_media
                        $stmt = $conn->prepare("
                            INSERT INTO log_media (media_id, entry_id)
                            VALUES (:media_id, :entry_id)
                        ");

                        $stmt->execute([
                            ':media_id' => $media_id,
                            ':entry_id' => $entry_id
                        ]);

                    } catch (Exception $e) {
                        $conn->rollBack();
                        error_log("File upload error: " . $e->getMessage());
                        echo json_encode(['success' => false, 'message' => 'Error processing file upload: ' . $e->getMessage()]);
                        exit();
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
        <form id="addLogForm" action="add_new_log.php" method="POST" enctype="multipart/form-data" onsubmit="uploadFiles(event)">
            <div class="form-header">
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="title">Title: (Optional)</label>
                    <input type="text" id="title" name="title" placeholder="Enter log title">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required placeholder="Enter log description"></textarea>
            </div>

            <div class="form-group">
                <label for="media">Upload Media Files:</label>
                <?php 
                require_once 'components/media_upload_button.php';
                renderMediaUploadButton();
                ?>
                <div id="selectedFiles" class="selected-files"></div>
                <div id="previewArea" class="preview-area"></div>
            </div>

            <input type="hidden" name="time" value="<?php echo date('H:i'); ?>">
            <button type="submit" class="submit-button">Add</button>
        </form>
        <div class="progress">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            generateVideoThumbnails();
        });
    </script>
</body>
</html>