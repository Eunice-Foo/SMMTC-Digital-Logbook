// Global variables
let selectedFiles = []; // Store new files
let existingFiles = new Set(); // Store names of existing files

document.addEventListener('DOMContentLoaded', function() {
    console.log('file_upload.js loaded');
    
    // Initialize existing files if any
    if (typeof document.currentMediaFiles !== 'undefined' && document.currentMediaFiles) {
        document.currentMediaFiles.forEach(file => {
            if (file.file_name) {
                existingFiles.add(file.file_name);
            }
        });
    }

    // Initialize video thumbnails for uploaded videos
    if (typeof generateVideoThumbnails === 'function') {
        generateVideoThumbnails();
    }
});

function showSelectedFiles(input) {
    console.log('showSelectedFiles called');
    const previewArea = document.getElementById('previewArea');
    if (!previewArea) {
        console.error('Preview area not found');
        return;
    }
    
    const files = Array.from(input.files);
    console.log('Files selected:', files.length);
    
    files.forEach(file => {
        // Check if file already exists
        if (!existingFiles.has(file.name)) {
            // Add to selected files array for upload
            selectedFiles.push(file);
            console.log('Added file to selection:', file.name, file.type, file.size);
            
            // Create preview container
            const previewContainer = createPreviewContainer(file);
            previewArea.appendChild(previewContainer);
        } else {
            console.log('File already exists:', file.name);
            if (typeof showWarningToast === 'function') {
                showWarningToast(`File "${file.name}" already exists in this log entry.`, 'Duplicate File');
            } else {
                alert(`File "${file.name}" already exists in this log entry.`);
            }
        }
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
    } else {
        // For other file types (PDF, docs, etc)
        const preview = document.createElement('div');
        preview.className = 'preview-item file-icon';
        
        let icon = '📄';
        if (file.type.includes('pdf')) icon = '📝';
        else if (file.type.includes('word') || file.type.includes('document')) icon = '📃';
        
        preview.innerHTML = `<div class="file-icon-container">${icon}</div>`;
        container.appendChild(preview);
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

function uploadFiles(event) {
    event.preventDefault();
    
    const form = document.getElementById('addLogForm');
    const formData = new FormData(form);
    
    // Show progress bar
    const progressBar = $('.progress-bar');
    const progressContainer = $('.progress');
    progressBar.width('0%').text('0%');
    progressContainer.show();
    
    // Add files to formData
    selectedFiles.forEach(file => {
        formData.append('media[]', file);
    });
    
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
                // Try to parse the response as JSON
                let result;
                if (typeof response === 'object') {
                    result = response;
                } else {
                    result = JSON.parse(response);
                }
                
                if (result.success) {
                    // Use success toast notification
                    showSuccessToast(result.message);
                    
                    // Reset form after a short delay
                    setTimeout(function() {
                        form.reset();
                        selectedFiles = [];
                        document.getElementById('previewArea').innerHTML = '';
                        
                        // Redirect to logbook
                        window.location.href = 'logbook.php';
                    }, 2000);
                } else {
                    // Use error toast notification
                    showErrorToast(result.message || 'Unknown error occurred', 'Upload Failed', 'OK');
                }
            } catch (e) {
                console.error('Parse error:', e);
                console.error('Response:', response);
                
                // Show error toast for parsing errors
                showErrorToast('There was a problem processing your request!', 'Error', 'OK');
            }
            
            // Hide progress bar
            progressContainer.hide();
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', {xhr, status, error});
            
            // Show error toast for AJAX errors
            showErrorToast('Upload failed: ' + error, 'Connection Error', 'Try Again');
            
            // Hide progress bar
            progressContainer.hide();
        }
    });
}