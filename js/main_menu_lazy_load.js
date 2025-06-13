/**
 * Main menu optimization with focus on minimizing main-thread work
 * Implements performance best practices for image loading and infinite scroll
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize with requestIdleCallback if available
    if ('requestIdleCallback' in window) {
        requestIdleCallback(initializeLazyLoaders);
    } else {
        // Fallback to setTimeout
        setTimeout(initializeLazyLoaders, 200);
    }
    
    // Set up category tabs
    setupCategoryTabs();
});

// Use module pattern to avoid global scope pollution
const PortfolioLoader = {
    loading: false,
    currentPage: window.portfolioData ? window.portfolioData.currentPage : 1,
    hasMore: window.portfolioData ? window.portfolioData.hasMore : false,
    category: window.portfolioData ? window.portfolioData.category : 'all',
    itemsPerPage: window.portfolioData?.itemsPerPage || 8,
    
    init: function() {
        this.observeInfiniteScroll();
    },
    
    loadMore: function() {
        if (this.loading || !this.hasMore) return;
        
        this.loading = true;
        const loadingIndicator = document.getElementById('loadingIndicator');
        if (loadingIndicator) loadingIndicator.textContent = 'Loading...';
        
        // Add category filter to the fetch URL if needed
        let fetchUrl = `main_menu.php?page=${this.currentPage + 1}&ajax=1`;
        if (this.category !== 'all') {
            fetchUrl += `&category=${encodeURIComponent(this.category)}`;
        }
        
        fetch(fetchUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => this.handleLoadMoreResponse(data))
        .catch(error => {
            console.error('Error loading more items:', error);
            const loadingIndicator = document.getElementById('loadingIndicator');
            if (loadingIndicator) loadingIndicator.textContent = 'Error loading items';
            this.loading = false;
        });
    },
    
    handleLoadMoreResponse: function(response) {
        const loadingIndicator = document.getElementById('loadingIndicator');
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        
        if (response.results && response.results.length > 0) {
            this.renderNewItems(response.results);
            this.hasMore = response.total > (this.currentPage * this.itemsPerPage);
        } else {
            this.hasMore = false;
            if (loadingIndicator) loadingIndicator.textContent = 'No more items to display';
        }
        
        this.loading = false;
    },
    
    renderNewItems: function(items) {
        const gallery = document.getElementById('gallery');
        if (!gallery || !items.length) return;
        
        const fragment = document.createDocumentFragment();
        const loadingIndicator = document.getElementById('loadingIndicator');
        
        items.forEach(item => {
            const card = this.createPortfolioCard(item);
            fragment.appendChild(card);
        });
        
        if (loadingIndicator) {
            gallery.insertBefore(fragment, loadingIndicator);
        } else {
            gallery.appendChild(fragment);
        }
        
        // Initialize lazy loading for new content with low priority
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => initializeLazyLoaders());
        } else {
            setTimeout(() => initializeLazyLoaders(), 50);
        }
    },
    
    createPortfolioCard: function(item) {
        const card = document.createElement('div');
        card.className = 'portfolio-card';
        card.setAttribute('data-category', item.category);
        card.onclick = () => window.location.href = `view_portfolio.php?id=${item.portfolio_id}`;
        
        // Build HTML structure efficiently
        card.innerHTML = this.getCardHTML(item);
        return card;
    },
    
    getCardHTML: function(item) {
        const isVideo = item.file_type && item.file_type.startsWith('video/');
        
        const mediaHTML = `
            <div class="card-media">
                <div class="placeholder-image"></div>
                ${isVideo ? this.getVideoHTML(item) : this.getImageHTML(item)}
                ${item.media_count > 1 ? `<div class="media-count">+${item.media_count - 1} more</div>` : ''}
            </div>`;
            
        const contentHTML = `
            <div class="card-content">
                <div class="card-header">
                    <h3>${item.portfolio_title}</h3>
                </div>
                <div class="card-meta">
                    <a href="user_portfolio_profile.php?id=${item.user_id}" class="author" onclick="event.stopPropagation();">
                        ${item.full_name || item.username}
                    </a>
                    <div class="timestamp">${this.formatDate(item.portfolio_date)}</div>
                </div>
            </div>`;
            
        return mediaHTML + contentHTML;
    },
    
    getVideoHTML: function(item) {
        return `
            <div class="video-thumbnail" data-video="${item.media}"></div>
            <div class="video-badge">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 0C3.6 0 0 3.6 0 8C0 12.4 3.6 16 8 16C12.4 16 16 12.4 16 8C16 3.6 12.4 0 8 0ZM6 11.5V4.5L12 8L6 11.5Z" fill="white"/>
                </svg>
            </div>`;
    },
    
    getImageHTML: function(item) {
        // Check if item has a valid media property
        if (!item || !item.media) {
            console.warn('No media found for item:', item?.portfolio_title || 'Unknown item');
            // Return a more reliable placeholder
            return `<img src="images/default-placeholder.png" 
                alt="No image available" 
                class="loaded-image"
                onerror="this.src='uploads/default-placeholder.jpg'; this.onerror=null;">`;
        }
        
        // Handle media being an array or an object with file_name property
        const mediaPath = typeof item.media === 'object' && item.media.file_name 
            ? item.media.file_name 
            : item.media;
        
        // Direct loading with improved error handling
        return `<img src="uploads/${mediaPath}" 
            alt="${item.portfolio_title || 'Portfolio item'}" 
            class="loaded-image"
            onerror="this.onerror=null; this.src='images/default-placeholder.png'; console.warn('Failed to load image: ' + this.src);">`;
    },
    
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short', 
            day: 'numeric', 
            year: 'numeric'
        });
    },
    
    observeInfiniteScroll: function() {
        const loadingIndicator = document.getElementById('loadingIndicator');
        if (!loadingIndicator) return;
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && this.hasMore && !this.loading) {
                    this.loadMoreItems();
                }
            });
        }, {
            rootMargin: '100px',
        });
        
        observer.observe(loadingIndicator);
    },
    
    loadMoreItems: function() {
        if (this.loading || !this.hasMore) return;
        
        this.loading = true;
        const loadingIndicator = document.getElementById('loadingIndicator');
        if (loadingIndicator) loadingIndicator.style.display = 'block';
        this.currentPage++;
        
        const searchInput = document.querySelector('input[name="search"]');
        const searchValue = searchInput ? searchInput.value : '';
        
        const params = new URLSearchParams({
            page: this.currentPage,
            search: searchValue,
            category: this.category,
            ajax: true
        });
        
        fetch('main_menu.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(response => response.json())
        .then(data => this.handleLoadMoreResponse(data))
        .catch(error => {
            console.error('Error loading more items:', error);
            if (loadingIndicator) loadingIndicator.style.display = 'none';
            this.loading = false;
        });
    },
    
    handleLoadMoreResponse: function(response) {
        const loadingIndicator = document.getElementById('loadingIndicator');
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        
        if (response.results && response.results.length > 0) {
            this.renderNewItems(response.results);
            this.hasMore = response.total > (this.currentPage * this.itemsPerPage);
        } else {
            this.hasMore = false;
            if (loadingIndicator) loadingIndicator.textContent = 'No more items to display';
        }
        
        this.loading = false;
    },
    
    renderNewItems: function(items) {
        const gallery = document.getElementById('gallery');
        if (!gallery || !items.length) return;
        
        const fragment = document.createDocumentFragment();
        const loadingIndicator = document.getElementById('loadingIndicator');
        
        items.forEach(item => {
            const card = this.createPortfolioCard(item);
            fragment.appendChild(card);
        });
        
        if (loadingIndicator) {
            gallery.insertBefore(fragment, loadingIndicator);
        } else {
            gallery.appendChild(fragment);
        }
        
        // Initialize lazy loading for new content with low priority
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => initializeLazyLoaders());
        } else {
            setTimeout(() => initializeLazyLoaders(), 50);
        }
    },
    
    createPortfolioCard: function(item) {
        const card = document.createElement('div');
        card.className = 'portfolio-card';
        card.setAttribute('data-category', item.category);
        card.onclick = () => window.location.href = `view_portfolio.php?id=${item.portfolio_id}`;
        
        // Build HTML structure efficiently
        card.innerHTML = this.getCardHTML(item);
        return card;
    },
    
    getCardHTML: function(item) {
        const isVideo = item.file_type && item.file_type.startsWith('video/');
        
        const mediaHTML = `
            <div class="card-media">
                <div class="placeholder-image"></div>
                ${isVideo ? this.getVideoHTML(item) : this.getImageHTML(item)}
                ${item.media_count > 1 ? `<div class="media-count">+${item.media_count - 1} more</div>` : ''}
            </div>`;
            
        const contentHTML = `
            <div class="card-content">
                <div class="card-header">
                    <h3>${item.portfolio_title}</h3>
                </div>
                <div class="card-meta">
                    <a href="user_portfolio_profile.php?id=${item.user_id}" class="author" onclick="event.stopPropagation();">
                        ${item.full_name || item.username}
                    </a>
                    <div class="timestamp">${this.formatDate(item.portfolio_date)}</div>
                </div>
            </div>`;
            
        return mediaHTML + contentHTML;
    },
    
    getVideoHTML: function(item) {
        return `
            <div class="video-thumbnail" data-video="${item.media}"></div>
            <div class="video-badge">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 0C3.6 0 0 3.6 0 8C0 12.4 3.6 16 8 16C12.4 16 16 12.4 16 8C16 3.6 12.4 0 8 0ZM6 11.5V4.5L12 8L6 11.5Z" fill="white"/>
                </svg>
            </div>`;
    },
    
    getImageHTML: function(item) {
        // Check if item has a valid media property
        if (!item || !item.media) {
            console.warn('No media found for item:', item?.portfolio_title || 'Unknown item');
            // Return a more reliable placeholder
            return `<img src="images/default-placeholder.png" 
                alt="No image available" 
                class="loaded-image"
                onerror="this.src='uploads/default-placeholder.jpg'; this.onerror=null;">`;
        }
        
        // Handle media being an array or an object with file_name property
        const mediaPath = typeof item.media === 'object' && item.media.file_name 
            ? item.media.file_name 
            : item.media;
        
        // Direct loading with improved error handling
        return `<img src="uploads/${mediaPath}" 
            alt="${item.portfolio_title || 'Portfolio item'}" 
            class="loaded-image"
            onerror="this.onerror=null; this.src='images/default-placeholder.png'; console.warn('Failed to load image: ' + this.src);">`;
    },
    
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short', 
            day: 'numeric', 
            year: 'numeric'
        });
    }
};

// Separated functions to improve code organization and reuse

function initializeLazyLoaders() {
    // Only keep infinite scroll functionality
    observeInfiniteScroll();
}

function initializeLazyImages() {
    // Do nothing - no lazy loading needed
}

function initializeLazyVideos() {
    // Load all video thumbnails immediately
    const videoThumbnails = document.querySelectorAll('.video-thumbnail[data-video]');
    
    videoThumbnails.forEach(thumbnail => {
        const videoPath = thumbnail.dataset.video;
        if (videoPath) {
            generateVideoThumbnail(videoPath, thumbnail);
            thumbnail.removeAttribute('data-video');
        }
    });
}

function generateVideoThumbnail(videoPath, thumbnailElement) {
    const canvas = document.createElement('canvas');
    canvas.width = 300;
    canvas.height = 180;
    canvas.className = 'video-canvas';
    thumbnailElement.appendChild(canvas);
    
    // Create video element with optimized attributes
    const video = document.createElement('video');
    video.style.display = 'none';
    video.preload = 'metadata';
    video.muted = true;
    video.playsInline = true;
    
    // Set up event cleanup function to avoid memory leaks
    const cleanupVideo = () => {
        if (video) {
            video.pause();
            video.removeAttribute('src');
            video.load();
            if (video.parentNode) video.parentNode.removeChild(video);
        }
    };
    
    // Handle errors and timeouts
    const errorHandler = () => {
        cleanupVideo();
        thumbnailElement.classList.add('thumbnail-error');
    };
    
    video.onerror = errorHandler;
    
    // Set timeout to prevent hanging
    const timeout = setTimeout(errorHandler, 5000);
    
    video.addEventListener('loadeddata', function() {
        clearTimeout(timeout);
        try {
            video.currentTime = 1;
        } catch (e) {
            errorHandler();
        }
    });
    
    video.addEventListener('seeked', function() {
        clearTimeout(timeout);
        try {
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Remove placeholder
            const placeholder = thumbnailElement.previousElementSibling;
            if (placeholder && placeholder.classList.contains('placeholder-image')) {
                placeholder.style.opacity = '0';
                setTimeout(() => { placeholder.style.display = 'none'; }, 300);
            }
        } catch (e) {
            console.error('Error drawing video thumbnail:', e);
        } finally {
            cleanupVideo();
        }
    });
    
    // Set src and append to document only after all event handlers are set
    video.src = 'uploads/' + videoPath;
    document.body.appendChild(video);
}

function observeInfiniteScroll() {
    if (!('IntersectionObserver' in window)) return;
    
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (!loadingIndicator) return;
    
    const loadMoreObserver = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && !PortfolioLoader.loading && PortfolioLoader.hasMore) {
                PortfolioLoader.loadMore();
            }
        },
        { rootMargin: '100px 0px' }
    );
    
    loadMoreObserver.observe(loadingIndicator);
}

function setupCategoryTabs() {
    const categoryTabs = document.querySelectorAll('.category-tab');
    
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const selectedCategory = tab.dataset.category;
            if (selectedCategory) {
                window.location.href = 'main_menu.php?category=' + selectedCategory;
            }
        });
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    PortfolioLoader.init();
});