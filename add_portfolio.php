<?php
// Increase memory and time limits for large uploads
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300); // 5 minutes

ob_start(); // Start output buffering to prevent any unexpected output

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
        
        // Optimize database operations
        $conn->exec("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
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
                        
                        // First just move the file to make uploads faster
                        if (!move_uploaded_file($tmp_name, $abs_path)) {
                            $error = error_get_last();
                            throw new Exception("Failed to move uploaded file: " . 
                                                (isset($error['message']) ? $error['message'] : 'Unknown error'));
                        }

                        // Insert media record first - use original file initially 
                        $media_id = insertMediaFile($conn, $_SESSION['user_id'], $unique_filename, $file_type);

                        // Only then start the thumbnail generation (after main file is saved)
                        if (strpos($file_type, 'video/') !== 0) {
                            // For images, generate thumbnails
                            require_once 'includes/image_converter.php';
                            $webpPath = createThumbnailsAndWebP($abs_path, $unique_filename, 85);
                            
                            // If WebP conversion was successful, update filename in DB
                            if ($webpPath && file_exists($webpPath)) {
                                $webp_filename = basename($webpPath);
                                $update_stmt = $conn->prepare("UPDATE media SET file_name = :webp_name WHERE media_id = :media_id");
                                $update_stmt->execute([
                                    ':webp_name' => $webp_filename,
                                    ':media_id' => $media_id
                                ]);
                            }
                        } else {
                            // For videos, create a poster thumbnail
                            $thumbnail_dir = "uploads/thumbnails/";
                            if (!file_exists($thumbnail_dir)) {
                                mkdir($thumbnail_dir, 0755, true);
                            }
                            
                            $thumbnail_path = $thumbnail_dir . pathinfo($unique_filename, PATHINFO_FILENAME) . ".jpg";
                            
                            // We'll rely on client-side thumbnail generation now, but this is where
                            // server-side video thumbnail generation would happen if needed
                        }

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
        ob_clean(); // Clean any previous output
        header('Content-Type: application/json');
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
    <link rel="stylesheet" href="css/cancel_modal.css">
    <link rel="stylesheet" href="css/form_indicators.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/video_thumbnail.js" defer></script>
    <script src="js/portfolio_upload.js" defer></script>
    <script src="js/tools_input.js" defer></script>
    <script src="js/cancel_confirmation.js" defer></script>
    <style>
    /* Floating action button */
    .add-options {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 100;
    }

    .btn-add {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 28px;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-family: var(--font-primary);
        font-size: 16px;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .btn-add:hover {
        background-color: var(--primary-hover);
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.18);
    }

    /* Loading overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.9);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s, visibility 0.3s;
    }

    .loading-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 20px;
    }

    .loading-text {
        font-size: 18px;
        color: var(--text-primary);
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Page header styles */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
    }
    </style>
</head>
<body>
    <?php 
    include 'components/topnav.php'; 
    
    // Include and initialize toast notification component
    require_once 'components/toast_notification.php';
    initializeToast();
    ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Add Portfolio Item</h1>
        </div>
        
        <form id="addPortfolioForm" action="add_portfolio.php" method="POST" enctype="multipart/form-data" onsubmit="uploadFiles(event)">
            <div class="form-header">
                <div class="form-group">
                    <label for="title">Title<span class="required-indicator">*</span></label>
                    <input type="text" id="title" name="title" placeholder="Enter portfolio title" required>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description<span class="required-indicator">*</span></label>
                <textarea id="description" name="description" rows="4" placeholder="Enter portfolio description" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group category-field">
                    <label for="category">Category<span class="required-indicator">*</span></label>
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
                    <label for="tools">Tools Used <span class="optional-label">(Optional)</span></label>
                    <div class="tools-input-container">
                        <input type="text" id="toolInput" placeholder="Type a tool name and press Enter">
                        <div id="toolSuggestions" class="tool-suggestions"></div>
                        <small class="hint">Type any tool names (e.g., Photoshop, Blender) and press Enter to add them</small>
                        <div id="selectedTools" class="selected-tools"></div>
                        <input type="hidden" name="tools" id="toolsHidden">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="media">Upload Media Files<span class="required-indicator">*</span></label>
                <?php 
                require_once 'components/media_upload_button.php';
                renderMediaUploadButton();
                ?>
                <div id="selectedFiles" class="selected-files"></div>
                <div id="previewArea" class="preview-area"></div>
            </div>
        </form>
        
        <!-- Floating action button -->
        <div class="add-options">
            <button type="submit" class="btn-add" form="addPortfolioForm">
                <i class="fi fi-rr-check"></i> Add Portfolio
            </button>
        </div>
        
        <!-- Loading overlay -->
        <div id="loadingOverlay" class="loading-overlay">
            <div class="loading-spinner"></div>
            <div class="loading-text">Uploading portfolio...</div>
        </div>
        
        <div class="progress">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    </div>

    <script>
    // Modified cancel button handler to show confirmation dialog
    document.addEventListener('DOMContentLoaded', function() {
        // Existing code...
        
        // Add cancel confirmation
        document.querySelector('.cancel-btn').addEventListener('click', function(event) {
            event.preventDefault();
            confirmCancel('portfolio.php');
        });
    });
    </script>
</body>
</html>