<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/upload_validation.php';  // Update path
require_once 'includes/file_naming.php';  // Update path
require_once 'includes/media_functions.php';  // Add this if needed

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
                // Get file name before deletion
                $stmt = $conn->prepare("SELECT file_name FROM media WHERE media_id = :media_id");
                $stmt->bindParam(':media_id', $mediaId);
                $stmt->execute();
                $fileName = $stmt->fetchColumn();

                // Delete from log_media
                $stmt = $conn->prepare("DELETE FROM log_media WHERE media_id = :media_id");
                $stmt->bindParam(':media_id', $mediaId);
                $stmt->execute();

                // Delete from media table
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

    // Parse media files JSON
    $mediaFiles = $entry['media_files'] ? json_decode('[' . $entry['media_files'] . ']', true) : [];

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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/file_upload.js"></script>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="container">
        <h2>Edit Log Entry</h2>
        <form id="editLogForm" action="edit_log.php?id=<?php echo $_GET['id']; ?>" method="POST" enctype="multipart/form-data" onsubmit="uploadFiles(event)">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($entry['entry_title']); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($entry['entry_description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($entry['entry_date']); ?>" required>
            </div>

            <div class="form-group">
                <label for="time">Time:</label>
                <input type="time" id="time" name="time" value="<?php echo htmlspecialchars($entry['entry_time']); ?>" required>
            </div>

            <input type="hidden" name="status" value="<?php echo htmlspecialchars($entry['entry_status']); ?>">
            
            <div class="form-group">
                <label for="media">Upload Media Files:</label>
                <input type="file" id="media" name="media[]" multiple accept="image/*,video/*" onchange="showSelectedFiles(this)">
                <div id="selectedFiles" class="selected-files"></div>
                <div id="previewArea" class="preview-area">
                    <?php foreach ($mediaFiles as $media): ?>
                        <div class="preview-container" data-media-id="<?php echo $media['media_id']; ?>">
                            <div class="preview-item">
                                <?php if (strpos($media['file_type'], 'video/') === 0): ?>
                                    <div class="video-thumbnail">
                                        <img src="uploads/thumbnails/<?php echo pathinfo($media['file_name'], PATHINFO_FILENAME); ?>.jpg" alt="Video Thumbnail">
                                        <span class="video-icon">🎥</span>
                                    </div>
                                <?php else: ?>
                                    <img src="uploads/<?php echo htmlspecialchars($media['file_name']); ?>" alt="Media Preview">
                                <?php endif; ?>
                            </div>
                            <div class="file-info">
                                <span><?php echo htmlspecialchars($media['file_name']); ?></span>
                                <button type="button" class="remove-file-btn" onclick="removeExistingFile(this, <?php echo $media['media_id']; ?>)">×</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="submit-button">Save Changes</button>
        </form>
        <div class="progress">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    </div>
    <script>
    let selectedFiles = []; // Global array to store new files
    let deletedMediaIds = []; // Global array to store IDs of files to delete
    let existingFiles = new Set(); // Store names of existing files

    // Initialize existingFiles when page loads
    document.addEventListener('DOMContentLoaded', function() {
        <?php foreach ($mediaFiles as $media): ?>
            existingFiles.add('<?php echo $media['file_name']; ?>');
        <?php endforeach; ?>
    });

    function showSelectedFiles(input) {
        const previewArea = document.getElementById('previewArea');
        const files = Array.from(input.files);
        
        files.forEach(file => {
            // Check if file already exists
            if (!existingFiles.has(file.name)) {
                selectedFiles.push(file);
                const previewContainer = createPreviewContainer(file);
                previewArea.appendChild(previewContainer);
            } else {
                alert(`File "${file.name}" already exists in this log entry.`);
            }
        });

        // Clear the file input to allow selecting the same file again
        input.value = '';
    }

    function removeExistingFile(button, mediaId) {
        if (confirm('Are you sure you want to remove this file?')) {
            deletedMediaIds.push(mediaId);
            button.closest('.preview-container').remove();
        }
    }

    function uploadFiles(event) {
        event.preventDefault();
        
        const form = document.getElementById('editLogForm');
        const formData = new FormData(form);
        
        // Add deleted media IDs
        formData.append('deleted_media_ids', JSON.stringify(deletedMediaIds));
        
        // Clear any existing media[] entries
        formData.delete('media[]');
        
        // Add only new files that don't exist in the database
        selectedFiles.forEach(file => {
            if (!existingFiles.has(file.name)) {
                formData.append('media[]', file);
            }
        });
        
        const progressBar = $('.progress-bar');
        progressBar.width('0%').text('0%');
        
        $.ajax({
            url: form.action,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = (evt.loaded / evt.total) * 100;
                        progressBar.width(percentComplete + '%');
                        progressBar.text(Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.success) {
                        alert(result.message);
                        window.location.href = 'logbook.php';
                    } else {
                        alert('Error: ' + (result.message || 'Unknown error occurred'));
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    console.error('Response:', response);
                    alert('Error processing response. Check console for details.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr, status, error});
                alert('Upload failed: ' + error);
            }
        });
    }

    // Add this function in edit_log.php script section
    function createPreviewContainer(file) {
        const container = document.createElement('div');
        container.className = 'preview-container';
        container.dataset.fileName = file.name;
        
        const fileInfo = createFileInfo(file);
        container.appendChild(fileInfo);
        
        if (file.type.startsWith('image/')) {
            createImagePreview(file, container);
        } else if (file.type.startsWith('video/')) {
            createVideoPreview(file, container);
        }
        
        return container;
    }

    function createImagePreview(file, container) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.className = 'preview-item';
            preview.innerHTML = `<img src="${e.target.result}" alt="${file.name}">`;
            container.appendChild(preview);
        }
        reader.readAsDataURL(file);
    }

    function createVideoPreview(file, container) {
        const preview = document.createElement('div');
        preview.className = 'preview-item video-preview';
        
        // Create a video element to generate thumbnail
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.src = URL.createObjectURL(file);
        
        video.onloadedmetadata = function() {
            video.currentTime = 1; // Get frame at 1 second
            
            video.onseeked = function() {
                // Create canvas to capture video frame
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                
                // Draw video frame to canvas
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Create thumbnail image (removed play button and filename)
                preview.innerHTML = `
                    <div class="video-thumbnail">
                        <img src="${canvas.toDataURL()}" alt="Video Thumbnail">
                    </div>
                `;
                
                // Clean up
                URL.revokeObjectURL(video.src);
            };
        };
        
        container.appendChild(preview);
    }

    function createFileInfo(file) {
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        fileInfo.innerHTML = `
            <span>${file.name}</span>
            <span>(${(file.size / (1024 * 1024)).toFixed(2)} MB)</span>
            <button type="button" class="remove-file-btn" onclick="removeFile(this)">×</button>
        `;
        return fileInfo;
    }

    function removeFile(button) {
        const container = button.closest('.preview-container');
        const fileName = container.dataset.fileName;
        
        // Remove file from selectedFiles array
        selectedFiles = selectedFiles.filter(file => file.name !== fileName);
        
        container.remove();
    }
    </script>
</body>
</html>