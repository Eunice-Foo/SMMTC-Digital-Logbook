// Global variables
let selectedFiles = []; // Store new files
let deletedMediaIds = []; // Store IDs of files to delete
let existingFiles = new Set(); // Store names of existing files

document.addEventListener('DOMContentLoaded', function() {
    // Initialize existing files
    const existingFilesArray = document.currentMediaFiles || [];
    existingFilesArray.forEach(file => {
        if (file.file_name) {
            existingFiles.add(file.file_name);
        }
    });

    // Initialize video thumbnails for existing videos
    generateVideoThumbnails();
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

    // Clear the file input
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
    
    const thumbnailContainer = document.createElement('div');
    thumbnailContainer.className = 'video-thumbnail';
    
    // Create video element
    const video = document.createElement('video');
    video.preload = 'metadata';
    video.style.display = 'none';
    video.src = URL.createObjectURL(file);
    
    // Create canvas and play button
    const canvas = document.createElement('canvas');
    canvas.className = 'video-canvas';
    
    const playButton = document.createElement('div');
    playButton.className = 'play-button';
    playButton.innerHTML = '▶';
    
    thumbnailContainer.appendChild(video);
    thumbnailContainer.appendChild(canvas);
    thumbnailContainer.appendChild(playButton);
    preview.appendChild(thumbnailContainer);
    container.appendChild(preview);
    
    // Generate thumbnail
    generateThumbnail(video, canvas);
}

function generateThumbnail(video, canvas) {
    video.addEventListener('loadeddata', function() {
        video.currentTime = 1;
    });

    video.addEventListener('seeked', function() {
        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(video.src);
        video.remove();
    });
}

function removeFile(button) {
    const container = button.closest('.preview-container');
    const fileName = container.dataset.fileName;
    selectedFiles = selectedFiles.filter(file => file.name !== fileName);
    container.remove();
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
    const totalSize = Array.from(formData.getAll('media[]'))
        .reduce((total, file) => total + file.size, 0);
    
    // Log total upload size
    console.log('Total upload size:', (totalSize / (1024 * 1024)).toFixed(2) + 'MB');
    
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
        success: handleUploadSuccess,
        error: handleUploadError
    });
}

function handleUploadSuccess(response) {
    try {
        // Try to parse the last JSON object if there are PHP warnings
        const jsonStr = response.substring(response.lastIndexOf('{'));
        const result = JSON.parse(jsonStr);
        
        if (result.success) {
            alert(result.message);
            window.location.href = 'logbook.php';
        } else {
            alert('Error: ' + (result.message || 'Unknown error occurred'));
        }
    } catch (e) {
        console.error('Parse error:', e);
        console.error('Response:', response);
        alert('Upload failed. File size may exceed server limits.');
    }
}

function handleUploadError(xhr, status, error) {
    console.error('AJAX error:', {xhr, status, error});
    alert('Upload failed: ' + error);
}