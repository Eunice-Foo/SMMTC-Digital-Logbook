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
            alert(`File "${file.name}" already exists in this portfolio.`);
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
    if (confirm('Are you sure you want to remove this file?')) {
        deletedMediaIds.push(mediaId);
        button.closest('.preview-container').remove();
    }
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
    
    // Debug information
    console.log('Form submission started');
    
    // Add deleted media IDs
    formData.append('deleted_media_ids', JSON.stringify(deletedMediaIds));
    console.log('Deleted media IDs:', deletedMediaIds);
    
    // Clear any existing media[] entries
    formData.delete('media[]');
    
    // Add only new files that don't exist in the database
    let newFilesCount = 0;
    selectedFiles.forEach(file => {
        if (!existingFiles.has(file.name)) {
            formData.append('media[]', file);
            newFilesCount++;
        }
    });
    console.log(`Adding ${newFilesCount} new files`);
    
    // Debug form data
    for (const pair of formData.entries()) {
        if (pair[0] !== 'media[]') { // Don't log binary file data
            console.log(`${pair[0]}: ${pair[1]}`);
        }
    }
    
    // Set a longer timeout for large files
    $.ajax({
        url: form.action,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 300000, // 5 minutes
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = (evt.loaded / evt.total) * 100;
                    progressBar.width(percentComplete + '%');
                    progressBar.text(Math.round(percentComplete) + '%');
                    console.log(`Upload progress: ${Math.round(percentComplete)}%`);
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            console.log('Raw server response:', response);
            
            try {
                // Handle responses that may already be parsed as objects by jQuery
                let result;
                if (typeof response === 'object') {
                    result = response;
                    console.log('Response is already a JSON object');
                } else {
                    // If it's a string, try to find and parse JSON
                    const jsonStart = response.indexOf('{');
                    if (jsonStart >= 0) {
                        const jsonStr = response.substring(jsonStart);
                        console.log('Extracting JSON from response:', jsonStr);
                        result = JSON.parse(jsonStr);
                    } else {
                        result = JSON.parse(response);
                    }
                }
                
                console.log('Parsed result:', result);
                
                if (result.success) {
                    alert(result.message || 'Portfolio updated successfully!');
                    window.location.href = 'view_portfolio.php?id=' + getPortfolioId();
                } else {
                    alert('Error: ' + (result.message || 'Unknown error'));
                }
            } catch (e) {
                console.error('JSON parsing error:', e);
                console.error('Response that caused the error:', response);
                
                // If we can't parse the response but it contains success message
                if (typeof response === 'string' && response.includes('Portfolio updated successfully')) {
                    alert('Portfolio updated successfully!');
                    window.location.href = 'view_portfolio.php?id=' + getPortfolioId();
                } else {
                    alert('There was an error updating the portfolio. Check the console for details.');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error status:', status);
            console.error('AJAX error message:', error);
            console.error('Server response:', xhr.responseText);
            
            alert('Error: ' + error);
        },
        complete: function() {
            // Hide or reset progress bar when done
            setTimeout(() => {
                progressContainer.hide();
            }, 1000);
        }
    });
}

function getPortfolioId() {
    // Extract portfolio ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}