<?php
session_start();
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'includes/upload_validation.php';  // Update path
require_once 'includes/file_naming.php';  // Update path
require_once 'includes/media_functions.php';  // Add this if needed
require_once 'components/log_entry_form.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

function isDuplicateFile($conn, $entryId, $fileName) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM media m 
        JOIN log_media lm ON m.media_id = lm.media_id 
        WHERE lm.entry_id = :entry_id AND m.file_name = :file_name
    ");
    $stmt->bindParam(':entry_id', $entryId, PDO::PARAM_INT);
    $stmt->bindParam(':file_name', $fileName);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        // Handle deleted files
        if (!empty($_POST['deleted_media_ids'])) {
            $deletedIds = json_decode($_POST['deleted_media_ids']);
            foreach ($deletedIds as $mediaId) {
                try {
                    // Get file name before deletion
                    $stmt = $conn->prepare("SELECT file_name FROM media WHERE media_id = :media_id");
                    $stmt->bindParam(':media_id', $mediaId);
                    $stmt->execute();
                    $fileName = $stmt->fetchColumn();

                    // First delete from portfolio_media if exists
                    $stmt = $conn->prepare("DELETE FROM portfolio_media WHERE media_id = :media_id");
                    $stmt->bindParam(':media_id', $mediaId);
                    $stmt->execute();

                    // Then delete from log_media
                    $stmt = $conn->prepare("DELETE FROM log_media WHERE media_id = :media_id");
                    $stmt->bindParam(':media_id', $mediaId);
                    $stmt->execute();

                    // Finally delete from media table
                    $stmt = $conn->prepare("DELETE FROM media WHERE media_id = :media_id");
                    $stmt->bindParam(':media_id', $mediaId);
                    $stmt->execute();

                    // Delete physical file
                    if ($fileName) {
                        $filePath = "uploads/" . $fileName;
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                } catch (PDOException $e) {
                    // Log the error but continue with other deletions
                    error_log("Error deleting media ID {$mediaId}: " . $e->getMessage());
                    continue;
                }
            }
        }

        // Update log entry
        $stmt = $conn->prepare("
            UPDATE log_entry 
            SET entry_title = :title,
                entry_description = :description,
                entry_date = :date,
                entry_time = :time
            WHERE entry_id = :entry_id AND user_id = :user_id
        ");

        $stmt->bindParam(':title', $_POST['title']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':date', $_POST['date']);
        $stmt->bindParam(':time', $_POST['time']);
        $stmt->bindParam(':entry_id', $_GET['id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        // Handle file uploads
        if (!empty($_FILES['media']['name'][0])) {
            foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
                // Add file size validation
                if (!validateFileSize($_FILES['media']['size'][$key])) {
                    throw new Exception("File size exceeds limit of 5GB");
                }

                if (!empty($tmp_name)) {
                    $original_filename = $_FILES['media']['name'][$key];
                    
                    // Skip if file already exists for this entry
                    if (isDuplicateFile($conn, $_GET['id'], $original_filename)) {
                        continue;
                    }
                    
                    $file_type = $_FILES['media']['type'][$key];
                    
                    // Store current date and time in variables before binding
                    $current_date = date('Y-m-d');
                    $current_time = date('H:i:s');
                    
                    // Generate unique filename
                    $unique_filename = generateUniqueFilename($original_filename, $_SESSION['username']);

                    // Move the uploaded file
                    move_uploaded_file($tmp_name, "uploads/" . $unique_filename);

                    // Insert into media table (removed media_category)
                    $stmt = $conn->prepare("
                        INSERT INTO media (user_id, file_name, file_type, upload_date, upload_time)
                        VALUES (:user_id, :file_name, :file_type, :upload_date, :upload_time)
                    ");

                    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                    $stmt->bindParam(':file_name', $unique_filename);
                    $stmt->bindParam(':file_type', $file_type);
                    $stmt->bindParam(':upload_date', $current_date);
                    $stmt->bindParam(':upload_time', $current_time);
                    $stmt->execute();

                    $media_id = $conn->lastInsertId();

                    // Insert into log_media table
                    $stmt = $conn->prepare("
                        INSERT INTO log_media (media_id, entry_id)
                        VALUES (:media_id, :entry_id)
                    ");

                    $stmt->bindParam(':media_id', $media_id, PDO::PARAM_INT);
                    $stmt->bindParam(':entry_id', $_GET['id'], PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Log entry updated successfully!']);
        exit();

    } catch(PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Fetch existing entry data with media files
try {
    $stmt = $conn->prepare("
        SELECT 
            le.entry_id,
            le.entry_title,
            le.entry_description,
            le.entry_status,
            le.entry_date,
            le.entry_time,
            GROUP_CONCAT(
                JSON_OBJECT(
                    'media_id', m.media_id,
                    'file_name', m.file_name,
                    'file_type', m.file_type
                )
            ) as media_files
        FROM log_entry le
        LEFT JOIN log_media lm ON le.entry_id = lm.entry_id
        LEFT JOIN media m ON lm.media_id = m.media_id
        WHERE le.entry_id = :entry_id AND le.user_id = :user_id
        GROUP BY le.entry_id
    ");
    
    $stmt->bindParam(':entry_id', $_GET['id'], PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    // Update the media files parsing
    $mediaFiles = [];
    if ($entry['media_files']) {
        $mediaFiles = json_decode('[' . $entry['media_files'] . ']', true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $mediaFiles = []; // Reset to empty array if JSON is invalid
        }
    }

    if (!$entry) {
        header("Location: logbook.php");
        exit();
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Log Entry</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/log_form.css">
    <link rel="stylesheet" href="css/file_preview.css">
    <link rel="stylesheet" href="css/video_thumbnail.css">
    <link rel="stylesheet" href="css/media_upload_button.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/video_thumbnail.js" defer></script>
    <script src="js/file_upload.js" defer></script>
    <script src="js/edit_log.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            generateVideoThumbnails();
        });
    </script>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <h2>Edit Log Entry</h2>
        <form id="editLogForm" action="edit_log.php?id=<?php echo $_GET['id']; ?>" method="POST" enctype="multipart/form-data" onsubmit="uploadFiles(event)">
            <div class="form-header">
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($entry['entry_date']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="title">Title: (Optional)</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($entry['entry_title']); ?>" placeholder="Enter log title">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required placeholder="Enter log description"><?php echo htmlspecialchars($entry['entry_description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="media">Upload Media Files:</label>
                <?php 
                require_once 'components/media_upload_button.php';
                renderMediaUploadButton();
                ?>
                <div id="selectedFiles" class="selected-files"></div>
                <div id="previewArea" class="preview-area">
                    <?php if (!empty($mediaFiles)): ?>
                        <?php foreach ($mediaFiles as $media): ?>
                            <div class="preview-container" data-media-id="<?php echo $media['media_id']; ?>">
                                <div class="file-info">
                                    <span><?php echo htmlspecialchars($media['file_name']); ?></span>
                                    <button type="button" class="remove-file-btn" onclick="removeExistingFile(this, <?php echo $media['media_id']; ?>)">×</button>
                                </div>
                                <?php if (strpos($media['file_type'], 'video/') === 0): ?>
                                    <?php 
                                    require_once 'components/video_thumbnail.php';
                                    renderVideoThumbnail($media['file_name']);
                                    ?>
                                <?php else: ?>
                                    <div class="preview-item">
                                        <img src="uploads/<?php echo htmlspecialchars($media['file_name']); ?>" alt="Media Preview">
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <input type="hidden" name="time" value="<?php echo htmlspecialchars($entry['entry_time']); ?>">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($entry['entry_status']); ?>">
            <button type="submit" class="submit-button">Save Changes</button>
        </form>
        <div class="progress">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    </div>
    <script>
        document.currentMediaFiles = <?php echo json_encode($mediaFiles); ?>;
    </script>
</body>
</html>