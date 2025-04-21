/**
 * Blur-up lazy loading for images
 * Uses IntersectionObserver to only load images when they come into view
 * and provides a smooth transition from blurry placeholder to sharp image
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeLazyLoading();
});

function initializeLazyLoading() {
    const lazyImages = document.querySelectorAll('img.lazy-image');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.dataset.src;
                    
                    if (src) {
                        // Create new image to preload
                        const newImg = new Image();
                        newImg.src = src;
                        
                        newImg.onload = () => {
                            // Replace src with the full resolution image
                            img.src = src;
                            
                            // Remove blur after a short delay (for smooth transition)
                            setTimeout(() => {
                                img.style.filter = 'none';
                            }, 50);
                            
                            // Add loaded class
                            img.classList.add('loaded');
                            
                            // Clean up data attribute
                            img.removeAttribute('data-src');
                        };
                        
                        // Stop observing this image
                        observer.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });
        
        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for browsers without IntersectionObserver
        lazyImages.forEach(img => {
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.style.filter = 'none';
            }
        });
    }
}