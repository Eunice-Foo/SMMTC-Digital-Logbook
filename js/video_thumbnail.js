function generateVideoThumbnails() {
    const videoElements = document.querySelectorAll('.video-thumbnail video');
    
    videoElements.forEach(video => {
        const canvas = video.nextElementSibling;
        const thumbnailContainer = video.closest('.thumbnail-container');
        
        if (video && canvas) {
            // Hide video but don't use display:none to allow metadata loading
            video.style.opacity = '0';
            video.style.position = 'absolute';

            video.addEventListener('loadedmetadata', function() {
                video.currentTime = 1;
            });

            video.addEventListener('seeked', function() {
                // Set canvas dimensions
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                
                // Draw the video frame on the canvas
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Create and append an image element using the canvas data
                const img = document.createElement('img');
                img.src = canvas.toDataURL();
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                thumbnailContainer.insertBefore(img, video);

                // Remove video and canvas elements
                video.remove();
                canvas.remove();
            });

            // Handle video loading error
            video.addEventListener('error', function() {
                console.error('Error loading video:', video.src);
                canvas.remove();
                video.remove();
                
                // Show placeholder if video fails to load
                const placeholder = document.createElement('div');
                placeholder.className = 'video-placeholder';
                placeholder.innerHTML = '🎥';
                thumbnailContainer.appendChild(placeholder);
            });
        }
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', generateVideoThumbnails);