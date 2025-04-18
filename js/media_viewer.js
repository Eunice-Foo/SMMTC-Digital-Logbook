let currentMediaIndex = 0;
let mediaFiles = [];

function initMediaViewer(files, startIndex = 0) {
    console.log('initMediaViewer called with files:', files);
    mediaFiles = files;
    const viewer = document.getElementById('mediaViewer');
    if (!viewer) {
        console.error('Media viewer element not found');
        return;
    }

    // Generate thumbnails
    const thumbnailsContainer = viewer.querySelector('.media-thumbnails');
    thumbnailsContainer.innerHTML = mediaFiles.map((file, index) => `
        <div class="thumbnail" onclick="showMedia(${index})">
            ${file.endsWith('.mp4') || file.endsWith('.mov') 
                ? `<div class="video-thumb">
                    <video style="display:none" preload="metadata">
                        <source src="uploads/${file}" type="video/mp4">
                    </video>
                    <canvas class="video-canvas"></canvas>
                    <div class="play-indicator">▶</div>
                   </div>`
                : `<img src="uploads/${file}" alt="Media Thumbnail">`
            }
        </div>
    `).join('');

    // Generate video thumbnails
    generateThumbnails();

    viewer.style.display = 'block';
    document.body.style.overflow = 'hidden';
    showMedia(startIndex); // Show the clicked media instead of first one
}

function generateThumbnails() {
    const videoThumbnails = document.querySelectorAll('.video-thumb');
    videoThumbnails.forEach(thumb => {
        const video = thumb.querySelector('video');
        const canvas = thumb.querySelector('.video-canvas');
        
        if (!video || !canvas) return;

        // Hide video but allow metadata loading
        video.style.display = 'none';
        
        // Load metadata and seek to 1s
        video.addEventListener('loadeddata', function() {
            video.currentTime = 1;
        });

        // Generate thumbnail once seeked
        video.addEventListener('seeked', function() {
            try {
                // Set canvas dimensions maintaining aspect ratio
                const aspectRatio = video.videoWidth / video.videoHeight;
                const thumbnailHeight = 80; // Gallery thumbnail height
                canvas.width = thumbnailHeight * aspectRatio;
                canvas.height = thumbnailHeight;
                
                // Draw the video frame
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Cleanup
                URL.revokeObjectURL(video.src);
                video.remove();

            } catch (error) {
                console.error('Error generating thumbnail:', error);
                handleThumbnailError(thumb);
            }
        });

        // Handle loading errors
        video.addEventListener('error', function() {
            console.error('Error loading video:', video.src);
            handleThumbnailError(thumb);
        });

        // Set timeout for video loading
        setTimeout(() => {
            if (!video.videoWidth) {
                console.warn('Video load timeout');
                handleThumbnailError(thumb);
            }
        }, 3000); // 3 second timeout for gallery view
    });
}

function handleThumbnailError(container) {
    container.innerHTML = `
        <div class="video-placeholder">
            <span>🎥</span>
        </div>
        <div class="play-indicator">▶</div>
    `;
}

function showMedia(index) {
    if (index < 0 || index >= mediaFiles.length) return;
    
    currentMediaIndex = index;
    const mediaDisplay = document.querySelector('.media-display');
    const file = mediaFiles[index];
    
    // Update thumbnails active state
    document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
    
    // Clear previous content
    mediaDisplay.innerHTML = '';
    
    // Handle different media types
    if (file.endsWith('.mp4') || file.endsWith('.mov')) {
        // Video handling
        const video = document.createElement('video');
        video.controls = true;
        video.autoplay = true;
        video.src = `uploads/${file}`;
        mediaDisplay.appendChild(video);
    } else {
        // Image handling with WebP support
        const picture = document.createElement('picture');
        
        // Add WebP source if not already a WebP
        if (!file.endsWith('.webp')) {
            const webpSource = document.createElement('source');
            webpSource.srcset = `uploads/${file.substring(0, file.lastIndexOf('.'))}.webp`;
            webpSource.type = 'image/webp';
            picture.appendChild(webpSource);
        }
        
        // Original image as fallback
        const img = document.createElement('img');
        img.src = `uploads/${file}`;
        img.style.maxHeight = '80vh';
        picture.appendChild(img);
        
        mediaDisplay.appendChild(picture);
    }

    // Scroll thumbnail into view
    const activeThumbnail = document.querySelector('.thumbnail.active');
    if (activeThumbnail) {
        activeThumbnail.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
}

function navigateMedia(direction) {
    let newIndex = currentMediaIndex + direction;
    if (newIndex < 0) newIndex = mediaFiles.length - 1;
    if (newIndex >= mediaFiles.length) newIndex = 0;
    showMedia(newIndex);
}

function closeMediaViewer() {
    const viewer = document.getElementById('mediaViewer');
    viewer.style.display = 'none';
    document.body.style.overflow = '';
    
    // Pause video if playing
    const video = viewer.querySelector('video');
    if (video) video.pause();
}

// Event Listeners
document.addEventListener('keydown', function(e) {
    const viewer = document.getElementById('mediaViewer');
    if (viewer.style.display === 'block') {
        if (e.key === 'ArrowLeft') navigateMedia(-1);
        if (e.key === 'ArrowRight') navigateMedia(1);
        if (e.key === 'Escape') closeMediaViewer();
    }
});

// Mouse wheel navigation
document.addEventListener('DOMContentLoaded', function() {
    const viewerContent = document.querySelector('.media-viewer-content');
    if (viewerContent) {
        viewerContent.addEventListener('wheel', function(e) {
            if (document.getElementById('mediaViewer').style.display === 'block') {
                e.preventDefault();
                navigateMedia(e.deltaY > 0 ? 1 : -1);
            }
        });
    }
});

// Click outside to close
document.addEventListener('DOMContentLoaded', function() {
    const viewer = document.getElementById('mediaViewer');
    if (viewer) {
        viewer.addEventListener('click', function(e) {
            if (e.target === viewer) {
                closeMediaViewer();
            }
        });
    }
});