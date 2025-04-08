let selectedFiles = []; // Store new files

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
    
    // Clear any existing media[] entries
    formData.delete('media[]');
    
    // Add selected files
    selectedFiles.forEach(file => {
        formData.append('media[]', file);
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
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.width(percent + '%').text(percent + '%');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    alert(result.message);
                    window.location.href = 'logbook.php';
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (e) {
                console.error('Parse error:', e);
                alert('Error processing response');
            }
        },
        error: function() {
            alert('Error uploading files');
        }
    });
}

// Add this function to generate thumbnails for videos
function generateVideoThumbnails() {
    const thumbnailContainers = document.querySelectorAll('.video-thumbnail');
    
    thumbnailContainers.forEach(container => {
        const video = container.querySelector('video');
        const canvas = container.querySelector('.video-canvas');
        
        if (!video || !canvas) return;

        // Hide video but allow metadata loading
        video.style.display = 'none';
        
        video.addEventListener('loadeddata', function() {
            try {
                video.currentTime = 1;
            } catch (error) {
                console.error('Error setting video time:', error);
            }
        });

        video.addEventListener('seeked', function() {
            try {
                // Calculate dimensions maintaining aspect ratio
                const aspectRatio = video.videoWidth / video.videoHeight;
                const maxHeight = 150;
                let width, height;

                if (aspectRatio > 1) {
                    width = maxHeight * aspectRatio;
                    height = maxHeight;
                } else {
                    height = maxHeight;
                    width = maxHeight * aspectRatio;
                }

                // Set canvas dimensions
                canvas.width = width;
                canvas.height = height;
                
                // Draw the frame
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, width, height);
                
                // Don't remove video for existing previews
                video.style.display = 'none';
            } catch (error) {
                console.error('Error generating thumbnail:', error);
            }
        });

        video.addEventListener('error', function(e) {
            console.error('Video error:', e);
        });
    });
}

// Call this function when the document is ready
document.addEventListener('DOMContentLoaded', generateVideoThumbnails);