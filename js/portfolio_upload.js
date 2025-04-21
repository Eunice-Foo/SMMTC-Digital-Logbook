// Global variables
let selectedFiles = [];

document.addEventListener('DOMContentLoaded', function() {
    // Initialize video thumbnails for uploaded videos
    if (typeof generateVideoThumbnails === 'function') {
        generateVideoThumbnails();
    }
    
    // Initialize tools input
    initializeToolsInput();
});

function showSelectedFiles(input) {
    const previewArea = document.getElementById('previewArea');
    const files = Array.from(input.files);
    
    files.forEach(file => {
        selectedFiles.push(file);
        const previewContainer = createPreviewContainer(file);
        previewArea.appendChild(previewContainer);
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

function uploadFiles(event) {
    event.preventDefault();
    
    // Validate that files are selected
    if (selectedFiles.length === 0) {
        showErrorToast('Please select at least one media file to upload', 'No Files Selected', 'OK');
        return;
    }

    // Show loading overlay
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.classList.add('active');
    
    const form = document.getElementById('addPortfolioForm');
    const formData = new FormData(form);
    
    // Show progress bar
    const progressBar = $('.progress-bar');
    const progressContainer = $('.progress');
    progressBar.width('0%').text('0%');
    progressContainer.show();
    
    // Clear any existing media[] entries to prevent duplicates
    formData.delete('media[]');
    
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
            // Hide loading overlay
            loadingOverlay.classList.remove('active');
            
            try {
                // Parse response if it's a string
                let result;
                if (typeof response === 'object') {
                    result = response;
                } else {
                    result = JSON.parse(response);
                }
                
                if (result.success) {
                    // Show success toast
                    showSuccessToast(result.message || 'Portfolio item added successfully!', 'Success');
                    
                    // Reset the form and preview area
                    form.reset();
                    selectedFiles = [];
                    document.getElementById('previewArea').innerHTML = '';
                    document.getElementById('selectedTools').innerHTML = '';
                    
                    // Redirect to portfolio page after delay
                    setTimeout(() => {
                        window.location.href = 'portfolio.php';
                    }, 2000);
                } else {
                    // Show error toast
                    showErrorToast(result.message || 'Unknown error occurred', 'Upload Failed', 'OK');
                }
            } catch (e) {
                showErrorToast('There was a problem processing the server response', 'Error', 'OK');
            }
            
            // Hide progress bar
            progressContainer.hide();
        },
        error: function(xhr, status, error) {
            // Hide loading overlay
            loadingOverlay.classList.remove('active');
            
            // Show specific error message based on HTTP status
            let errorMessage = 'Upload failed';
            if (xhr.status === 413) {
                errorMessage = 'File too large. Please reduce file size and try again.';
            } else if (xhr.status === 0) {
                errorMessage = 'Connection lost or timed out. Please try again.';
            } else {
                errorMessage = `${error} (Status: ${xhr.status})`;
            }
            
            // Show error toast notification
            showErrorToast(errorMessage, 'Connection Error', 'Try Again');
            
            // Hide progress bar
            progressContainer.hide();
        }
    });
}