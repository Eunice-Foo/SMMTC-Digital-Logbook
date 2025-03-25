<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'includes/upload_validation.php';
require_once 'includes/file_naming.php';
require_once 'includes/media_functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        // Insert new portfolio entry
        $stmt = $conn->prepare("
            INSERT INTO portfolio 
            (user_id, portfolio_title, portfolio_description, portfolio_date, portfolio_time)
            VALUES 
            (:user_id, :title, :description, :date, :time)
        ");

        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':title' => $_POST['title'],
            ':description' => $_POST['description'],
            ':date' => $_POST['date'],
            ':time' => $_POST['time']
        ]);

        $portfolio_id = $conn->lastInsertId();

        // Handle file uploads
        if (!empty($_FILES['media']['name'][0])) {
            foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name)) {
                    $original_filename = $_FILES['media']['name'][$key];
                    $file_type = $_FILES['media']['type'][$key];
                    
                    // Generate unique filename
                    $unique_filename = generateUniqueFilename($original_filename, $_SESSION['username']);

                    // Move the uploaded file
                    move_uploaded_file($tmp_name, $upload_dir . $unique_filename);

                    // Insert into media table
                    $stmt = $conn->prepare("
                        INSERT INTO media 
                        (user_id, file_name, file_type, upload_date, upload_time)
                        VALUES 
                        (:user_id, :file_name, :file_type, CURDATE(), CURTIME())
                    ");

                    $stmt->execute([
                        ':user_id' => $_SESSION['user_id'],
                        ':file_name' => $unique_filename,
                        ':file_type' => $file_type
                    ]);

                    $media_id = $conn->lastInsertId();

                    // Insert into portfolio_media
                    $stmt = $conn->prepare("
                        INSERT INTO portfolio_media 
                        (media_id, portfolio_id)
                        VALUES 
                        (:media_id, :portfolio_id)
                    ");

                    $stmt->execute([
                        ':media_id' => $media_id,
                        ':portfolio_id' => $portfolio_id
                    ]);
                }
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Portfolio item added successfully!']);
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
    <title>Add Portfolio Item</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/log_form.css">
    <link rel="stylesheet" href="css/file_preview.css">
    <link rel="stylesheet" href="css/portfolio.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/file_upload.js"></script>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <h2>Add Portfolio Item</h2>
        
        <form id="addPortfolioForm" action="add_portfolio.php" method="POST" enctype="multipart/form-data" onsubmit="uploadFiles(event)">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
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

            <div class="form-group">
                <label for="media">Upload Media Files:</label>
                <input type="file" id="media" name="media[]" multiple accept="image/*,video/*" onchange="showSelectedFiles(this)">
                <div id="selectedFiles" class="selected-files"></div>
                <div id="previewArea" class="preview-area"></div>
            </div>

            <button type="submit" class="submit-button">Add to Portfolio</button>
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
        
        files.forEach(file => {
            selectedFiles.push(file);
            const previewContainer = createPreviewContainer(file);
            previewArea.appendChild(previewContainer);
        });

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
        preview.innerHTML = `
            <div class="video-thumbnail">
                <span class="video-icon">🎥</span>
                <span>${file.name}</span>
            </div>
        `;
        container.appendChild(preview);
    }

    function removeFile(button) {
        const container = button.closest('.preview-container');
        const fileName = container.dataset.fileName;
        
        selectedFiles = selectedFiles.filter(file => file.name !== fileName);
        container.remove();
    }

    function uploadFiles(event) {
        event.preventDefault();
        
        const form = document.getElementById('addPortfolioForm');
        const formData = new FormData(form);
        
        formData.delete('media[]');
        selectedFiles.forEach(file => {
            formData.append('media[]', file);
        });
        
        const progressBar = $('.progress-bar');
        
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
                    const result = JSON.parse(response);
                    if (result.success) {
                        alert(result.message);
                        window.location.href = 'portfolio.php';
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    alert('Error processing response');
                }
            },
            error: function(xhr, status, error) {
                console.error('Upload error:', error);
                alert('An error occurred during upload: ' + error);
            }
        });
    }
    </script>
</body>
</html>