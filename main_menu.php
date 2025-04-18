<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/media_count_label.php';
require_once 'components/portfolio_card.php';
require_once 'components/category_tabs.php';

$search_results = [];
$total_results = 0;
$items_per_page = 8; // Reduced from 12 to improve initial load time
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($page - 1) * $items_per_page;
$category_filter = isset($_REQUEST['category']) ? $_REQUEST['category'] : 'all';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $search = $_POST['search'];
    if (empty($search)) {
        $stmt = $conn->prepare("
            SELECT 
                p.portfolio_id,
                p.portfolio_title, 
                p.portfolio_description, 
                p.portfolio_date, 
                p.portfolio_time, 
                p.category,
                u.user_id,
                u.user_name as username,
                COALESCE(s.full_name, sv.supervisor_name) as full_name,
                m.file_name as media, 
                m.file_type,
                (SELECT COUNT(*) FROM portfolio_media pm2 WHERE pm2.portfolio_id = p.portfolio_id) as media_count
            FROM portfolio p
            INNER JOIN user u ON p.user_id = u.user_id
            LEFT JOIN student s ON u.user_id = s.student_id
            LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id
            LEFT JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
            LEFT JOIN media m ON pm.media_id = m.media_id
            " . ($category_filter !== 'all' ? "WHERE p.category = :category" : "") . "
            GROUP BY p.portfolio_id
            ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
            LIMIT :limit OFFSET :offset
        ");
        
        if ($category_filter !== 'all') {
            $stmt->bindParam(':category', $category_filter);
        }
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    } else {
        // Search query with category filter
        $stmt = $conn->prepare("
            SELECT 
                p.portfolio_id,
                p.portfolio_title, 
                p.portfolio_description, 
                p.portfolio_date, 
                p.portfolio_time, 
                p.category,
                u.user_id,
                u.user_name as username,
                COALESCE(s.full_name, sv.supervisor_name) as full_name,
                m.file_name as media, 
                m.file_type,
                (SELECT COUNT(*) FROM portfolio_media pm2 WHERE pm2.portfolio_id = p.portfolio_id) as media_count
            FROM portfolio p
            INNER JOIN user u ON p.user_id = u.user_id
            LEFT JOIN student s ON u.user_id = s.student_id
            LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id
            LEFT JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
            LEFT JOIN media m ON pm.media_id = m.media_id
            WHERE (p.portfolio_title LIKE :search OR p.portfolio_description LIKE :search)
            " . ($category_filter !== 'all' ? "AND p.category = :category" : "") . "
            GROUP BY p.portfolio_id
            ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $search_param = "%" . $search . "%";
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        if ($category_filter !== 'all') {
            $stmt->bindParam(':category', $category_filter);
        }
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total results for pagination
    if (empty($search)) {
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT p.portfolio_id) as total
            FROM portfolio p
            " . ($category_filter !== 'all' ? "WHERE p.category = :category" : "") . "
        ");
        if ($category_filter !== 'all') {
            $stmt->bindParam(':category', $category_filter);
        }
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT p.portfolio_id) as total
            FROM portfolio p
            WHERE (p.portfolio_title LIKE :search OR p.portfolio_description LIKE :search)
            " . ($category_filter !== 'all' ? "AND p.category = :category" : "") . "
        ");
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        if ($category_filter !== 'all') {
            $stmt->bindParam(':category', $category_filter);
        }
    }
    
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_results = $result['total'];
    
    if (isset($_POST['ajax'])) {
        echo json_encode([
            'results' => $search_results,
            'total' => $total_results
        ]);
        exit;
    }
} else {
    // Initial page load
    $stmt = $conn->prepare("
        SELECT 
            p.portfolio_id,
            p.portfolio_title, 
            p.portfolio_description, 
            p.portfolio_date, 
            p.portfolio_time, 
            p.category,
            u.user_id,
            u.user_name as username,
            COALESCE(s.full_name, sv.supervisor_name) as full_name,
            m.file_name as media, 
            m.file_type,
            (SELECT COUNT(*) FROM portfolio_media pm2 WHERE pm2.portfolio_id = p.portfolio_id) as media_count
        FROM portfolio p
        INNER JOIN user u ON p.user_id = u.user_id
        LEFT JOIN student s ON u.user_id = s.student_id
        LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id
        LEFT JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
        LEFT JOIN media m ON pm.media_id = m.media_id
        " . ($category_filter !== 'all' ? "WHERE p.category = :category" : "") . "
        GROUP BY p.portfolio_id
        ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
        LIMIT :limit OFFSET :offset
    ");
    
    if ($category_filter !== 'all') {
        $stmt->bindParam(':category', $category_filter);
    }
    $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total results
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT p.portfolio_id) as total
        FROM portfolio p
        " . ($category_filter !== 'all' ? "WHERE p.category = :category" : "") . "
    ");
    
    if ($category_filter !== 'all') {
        $stmt->bindParam(':category', $category_filter);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_results = $result['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Portfolios</title>
    
    <!-- Add resource hints for faster loading -->
    <link rel="preload" href="css/theme.css" as="style">
    <link rel="preload" href="css/main_menu.css" as="style">
    <link rel="preconnect" href="https://cdn-uicons.flaticon.com">
    <link rel="preconnect" href="https://code.jquery.com">
    
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/main_menu.css">
    <link rel="stylesheet" href="css/category_tabs.css">
    
    <!-- Defer non-critical JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
    
    <style>
        /* Inline critical styles */
        .placeholder-image {
            background: linear-gradient(110deg, #ececec 8%, #f5f5f5 18%, #ececec 33%);
            background-size: 200% 100%;
            animation: 1.5s shine linear infinite;
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        @keyframes shine {
            to { background-position-x: -200%; }
        }
        
        .card-media {
            position: relative;
        }
    </style>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <div class="search-section">
            <form action="main_menu.php" method="post" class="search-form">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search creative works..." 
                    class="search-input"
                    value="<?php echo isset($_POST['search']) ? htmlspecialchars($_POST['search']) : ''; ?>"
                >
                <button type="submit" class="search-button">Search</button>
            </form>
        </div>

        <?php renderCategoryTabs($category_filter); ?>

        <div class="results-count">
            <span><?php echo $total_results; ?> results</span>
        </div>
        
        <div class="gallery" id="gallery">
            <?php if (empty($search_results)): ?>
                <div class="empty-state">
                    <h3>No portfolio items found</h3>
                    <p>Try adjusting your search or explore more creative works</p>
                </div>
            <?php else: ?>
                <?php foreach ($search_results as $result): ?>
                    <div class="portfolio-card" onclick="window.location.href='view_portfolio.php?id=<?php echo $result['portfolio_id']; ?>'" data-category="<?php echo $result['category']; ?>">
                        <div class="card-media">
                            <!-- Add placeholder before actual content -->
                            <div class="placeholder-image"></div>
                            
                            <?php if (isset($result['file_type']) && strpos($result['file_type'], 'video/') === 0): ?>
                                <!-- Use data attributes to lazy load video thumbnails -->
                                <div class="video-thumbnail" data-video="<?php echo htmlspecialchars($result['media']); ?>"></div>
                                <div class="video-badge">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8 0C3.6 0 0 3.6 0 8C0 12.4 3.6 16 8 16C12.4 16 16 12.4 16 8C16 3.6 12.4 0 8 0ZM6 11.5V4.5L12 8L6 11.5Z" fill="white"/>
                                    </svg>
                                </div>
                            <?php else: ?>
                                <!-- Lazy load images -->
                                <img 
                                    src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E" 
                                    data-src="uploads/<?php echo htmlspecialchars($result['media']); ?>" 
                                    alt="Portfolio Media" 
                                    class="lazy-image"
                                    loading="lazy"
                                    width="300"
                                    height="180">
                            <?php endif; ?>
                            
                            <?php if ($result['media_count'] > 1): ?>
                                <div class="media-count">+<?php echo $result['media_count'] - 1; ?> more</div>
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($result['portfolio_title']); ?></h3>
                            </div>
                            <div class="card-meta">
                                <a href="user_portfolio_profile.php?id=<?php echo $result['user_id']; ?>" class="author" onclick="event.stopPropagation();">
                                    <?php echo htmlspecialchars(!empty($result['full_name']) ? $result['full_name'] : $result['username']); ?>
                                </a>
                                <div class="timestamp">
                                    <?php echo date('M d, Y', strtotime($result['portfolio_date'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="loading-indicator" id="loadingIndicator">
                Loading more items...
            </div>
        </div>
    </div>

    <script>
        // Optimized lazy loading with IntersectionObserver
        document.addEventListener('DOMContentLoaded', function() {
            // Lazy load images
            const lazyImages = document.querySelectorAll('.lazy-image');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.onload = () => {
                                // Remove placeholder when image loads
                                const placeholder = img.previousElementSibling;
                                if (placeholder && placeholder.classList.contains('placeholder-image')) {
                                    placeholder.style.display = 'none';
                                }
                                img.classList.add('loaded');
                            };
                        }
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '200px 0px' // Start loading images before they're visible
            });

            lazyImages.forEach(img => {
                imageObserver.observe(img);
            });

            // Lazy load video thumbnails
            const videoThumbnails = document.querySelectorAll('.video-thumbnail[data-video]');
            const videoObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const thumbnail = entry.target;
                        const videoPath = thumbnail.dataset.video;
                        
                        if (videoPath) {
                            // Create canvas for thumbnail
                            const canvas = document.createElement('canvas');
                            canvas.width = 300;
                            canvas.height = 180;
                            canvas.className = 'video-canvas';
                            thumbnail.appendChild(canvas);
                            
                            // Generate thumbnail
                            generateVideoThumbnail(videoPath, canvas, () => {
                                // Remove placeholder when thumbnail is ready
                                const placeholder = thumbnail.previousElementSibling;
                                if (placeholder && placeholder.classList.contains('placeholder-image')) {
                                    placeholder.style.display = 'none';
                                }
                            });
                            
                            // Remove data attribute to prevent regeneration
                            thumbnail.removeAttribute('data-video');
                        }
                        
                        observer.unobserve(thumbnail);
                    }
                });
            }, {
                rootMargin: '200px 0px'
            });
            
            videoThumbnails.forEach(thumbnail => {
                videoObserver.observe(thumbnail);
            });

            // Generate video thumbnail efficiently
            function generateVideoThumbnail(videoPath, canvas, callback) {
                const video = document.createElement('video');
                video.style.display = 'none';
                video.preload = 'metadata';
                video.muted = true;
                video.src = 'uploads/' + videoPath;
                
                video.addEventListener('loadeddata', function() {
                    setTimeout(() => {
                        video.currentTime = 1;
                    }, 200);
                });
                
                video.addEventListener('seeked', function() {
                    try {
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        
                        // Clean up
                        video.pause();
                        video.src = '';
                        video.load();
                        video.remove();
                        
                        if (callback) callback();
                    } catch (e) {
                        console.error('Error generating thumbnail:', e);
                    }
                });
                
                document.body.appendChild(video);
                video.load();
            }

            // Use IntersectionObserver for infinite scroll
            const loadingIndicator = document.getElementById('loadingIndicator');
            if (loadingIndicator) {
                const loadMoreObserver = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting && !loading && hasMore) {
                        loadMore();
                    }
                });
                
                loadMoreObserver.observe(loadingIndicator);
            }
        });

        let currentPage = 1;
        let loading = false;
        let hasMore = <?php echo $total_results > ($page * $items_per_page) ? 'true' : 'false'; ?>;
        let currentCategory = '<?php echo $category_filter; ?>';

        // Optimized loadMore function
        function loadMore() {
            loading = true;
            document.getElementById('loadingIndicator').style.display = 'block';
            currentPage++;
            
            // Use fetch API instead of jQuery AJAX
            fetch('main_menu.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    page: currentPage,
                    search: document.querySelector('input[name="search"]')?.value || '',
                    category: currentCategory,
                    ajax: true
                })
            })
            .then(response => response.json())
            .then(response => {
                document.getElementById('loadingIndicator').style.display = 'none';
                
                if (response.results && response.results.length > 0) {
                    const gallery = document.getElementById('gallery');
                    
                    response.results.forEach(result => {
                        const card = document.createElement('div');
                        card.className = 'portfolio-card';
                        card.setAttribute('data-category', result.category);
                        card.onclick = function() { 
                            window.location.href = `view_portfolio.php?id=${result.portfolio_id}`; 
                        };
                        
                        // Create media HTML with placeholders and lazy loading
                        let mediaHTML = `<div class="card-media"><div class="placeholder-image"></div>`;
                        
                        if (result.file_type && result.file_type.startsWith('video/')) {
                            mediaHTML += `
                                <div class="video-thumbnail" data-video="${result.media}"></div>
                                <div class="video-badge">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8 0C3.6 0 0 3.6 0 8C0 12.4 3.6 16 8 16C12.4 16 16 12.4 16 8C16 3.6 12.4 0 8 0ZM6 11.5V4.5L12 8L6 11.5Z" fill="white"/>
                                    </svg>
                                </div>`;
                        } else {
                            mediaHTML += `<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E" 
                                data-src="uploads/${result.media}" alt="Portfolio Media" class="lazy-image" loading="lazy" width="300" height="180">`;
                        }
                        
                        if (result.media_count > 1) {
                            mediaHTML += `<div class="media-count">+${result.media_count - 1} more</div>`;
                        }
                        
                        mediaHTML += `</div>`;
                        
                        // Add content section
                        const contentHTML = `
                            <div class="card-content">
                                <div class="card-header">
                                    <h3>${result.portfolio_title}</h3>
                                </div>
                                <div class="card-meta">
                                    <a href="user_portfolio_profile.php?id=${result.user_id}" class="author" onclick="event.stopPropagation();">
                                        ${result.full_name || result.username}
                                    </a>
                                    <div class="timestamp">${new Date(result.portfolio_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</div>
                                </div>
                            </div>`;
                        
                        // Combine HTML
                        card.innerHTML = mediaHTML + contentHTML;
                        gallery.insertBefore(card, document.getElementById('loadingIndicator'));
                    });
                    
                    // Initialize lazy loading for newly added content
                    initLazyLoading();
                    
                    hasMore = response.total > (currentPage * <?php echo $items_per_page; ?>);
                } else {
                    hasMore = false;
                    document.getElementById('loadingIndicator').textContent = 'No more items to display';
                }
                loading = false;
            })
            .catch(error => {
                console.error('Error loading more items:', error);
                document.getElementById('loadingIndicator').style.display = 'none';
                loading = false;
            });
        }

        // Function to initialize lazy loading for newly added content
        function initLazyLoading() {
            // Lazy load new images
            const newLazyImages = document.querySelectorAll('.lazy-image:not(.loaded)');
            if (newLazyImages.length > 0) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.onload = () => {
                                    const placeholder = img.previousElementSibling;
                                    if (placeholder && placeholder.classList.contains('placeholder-image')) {
                                        placeholder.style.display = 'none';
                                    }
                                    img.classList.add('loaded');
                                };
                            }
                            observer.unobserve(img);
                        }
                    });
                }, { rootMargin: '200px 0px' });

                newLazyImages.forEach(img => imageObserver.observe(img));
            }

            // Process new video thumbnails
            const newVideoThumbnails = document.querySelectorAll('.video-thumbnail[data-video]');
            if (newVideoThumbnails.length > 0) {
                const videoObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const thumbnail = entry.target;
                            const videoPath = thumbnail.dataset.video;
                            
                            if (videoPath) {
                                const canvas = document.createElement('canvas');
                                canvas.width = 300;
                                canvas.height = 180;
                                canvas.className = 'video-canvas';
                                thumbnail.appendChild(canvas);
                                
                                // Generate video thumbnail
                                const video = document.createElement('video');
                                video.style.display = 'none';
                                video.preload = 'metadata';
                                video.muted = true;
                                video.src = 'uploads/' + videoPath;
                                
                                video.addEventListener('loadeddata', function() {
                                    video.currentTime = 1;
                                });
                                
                                video.addEventListener('seeked', function() {
                                    try {
                                        const ctx = canvas.getContext('2d');
                                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                                        
                                        // Hide placeholder
                                        const placeholder = thumbnail.previousElementSibling;
                                        if (placeholder && placeholder.classList.contains('placeholder-image')) {
                                            placeholder.style.display = 'none';
                                        }
                                        
                                        // Clean up
                                        video.pause();
                                        video.src = '';
                                        video.load();
                                        video.remove();
                                    } catch (e) {
                                        console.error('Error generating thumbnail:', e);
                                    }
                                });
                                
                                document.body.appendChild(video);
                                video.load();
                                
                                // Remove data attribute
                                thumbnail.removeAttribute('data-video');
                            }
                            
                            observer.unobserve(thumbnail);
                        }
                    });
                }, { rootMargin: '200px 0px' });

                newVideoThumbnails.forEach(thumbnail => videoObserver.observe(thumbnail));
            }
        }

        // Initialize category filtering
        document.addEventListener('DOMContentLoaded', function() {
            const categoryTabs = document.querySelectorAll('.category-tab');
            
            categoryTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    categoryTabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    const selectedCategory = tab.dataset.category;
                    currentCategory = selectedCategory;
                    
                    window.location.href = 'main_menu.php?category=' + selectedCategory;
                });
            });
        });
    </script>
</body>
</html>