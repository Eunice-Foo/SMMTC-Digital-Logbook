<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'includes/upload_validation.php';
require_once 'includes/file_naming.php';
require_once 'includes/media_functions.php';

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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/file_upload.js"></script>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <h2>Add New Log Entry</h2>
        <form id="addLogForm" action="add_new_log.php" method="POST" enctype="multipart/form-data" onsubmit="uploadFiles(event)">
            <div class="form-group">
                <label for="title">Title: (Optional)</label>
                <input type="text" id="title" name="title">
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="time">Time:</label>
                <input type="time" id="time" name="time" value="<?php echo date('H:i'); ?>" required>
            </div>

            <input type="hidden" name="status" value="pending_review">
            <div class="form-group">
                <label for="media">Upload Media Files:</label>
                <input type="file" id="media" name="media[]" multiple accept="image/*,video/*" onchange="showSelectedFiles(this)">
                <div id="selectedFiles" class="selected-files"></div>
                <div id="previewArea" class="preview-area"></div>
            </div>

            <button type="submit" class="submit-button">Add</button>
        </form>
        <div class="progress">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    </div>

    <script>
    let selectedFiles = []; // Global array to store selected files

    function showSelectedFiles(input) {
        const previewArea = document.getElementById('previewArea');
        const files = Array.from(input.files);
        
        // Add new files to our selectedFiles array
        files.forEach(file => {
            selectedFiles.push(file);
            const previewContainer = createPreviewContainer(file);
            previewArea.appendChild(previewContainer);
        });

        // Clear the file input to allow selecting the same file again
        input.value = '';
    }

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

    function removeFile(button) {
        const container = button.closest('.preview-container');
        const fileName = container.dataset.fileName;
        
        // Remove file from selectedFiles array
        selectedFiles = selectedFiles.filter(file => file.name !== fileName);
        
        container.remove();
    }

    function uploadFiles(event) {
        event.preventDefault();
        
        const form = document.getElementById('addLogForm');
        const formData = new FormData(form);
        
        // Clear any existing media[] entries
        formData.delete('media[]');
        
        // Add all files from our selectedFiles array
        selectedFiles.forEach(file => {
            formData.append('media[]', file);
        });
        
        const progressBar = $('.progress-bar');
        
        $.ajax({
            url: 'add_new_log.php',
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
                        console.error('Server response:', response);
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    console.error('Raw response:', response);
                    alert('Error processing response. Check console for details.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Upload error:', {xhr, status, error});
                alert('Upload failed: ' + error);
            }
        });
    }
    </script>
</body>
</html>