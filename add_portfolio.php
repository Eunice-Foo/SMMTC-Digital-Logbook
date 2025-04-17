<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'includes/upload_validation.php';
require_once 'includes/file_naming.php';
require_once 'includes/media_functions.php';

// Ensure uploads directory has proper path and permissions
$upload_dir = __DIR__ . '/uploads/';
$thumbnail_dir = __DIR__ . '/uploads/thumbnails/';

// Create directories with proper permissions if they don't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    chmod($upload_dir, 0755);
}

if (!file_exists($thumbnail_dir)) {
    mkdir($thumbnail_dir, 0755, true);
    chmod($thumbnail_dir, 0755);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Add debug output if needed
        error_log("Portfolio submission started");
        
        // Start transaction
        $conn->beginTransaction();

        // Insert new portfolio entry with current date/time
        $stmt = $conn->prepare("
            INSERT INTO portfolio 
            (user_id, portfolio_title, portfolio_description, portfolio_date, portfolio_time, category, tools)
            VALUES 
            (:user_id, :title, :description, CURDATE(), CURTIME(), :category, :tools)
        ");

        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':title' => $_POST['title'],
            ':description' => $_POST['description'],
            ':category' => $_POST['category'],
            ':tools' => $_POST['tools']
        ]);

        $portfolio_id = $conn->lastInsertId();

        // Handle file uploads with improved error handling
        if (!empty($_FILES['media']['name'][0])) {
            foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name)) {
                    try {
                        $original_filename = $_FILES['media']['name'][$key];
                        $file_type = $_FILES['media']['type'][$key];
                        $file_size = $_FILES['media']['size'][$key];
                        $file_error = $_FILES['media']['error'][$key];
                        
                        // Check for upload errors first
                        if ($file_error !== UPLOAD_ERR_OK) {
                            throw new Exception("Upload error code: " . $file_error);
                        }
                        
                        // Log file info for debugging
                        error_log("Uploading file: $original_filename, type: $file_type, size: $file_size");
                        
                        // Add file size validation
                        if (!validateFileSize($file_size)) {
                            throw new Exception("File size exceeds limit");
                        }
                        
                        // Generate unique filename
                        $unique_filename = generateUniqueFilename($original_filename, $_SESSION['username']);
                        
                        // Use relative paths for database but absolute paths for file operations
                        $rel_path = 'uploads/' . $unique_filename;
                        $abs_path = $upload_dir . $unique_filename;
                        
                        error_log("Moving file to: $abs_path");
                        
                        // Handle file upload with explicit error reporting
                        if (!move_uploaded_file($tmp_name, $abs_path)) {
                            $error = error_get_last();
                            throw new Exception("Failed to move uploaded file: " . 
                                               (isset($error['message']) ? $error['message'] : 'Unknown error'));
                        }
                        
                        // Rest of the file handling logic...
                        if (strpos($file_type, 'video/') === 0) {
                            // Video thumbnail generation...
                            try {
                                generateVideoThumbnail(
                                    $abs_path,
                                    $thumbnail_dir . pathinfo($unique_filename, PATHINFO_FILENAME) . '.jpg'
                                );
                            } catch (Exception $e) {
                                error_log("Thumbnail generation error: " . $e->getMessage());
                            }
                        }

                        // Insert into media table using the relative path for database
                        $media_id = insertMediaFile($conn, $_SESSION['user_id'], $unique_filename, $file_type);
                        
                        // Insert into portfolio_media
                        $stmt = $conn->prepare("
                            INSERT INTO portfolio_media (media_id, portfolio_id)
                            VALUES (:media_id, :portfolio_id)
                        ");

                        $stmt->execute([
                            ':media_id' => $media_id,
                            ':portfolio_id' => $portfolio_id
                        ]);
                        
                        error_log("File processed successfully: $unique_filename");
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
        error_log("Portfolio saved successfully with ID: $portfolio_id");
        echo json_encode(['success' => true, 'message' => 'Portfolio item added successfully!']);
        exit();
    } catch(Exception $e) {
        $conn->rollBack();
        error_log("Portfolio error: " . $e->getMessage());
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
    <title>Add Portfolio Item</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/log_form.css">
    <link rel="stylesheet" href="css/file_preview.css">
    <link rel="stylesheet" href="css/video_thumbnail.css">
    <link rel="stylesheet" href="css/media_upload_button.css">
    <link rel="stylesheet" href="css/tools_input.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/video_thumbnail.js" defer></script>
    <script src="js/portfolio_upload.js" defer></script>
    <script src="js/tools_input.js" defer></script>
</head>
<body>
    <?php 
    include 'components/side_menu.php'; 
    
    // Include and initialize toast notification component
    require_once 'components/toast_notification.php';
    initializeToast();
    ?>
    
    <div class="main-content">
        <h2>Add Portfolio Item</h2>
        
        <form id="addPortfolioForm" action="add_portfolio.php" method="POST" enctype="multipart/form-data" onsubmit="uploadFiles(event)">
            <div class="form-header">
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" placeholder="Enter portfolio title" required>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" placeholder="Enter portfolio description" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group category-field">
                    <label for="category">Category:</label>
                    <select id="category" name="category" required>
                        <option value="Image">Image</option>
                        <option value="Video">Video</option>
                        <option value="Animation">Animation</option>
                        <option value="3D Model">3D Model</option>
                        <option value="UI/UX">UI/UX</option>
                        <option value="Graphic Design">Graphic Design</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group tools-field">
                    <label for="tools">Tools Used:</label>
                    <div class="tools-input-container">
                        <input type="text" id="toolInput" placeholder="Type a tool name and press Enter">
                        <div id="toolSuggestions" class="tool-suggestions"></div>
                        <div id="selectedTools" class="selected-tools"></div>
                        <input type="hidden" name="tools" id="toolsHidden">
                    </div>
                </div>
            </div>
            <small class="hint">Type tool names (e.g., Photoshop, Blender) and press Enter to add them</small>

            <div class="form-group">
                <label for="media">Upload Media Files:</label>
                <?php 
                require_once 'components/media_upload_button.php';
                renderMediaUploadButton();
                ?>
                <div id="selectedFiles" class="selected-files"></div>
                <div id="previewArea" class="preview-area"></div>
            </div>

            <button type="submit" class="submit-button">Add to Portfolio</button>
        </form>
        <div class="progress">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    </div>
</body>
</html>