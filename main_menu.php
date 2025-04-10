<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/media_count_label.php';
require_once 'components/portfolio_card.php';

$search_results = [];
$total_results = 0;
$items_per_page = 12; // Increased to 12 items per page
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($page - 1) * $items_per_page;

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
                u.user_id, /* Add this line */
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
            GROUP BY p.portfolio_id
            ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                p.portfolio_id,
                p.portfolio_title, 
                p.portfolio_description, 
                p.portfolio_date, 
                p.portfolio_time, 
                u.user_id, /* Add this line */
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
            WHERE p.portfolio_title LIKE :search 
            OR p.portfolio_description LIKE :search
            GROUP BY p.portfolio_id
            ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
            LIMIT :limit OFFSET :offset
        ");
        $search_param = "%" . $search . "%";
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    if (empty($search)) {
        $count_stmt = $conn->prepare("
            SELECT COUNT(DISTINCT p.portfolio_id) as count
            FROM portfolio p
        ");
    } else {
        $count_stmt = $conn->prepare("
            SELECT COUNT(DISTINCT p.portfolio_id) as count
            FROM portfolio p
            WHERE p.portfolio_title LIKE :search 
            OR p.portfolio_description LIKE :search
        ");
        $count_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
    }
    $count_stmt->execute();
    $total_results = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
} else {
    // Initial page load
    $stmt = $conn->prepare("
        SELECT 
            p.portfolio_id,
            p.portfolio_title, 
            p.portfolio_description, 
            p.portfolio_date, 
            p.portfolio_time, 
            u.user_id, /* Add this line */
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
        GROUP BY p.portfolio_id
        ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT p.portfolio_id) as count
        FROM portfolio p
    ");
    $count_stmt->execute();
    $total_results = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// If this is an AJAX request, return JSON
if (isset($_POST['ajax'])) {
    echo json_encode(['results' => $search_results, 'total' => $total_results]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Portfolio Works</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/main_menu.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body data-user-id="<?php echo $_SESSION['user_id']; ?>">
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
                    <?php renderPortfolioCard($result); ?>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="loading-indicator" id="loadingIndicator">
                Loading more items...
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let loading = false;
        let hasMore = <?php echo $total_results > ($page * $items_per_page) ? 'true' : 'false'; ?>;

        $(window).scroll(function() {
            if($(window).scrollTop() + $(window).height() > $(document).height() - 300) {
                if (!loading && hasMore) {
                    loadMore();
                }
            }
        });

        function loadMore() {
            loading = true;
            $('#loadingIndicator').show();
            currentPage++;
            
            $.post('main_menu.php', {
                page: currentPage,
                search: $('input[name="search"]').val(),
                ajax: true
            }, function(data) {
                const response = JSON.parse(data);
                $('#loadingIndicator').hide();
                
                if (response.results.length > 0) {
                    response.results.forEach(result => {
                        let videoHtml = '';
                        if (result.file_type && result.file_type.startsWith('video/')) {
                            videoHtml = `
                                <div class="video-thumbnail">
                                    <video preload="metadata">
                                        <source src="uploads/${result.media}" type="video/mp4">
                                    </video>
                                    <canvas class="video-canvas"></canvas>
                                </div>
                                <div class="video-badge">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8 0C3.6 0 0 3.6 0 8C0 12.4 3.6 16 8 16C12.4 16 16 12.4 16 8C16 3.6 12.4 0 8 0ZM6 11.5V4.5L12 8L6 11.5Z" fill="white"/>
                                    </svg>
                                </div>
                            `;
                        } else {
                            videoHtml = `<img src="uploads/${result.media}" alt="Portfolio Media">`;
                        }

                        let mediaCountHtml = '';
                        if (result.media_count > 1) {
                            mediaCountHtml = `<div class="media-count" style="position: absolute; bottom: 10px; right: 10px; background: rgba(0, 0, 0, 0.7); color: white; padding: 4px 8px; border-radius: 8px; font-size: 12px; z-index: 3;">+${result.media_count - 1} more</div>`;
                        }
                        
                        $('#gallery').append(`
                            <div class="portfolio-card" onclick="window.location.href='view_portfolio.php?id=${result.portfolio_id}'">
                                <div class="card-media">
                                    ${videoHtml}
                                    ${mediaCountHtml}
                                </div>
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
                                </div>
                            </div>
                        `);
                    });
                    
                    // Initialize video thumbnails for new content
                    generateVideoThumbnails();
                    
                    // Check if there are more items to load
                    hasMore = response.total > (currentPage * <?php echo $items_per_page; ?>);
                } else {
                    hasMore = false;
                    $('#loadingIndicator').text('No more items to display');
                }
                loading = false;
            });
        }
    </script>
    <script src="js/video_thumbnail.js"></script>
</body>
</html>