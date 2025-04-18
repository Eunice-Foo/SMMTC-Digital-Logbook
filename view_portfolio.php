<?php
// filepath: c:\xampp\htdocs\log\view_portfolio.php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/media_viewer.php';
require_once 'includes/image_converter.php'; // Add this line to include the image converter

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: portfolio.php");
    exit();
}

$portfolio_id = $_GET['id'];
$is_owner = false; // Initialize ownership flag

try {
    // Get portfolio details
    $stmt = $conn->prepare("
        SELECT 
            p.portfolio_id,
            p.portfolio_title,
            p.portfolio_description,
            p.portfolio_date,
            p.portfolio_time,
            p.category,
            p.tools,
            p.user_id,
            u.user_name,
            u.user_id,
            COALESCE(s.full_name, sv.supervisor_name) as full_name
        FROM portfolio p
        INNER JOIN user u ON p.user_id = u.user_id
        LEFT JOIN student s ON u.user_id = s.student_id
        LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id
        WHERE p.portfolio_id = :portfolio_id
    ");
    
    $stmt->bindParam(':portfolio_id', $portfolio_id, PDO::PARAM_INT);
    $stmt->execute();
    $portfolio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$portfolio) {
        header("Location: portfolio.php");
        exit();
    }
    
    // Check if current user is the owner of this portfolio
    $is_owner = ($_SESSION['user_id'] == $portfolio['user_id']);

    // Get all media for this portfolio
    $stmt = $conn->prepare("
        SELECT 
            m.media_id,
            m.file_name,
            m.file_type
        FROM portfolio_media pm
        INNER JOIN media m ON pm.media_id = m.media_id
        WHERE pm.portfolio_id = :portfolio_id
    ");
    
    $stmt->bindParam(':portfolio_id', $portfolio_id, PDO::PARAM_INT);
    $stmt->execute();
    $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format media files as comma-separated list for media viewer
    $media_files = array_map(function($item) {
        return $item['file_name'];
    }, $media);
    $media_files_str = implode(',', $media_files);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($portfolio['portfolio_title']); ?> - Portfolio</title>
    
    <!-- Preload critical CSS -->
    <link rel="preload" href="css/theme.css" as="style">
    <link rel="preload" href="css/media_viewer.css" as="style">
    
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/media_viewer.css">
    <link rel="stylesheet" href="css/video_thumbnail.css">
    
    <!-- Preconnect to improve resource load time -->
    <link rel="preconnect" href="https://cdn-uicons.flaticon.com">
    
    <style>
        .portfolio-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Top header with title and category */
        .portfolio-top-header {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 20px;
        }

        .portfolio-title {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .category-tag {
            background-color: #f0f0f0;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 14px;
            color: var(--text-primary);
        }

        /* User info and date row */
        .portfolio-meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--text-secondary);
            overflow: hidden;
        }

        .user-avatar i {
            font-size: 18px;
        }

        .user-name {
            font-size: 15px;
            font-weight: 500;
        }

        .upload-date {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Divider */
        .content-divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 20px 0;
        }

        /* Description */
        .portfolio-description {
            line-height: 1.6;
            margin-bottom: 24px;
            color: var(--text-primary);
        }

        /* Tools section */
        .tools-section {
            margin-bottom: 30px;
        }

        .tools-label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .tools-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tool-tag {
            background-color: #f0f0f0;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 14px;
            color: var(--text-primary);
        }

        /* Media section */
        .media-section {
            width: 80%;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .media-item {
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
            aspect-ratio: 16/9;
            background: #f5f5f5;
            min-height: 140px;
        }

        .media-item img.lazy-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover; /* Changed from contain to cover for better display */
            z-index: 2; /* Above the placeholder */
            opacity: 0; /* Start hidden */
            transition: opacity 0.3s ease;
        }

        .media-item img.lazy-image.loaded {
            opacity: 1; /* Show when loaded */
        }

        .image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(110deg, #ececec 8%, #f5f5f5 18%, #ececec 33%);
            background-size: 200% 100%;
            animation: 1.5s shine linear infinite;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1; /* Below the actual image */
        }

        @keyframes shine {
            to {
                background-position-x: -200%;
            }
        }

        .play-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 12px;
        }

        .user-link {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .user-link:hover {
            text-decoration: underline;
            color: var(--primary-color);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .portfolio-top-header {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
            }
            
            .portfolio-title {
                flex: 1;
            }
            
            .category-tag {
                order: 3;
                margin-top: 10px;
            }
            
            .portfolio-meta-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .media-section {
                width: 100%;
            }
        }
    </style>
    
    <!-- Defer non-critical JavaScript -->
    <script src="js/video_thumbnail.js" defer></script>
    <script src="js/media_viewer.js" defer></script>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <div class="portfolio-container">
            <!-- Top header with title and category -->
            <div class="portfolio-top-header">
                <h1 class="portfolio-title"><?php echo htmlspecialchars($portfolio['portfolio_title']); ?></h1>
                <span class="category-tag"><?php echo htmlspecialchars($portfolio['category']); ?></span>
                
                <?php if ($is_owner): ?>
                    <?php 
                    require_once 'components/three_dot_menu.php';
                    renderThreeDotMenu($portfolio_id); 
                    ?>
                <?php endif; ?>
            </div>
            
            <!-- User info and date row -->
            <div class="portfolio-meta-row">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fi fi-rr-user"></i>
                    </div>
                    <span class="user-name">
                        <a href="user_portfolio_profile.php?id=<?php echo $portfolio['user_id']; ?>" class="user-link">
                            <?php echo htmlspecialchars(!empty($portfolio['full_name']) ? $portfolio['full_name'] : $portfolio['user_name']); ?>
                        </a>
                    </span>
                </div>
                <div class="upload-date">
                    <?php 
                        $timestamp = strtotime($portfolio['portfolio_date']);
                        echo date('d M, Y', $timestamp); 
                    ?>
                </div>
            </div>
            
            <!-- Divider -->
            <div class="content-divider"></div>
            
            <!-- Description -->
            <?php if (!empty($portfolio['portfolio_description'])): ?>
                <div class="portfolio-description">
                    <?php echo nl2br(htmlspecialchars($portfolio['portfolio_description'])); ?>
                </div>
            <?php endif; ?>
            
            <!-- Tools section -->
            <?php if (!empty($portfolio['tools'])): ?>
                <div class="tools-section">
                    <div class="tools-label">Tools Used:</div>
                    <div class="tools-list">
                        <?php 
                        $tools = explode(',', $portfolio['tools']);
                        foreach ($tools as $tool): 
                            if (!empty(trim($tool))): 
                        ?>
                            <span class="tool-tag"><?php echo htmlspecialchars(trim($tool)); ?></span>
                        <?php 
                            endif; 
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Media section -->
            <?php if (!empty($media)): ?>
                <div class="media-section">
                    <?php foreach ($media as $index => $item): ?>
                        <div class="media-item" onclick="initMediaViewer('<?php echo $media_files_str; ?>', <?php echo $index; ?>)">
                            <?php if (strpos($item['file_type'], 'video/') === 0): ?>
                                <div class="video-placeholder" data-src="<?php echo htmlspecialchars($item['file_name']); ?>" 
                                     data-index="<?php echo $index; ?>">
                                    <div class="image-placeholder"></div>
                                    <div class="play-indicator">🎥 Video</div>
                                </div>
                            <?php else: ?>
                                <!-- Fixed image display -->
                                <div class="image-placeholder"></div>
                                <img 
                                    src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E" 
                                    data-src="uploads/<?php echo htmlspecialchars($item['file_name']); ?>" 
                                    alt="Portfolio media" 
                                    class="lazy-image"
                                    loading="lazy">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No media available for this portfolio item.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Only include the media viewer component when needed, not upfront -->
    <div id="mediaViewerContainer"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lazy load images
            const lazyImages = document.querySelectorAll('img.lazy-image');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.onload = () => {
                            img.classList.add('loaded');
                            // Don't hide the placeholder until the image is loaded
                            const placeholder = img.previousElementSibling;
                            if (placeholder && placeholder.classList.contains('image-placeholder')) {
                                // Just fade out instead of display: none
                                placeholder.style.opacity = '0';
                                // Remove after transition
                                setTimeout(() => {
                                    placeholder.style.display = 'none';
                                }, 300);
                            }
                        };
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px',
                threshold: 0.1
            });
            
            lazyImages.forEach(img => {
                imageObserver.observe(img);
            });
            
            // Lazy initialize video thumbnails
            const videoPlaceholders = document.querySelectorAll('.video-placeholder');
            const videoObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const placeholder = entry.target;
                        const filename = placeholder.dataset.src;
                        
                        // Create video thumbnail
                        const thumbnailContainer = document.createElement('div');
                        thumbnailContainer.className = 'video-thumbnail';
                        
                        const canvas = document.createElement('canvas');
                        canvas.width = 250;
                        canvas.height = 140;
                        canvas.className = 'video-canvas';
                        
                        thumbnailContainer.appendChild(canvas);
                        
                        // Replace placeholder with actual thumbnail
                        placeholder.parentNode.appendChild(thumbnailContainer);
                        
                        // Generate the thumbnail
                        generateThumbnailFromFilename(filename, canvas);
                        
                        observer.unobserve(placeholder);
                    }
                });
            });
            
            videoPlaceholders.forEach(placeholder => {
                videoObserver.observe(placeholder);
            });
        });
        
        // Only load media viewer when needed
        function initMediaViewer(files, index) {
            const container = document.getElementById('mediaViewerContainer');
            if (container) {
                // First time initialization
                if (container.innerHTML === '') {
                    container.innerHTML = `
                        <div id="mediaViewer" class="media-viewer">
                            <div class="media-viewer-content">
                                <button class="nav-button prev-button" onclick="navigateMedia(-1)">❮</button>
                                <div class="main-media-container">
                                    <div class="media-display"></div>
                                </div>
                                <button class="nav-button next-button" onclick="navigateMedia(1)">❯</button>
                                <button class="close-button" onclick="closeMediaViewer()">×</button>
                            </div>
                            <div class="media-thumbnails">
                                <!-- Thumbnails will be generated dynamically -->
                            </div>
                        </div>
                    `;
                    
                    // Load media viewer script if not already loaded
                    if (typeof showMedia !== 'function') {
                        const script = document.createElement('script');
                        script.src = 'js/media_viewer.js';
                        script.onload = () => showMediaViewer(files, index);
                        document.head.appendChild(script);
                    } else {
                        showMediaViewer(files, index);
                    }
                } else {
                    showMediaViewer(files, index);
                }
            }
        }
        
        function showMediaViewer(files, index) {
            mediaFiles = files.split(',');
            currentMediaIndex = index;
            document.getElementById('mediaViewer').style.display = 'block';
            showMedia(index);
            generateThumbnails();
        }
        
        function generateThumbnailFromFilename(filename, canvas) {
            const video = document.createElement('video');
            video.style.display = 'none';
            video.src = `uploads/${filename}`;
            video.preload = 'metadata';
            
            video.addEventListener('loadeddata', function() {
                video.currentTime = 1;
            });

            video.addEventListener('seeked', function() {
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(video.src);
                video.remove();
            });
            
            document.body.appendChild(video);
        }
    </script>
</body>
</html>