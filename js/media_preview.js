function showSelectedFiles(input) {
    const previewArea = document.getElementById('previewArea');
    const files = Array.from(input.files);
    
    files.forEach(file => {
        const previewContainer = createPreviewContainer(file);
        previewArea.appendChild(previewContainer);
    });
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
    preview.innerHTML = `<span class="video-icon">🎥</span><span>${file.name}</span>`;
    container.appendChild(preview);
}

// Add this function to generate thumbnails for videos
function generateVideoThumbnails() {
    const thumbnailContainers = document.querySelectorAll('.video-thumbnail');
    
    thumbnailContainers.forEach(container => {
        const video = container.querySelector('video');
        const canvas = container.querySelector('.video-canvas');
        
        if (video && canvas) {
            video.addEventListener('loadeddata', function() {
                video.currentTime = 1; // Get frame at 1 second
            });

            video.addEventListener('seeked', function() {
                const ctx = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                video.remove(); // Remove video element after generating thumbnail
            });
        }
    });
}

// Call this function when the document is ready
document.addEventListener('DOMContentLoaded', generateVideoThumbnails);