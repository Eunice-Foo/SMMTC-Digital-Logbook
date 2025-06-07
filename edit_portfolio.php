<?php
// filepath: c:\xampp\htdocs\log\edit_portfolio.php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'includes/upload_validation.php';
require_once 'includes/file_naming.php';
require_once 'includes/media_functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: portfolio.php");
    exit();
}

$portfolio_id = $_GET['id'];
$success_message = '';
$error_message = '';

try {
    // Check if the portfolio belongs to the current user
    $stmt = $conn->prepare("
        SELECT 
            p.portfolio_id,
            p.portfolio_title,
            p.portfolio_description,
            p.category,
            p.tools,
            p.user_id
        FROM portfolio p
        WHERE p.portfolio_id = :portfolio_id
    ");
    
    $stmt->bindParam(':portfolio_id', $portfolio_id, PDO::PARAM_INT);
    $stmt->execute();
    $portfolio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$portfolio) {
        header("Location: portfolio.php");
        exit();
    }
    
    // Check if current user owns this portfolio
    if ($_SESSION['user_id'] != $portfolio['user_id']) {
        header("Location: portfolio.php");
        exit();
    }
    
    // Get media files for this portfolio
    $stmt = $conn->prepare("
        SELECT 
            m.media_id,
            m.file_name,
            m.file_type
        FROM portfolio_media pm
        INNER JOIN media m ON pm.media_id = m.media_id
        WHERE pm.portfolio_id = :portfolio_id
    ");
    
    $stmt->bindParam(':portfolio_id', $portfolio_id, PDO::PARAM_INT);
    $stmt->execute();
    $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submission and add file handling logic
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        try {
            $conn->beginTransaction();
            
            // Handle deleted files
            if (isset($_POST['deleted_media_ids'])) {
                // Decode the JSON string of deleted media IDs
                $deletedIds = json_decode($_POST['deleted_media_ids']);
                
                if (is_array($deletedIds) && !empty($deletedIds)) {
                    foreach ($deletedIds as $mediaId) {
                        // Get file name before deletion
                        $stmt = $conn->prepare("SELECT file_name FROM media WHERE media_id = :media_id");
                        $stmt->bindParam(':media_id', $mediaId);
                        $stmt->execute();
                        $fileName = $stmt->fetchColumn();
                        
                        // Delete from portfolio_media
                        $stmt = $conn->prepare("DELETE FROM portfolio_media WHERE portfolio_id = :portfolio_id AND media_id = :media_id");
                        $stmt->bindParam(':portfolio_id', $portfolio_id);
                        $stmt->bindParam(':media_id', $mediaId);
                        $stmt->execute();
                        
                        // Check if file is used in log entries before deleting from media table
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM log_media WHERE media_id = :media_id");
                        $stmt->bindParam(':media_id', $mediaId);
                        $stmt->execute();
                        $usedInLogs = $stmt->fetchColumn() > 0;
                        
                        // Check if file is used in other portfolios
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM portfolio_media WHERE media_id = :media_id");
                        $stmt->bindParam(':media_id', $mediaId);
                        $stmt->execute();
                        $usedInOtherPortfolios = $stmt->fetchColumn() > 0;
                        
                        if (!$usedInLogs && !$usedInOtherPortfolios) {
                            // Delete from media table if not used elsewhere
                            $stmt = $conn->prepare("DELETE FROM media WHERE media_id = :media_id");
                            $stmt->bindParam(':media_id', $mediaId);
                            $stmt->execute();
                            
                            // Delete physical file
                            if ($fileName) {
                                $filePath = "uploads/" . $fileName;
                                if (file_exists($filePath)) {
                                    unlink($filePath);
                                }
                                
                                // Delete thumbnail if exists
                                $thumbnailPath = "uploads/thumbnails/" . pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
                                if (file_exists($thumbnailPath)) {
                                    unlink($thumbnailPath);
                                }
                            }
                        }
                    }
                }
            }
            
            // Update portfolio details
            $stmt = $conn->prepare("
                UPDATE portfolio 
                SET 
                    portfolio_title = :title, 
                    portfolio_description = :description,
                    category = :category,
                    tools = :tools
                WHERE portfolio_id = :portfolio_id AND user_id = :user_id
            ");
            
            $stmt->execute([
                ':title' => $_POST['title'],
                ':description' => $_POST['description'],
                ':category' => $_POST['category'],
                ':tools' => isset($_POST['tools']) ? $_POST['tools'] : '',
                ':portfolio_id' => $portfolio_id,
                ':user_id' => $_SESSION['user_id']
            ]);
            
            // Handle file uploads
            if (!empty($_FILES['media']['name'][0])) {
                foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
                    if (!empty($tmp_name)) {
                        $original_filename = $_FILES['media']['name'][$key];
                        $file_type = $_FILES['media']['type'][$key];
                        $file_size = $_FILES['media']['size'][$key];
                        
                        // Add file size validation
                        if (!validateFileSize($file_size)) {
                            throw new Exception("File size exceeds limit");
                        }
                        
                        // Generate unique filename
                        $unique_filename = generateUniqueFilename($original_filename, $_SESSION['username']);
                        
                        // Handle video files
                        if (strpos($file_type, 'video/') === 0) {
                            if (!move_uploaded_file($tmp_name, "uploads/" . $unique_filename)) {
                                throw new Exception("Failed to move uploaded video file");
                            }
                            
                            // Generate video thumbnail
                            try {
                                generateVideoThumbnail(
                                    "uploads/" . $unique_filename,
                                    "uploads/thumbnails/" . pathinfo($unique_filename, PATHINFO_FILENAME) . '.jpg'
                                );
                            } catch (Exception $e) {
                                // Log error but continue if thumbnail generation fails
                                error_log("Thumbnail generation error: " . $e->getMessage());
                            }
                        } else {
                            // Move the uploaded file
                            $destPath = "uploads/" . $unique_filename;
                            if (!move_uploaded_file($tmp_name, $destPath)) {
                                throw new Exception("Failed to move uploaded file");
                            }
                            
                            // Convert image to WebP and create thumbnails
                            require_once 'includes/image_converter.php';
                            $webpPath = createThumbnailsAndWebP($destPath, $unique_filename);
                            // Optionally, update the DB to use the .webp filename for images
                            if ($webpPath) {
                                $unique_filename = basename($webpPath); // Save WebP as main image
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
                        
                        // Insert into portfolio_media
                        $stmt = $conn->prepare("
                            INSERT INTO portfolio_media (media_id, portfolio_id)
                            VALUES (:media_id, :portfolio_id)
                        ");
                        
                        $stmt->execute([
                            ':media_id' => $media_id,
                            ':portfolio_id' => $portfolio_id
                        ]);
                    }
                }
            }
            
            $conn->commit();
            
            // Return a proper JSON response for AJAX
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Portfolio updated successfully!']);
            exit;
            
        } catch(PDOException $e) {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        } catch(Exception $e) {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
} catch(PDOException $e) {
    $error_message = 'Error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Portfolio</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/log_form.css">
    <link rel="stylesheet" href="css/file_preview.css">
    <link rel="stylesheet" href="css/video_thumbnail.css">
    <link rel="stylesheet" href="css/media_upload_button.css">
    <link rel="stylesheet" href="css/tools_input.css">
    <link rel="stylesheet" href="css/cancel_modal.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/video_thumbnail.js" defer></script>
    <script src="js/portfolio_edit.js" defer></script>
    <script src="js/tools_input.js" defer></script>
    <script src="js/cancel_confirmation.js" defer></script>
</head>
<body>
    <?php include 'components/topnav.php'; ?>
    
    <?php 
    // Include and initialize toast notification component
    require_once 'components/toast_notification.php';
    initializeToast();
    ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Edit Portfolio</h1>
        </div>
        
        <form id="editPortfolioForm" action="edit_portfolio.php?id=<?php echo $portfolio_id; ?>" method="POST" enctype="multipart/form-data" onsubmit="uploadFiles(event)">
            <div class="form-header">
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($portfolio['portfolio_title']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($portfolio['portfolio_description']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group category-field">
                    <label for="category">Category:</label>
                    <select id="category" name="category" required>
                        <option value="Image" <?php echo ($portfolio['category'] === 'Image') ? 'selected' : ''; ?>>Image</option>
                        <option value="Video" <?php echo ($portfolio['category'] === 'Video') ? 'selected' : ''; ?>>Video</option>
                        <option value="Animation" <?php echo ($portfolio['category'] === 'Animation') ? 'selected' : ''; ?>>Animation</option>
                        <option value="3D Model" <?php echo ($portfolio['category'] === '3D Model') ? 'selected' : ''; ?>>3D Model</option>
                        <option value="UI/UX" <?php echo ($portfolio['category'] === 'UI/UX') ? 'selected' : ''; ?>>UI/UX</option>
                        <option value="Graphic Design" <?php echo ($portfolio['category'] === 'Graphic Design') ? 'selected' : ''; ?>>Graphic Design</option>
                        <option value="Other" <?php echo ($portfolio['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group tools-field">
                    <label for="tools">Tools Used:</label>
                    <div class="tools-input-container">
                        <input type="text" id="toolInput" placeholder="Type a tool name and press Enter">
                        <div id="toolSuggestions" class="tool-suggestions"></div>
                        <div id="selectedTools" class="selected-tools"></div>
                        <input type="hidden" name="tools" id="toolsHidden" value="<?php echo htmlspecialchars($portfolio['tools']); ?>">
                    </div>
                </div>
            </div>
            <small class="hint">Type tool names (e.g., Photoshop, Blender) and press Enter to add them</small>

            <div class="form-group">
                <label for="media">Media Files:</label>
                <?php 
                require_once 'components/media_upload_button.php';
                renderMediaUploadButton();
                ?>
                <div id="selectedFiles" class="selected-files"></div>
                <div id="previewArea" class="preview-area">
                    <?php if (!empty($media)): ?>
                        <?php foreach ($media as $item): ?>
                            <div class="preview-container" data-media-id="<?php echo $item['media_id']; ?>">
                                <div class="file-info">
                                    <span><?php echo htmlspecialchars($item['file_name']); ?></span>
                                    <button type="button" class="remove-file-btn" onclick="removeExistingFile(this, <?php echo $item['media_id']; ?>)">×</button>
                                </div>
                                <?php if (strpos($item['file_type'], 'video/') === 0): ?>
                                    <?php 
                                    require_once 'components/video_thumbnail.php';
                                    renderVideoThumbnail($item['file_name']);
                                    ?>
                                <?php else: ?>
                                    <div class="preview-item">
                                        <img src="uploads/<?php echo htmlspecialchars($item['file_name']); ?>" alt="Media Preview">
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <a href="javascript:void(0)" class="btn cancel-btn">Cancel</a>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fi fi-rr-floppy-disk-pen"></i> Save Changes
                </button>
            </div>
        </form>
        <div class="progress">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Make sure we're using the correct selector for the cancel button
            const cancelBtn = document.querySelector('.cancel-btn, .btn:not(.btn-primary)');
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function(event) {
                    event.preventDefault();
                    
                    // Get the portfolio ID from the URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const portfolioId = urlParams.get('id');
                    
                    // Set the return URL to view the current portfolio
                    const returnUrl = `view_portfolio.php?id=${portfolioId}`;
                    
                    // Show the cancel confirmation dialog
                    confirmCancel(returnUrl);
                });
            } else {
                console.error('Cancel button not found in edit_portfolio.php');
            }
            
            // Initialize existing media files for tracking
            window.existingMediaData = <?php echo json_encode($media); ?>;
            console.log('Existing media data loaded', window.existingMediaData);
            
            // Initialize existing files
            if (window.existingMediaData) {
                window.existingMediaData.forEach(file => {
                    if (file.file_name) {
                        existingFiles.add(file.file_name);
                    }
                });
            }
            
            // Initialize video thumbnails
            generateVideoThumbnails();
        });
    </script>
</body>
</html>