// Global variables
let selectedFiles = []; // Store new files
let deletedMediaIds = []; // Store IDs of files to delete
let existingFiles = new Set(); // Store names of existing files

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tools input
    initializeToolsInput();
    
    // Initialize video thumbnails
    generateVideoThumbnails();
    
    // Initialize existing media files for tracking
    if (window.existingMediaData) {
        window.existingMediaData.forEach(file => {
            if (file.file_name) {
                existingFiles.add(file.file_name);
            }
        });
    }
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
            // Replace alert with warning toast
            showWarningToast(`File "${file.name}" already exists in this portfolio.`, 'Duplicate File', 'OK');
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
    
    const video = document.createElement('video');
    video.preload = 'metadata';
    video.style.display = 'none';
    video.src = URL.createObjectURL(file);
    
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
    const container = button.closest('.preview-container');
    
    // Replace confirm with warning toast with callback
    showWarningToast(
        'Are you sure you want to remove this file?', 
        'Confirm Deletion', 
        'Delete', 
        function() {
            deletedMediaIds.push(mediaId);
            container.remove();
            // Confirmation toast after removal
            showSuccessToast('File marked for removal', 'File Removed');
        }
    );
}

function uploadFiles(event) {
    event.preventDefault();
    
    const form = document.getElementById('editPortfolioForm');
    const formData = new FormData(form);
    
    // Show progress bar
    const progressBar = $('.progress-bar');
    const progressContainer = $('.progress');
    progressBar.width('0%').text('0%');
    progressContainer.show();
    
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
    
    $.ajax({
        url: form.action,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 300000, // 5-minute timeout for large uploads
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
                // Handle responses that may already be parsed as objects by jQuery
                let result;
                if (typeof response === 'object') {
                    result = response;
                } else {
                    // If it's a string, try to find and parse JSON
                    const jsonStart = response.indexOf('{');
                    if (jsonStart >= 0) {
                        const jsonStr = response.substring(jsonStart);
                        result = JSON.parse(jsonStr);
                    } else {
                        result = JSON.parse(response);
                    }
                }
                
                if (result.success) {
                    // Replace alert with success toast
                    showSuccessToast(result.message || 'Portfolio updated successfully!', 'Portfolio Updated');
                    // Redirect after a short delay to see the toast
                    setTimeout(() => {
                        window.location.href = 'view_portfolio.php?id=' + getPortfolioIdFromUrl();
                    }, 2000);
                } else {
                    // Replace alert with error toast
                    showErrorToast(result.message || 'Unknown error occurred', 'Update Failed', 'Try Again');
                }
            } catch (e) {
                // Replace alert with error toast on parsing error
                showErrorToast('There was a problem processing the server response', 'Error', 'OK');
            }
            
            // Hide progress bar
            progressContainer.hide();
        },
        error: function(xhr, status, error) {
            // Show specific error message based on HTTP status
            let errorMessage = 'Unable to update portfolio';
            if (xhr.status === 413) {
                errorMessage = 'File too large. Please reduce file size and try again.';
            } else if (xhr.status === 0) {
                errorMessage = 'Connection lost or timed out. Please try again.';
            } else {
                errorMessage = `${error} (Status: ${xhr.status})`;
            }
            
            // Replace alert with error toast
            showErrorToast(errorMessage, 'Connection Error', 'Try Again');
            
            // Hide progress bar
            progressContainer.hide();
        }
    });
}

function getPortfolioIdFromUrl() {
    // Extract portfolio ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}