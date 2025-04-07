function generateThumbnails() {
    const videoElements = document.querySelectorAll('.video-thumbnail video');
    videoElements.forEach(video => {
        const canvas = video.nextElementSibling;
        const thumbnailContainer = video.closest('.thumbnail-container');
        
        if (!video || !canvas || !thumbnailContainer) return;

        video.style.display = 'none';
        
        video.addEventListener('loadeddata', function() {
            video.currentTime = 1;
        });

        video.addEventListener('seeked', function() {
            try {
                // Calculate dimensions maintaining aspect ratio
                const aspectRatio = video.videoWidth / video.videoHeight;
                const containerWidth = 150;
                const containerHeight = 150;
                let width, height;

                if (aspectRatio > 1) {
                    // Landscape video
                    width = containerWidth;
                    height = containerWidth / aspectRatio;
                } else {
                    // Portrait video
                    height = containerHeight;
                    width = containerHeight * aspectRatio;
                }

                // Set canvas dimensions to match video proportions
                canvas.width = width;
                canvas.height = height;
                
                // Draw the video frame
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Clean up
                URL.revokeObjectURL(video.src);
                video.remove();
                
                console.log('Thumbnail generated successfully');
            } catch (error) {
                console.error('Error generating thumbnail:', error);
                canvas.remove();
                video.remove();
            }
        });

        video.addEventListener('error', function() {
            console.error('Error loading video:', video.src);
            canvas.remove();
            video.remove();
        });
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', generateThumbnails);