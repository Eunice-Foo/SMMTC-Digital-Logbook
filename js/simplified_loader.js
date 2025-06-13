/**
 * Simplified image loading for main menu
 */
document.addEventListener('DOMContentLoaded', function() {
    // Simple function to load images immediately
    function loadImagesImmediately() {
        const lazyImages = document.querySelectorAll('.lazy-image');
        
        lazyImages.forEach(img => {
            if (img.dataset.src) {
                // Simply set the src attribute
                img.src = img.dataset.src;
                img.classList.add('loaded');
                
                // Hide placeholder
                const placeholder = img.previousElementSibling;
                if (placeholder && placeholder.classList.contains('placeholder-image')) {
                    placeholder.style.opacity = '0';
                    setTimeout(() => { 
                        placeholder.style.display = 'none';
                    }, 300);
                }
            }
        });
    }
    
    // Load first batch immediately
    loadImagesImmediately();
    
    // Set up infinite scroll with simplified loading
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator && typeof PortfolioLoader !== 'undefined') {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !PortfolioLoader.loading && PortfolioLoader.hasMore) {
                PortfolioLoader.loadMore();
                // Will use existing AJAX but with simplified image loading
            }
        }, { rootMargin: '200px 0px' });
        
        observer.observe(loadingIndicator);
        
        // Override the renderNewItems function to use our simplified loader
        const originalRenderNewItems = PortfolioLoader.renderNewItems;
        PortfolioLoader.renderNewItems = function(items) {
            originalRenderNewItems.call(this, items);
            // After rendering, load all new images immediately
            setTimeout(loadImagesImmediately, 100);
        };
    }
});