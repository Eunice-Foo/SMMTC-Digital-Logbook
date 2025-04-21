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
                (SELECT COUNT(*) FROM portfolio_media pm2 WHERE pm2.portfolio_id = p.portfolio_id) as media_count
            FROM portfolio p
            INNER JOIN user u ON p.user_id = u.user_id
            LEFT JOIN student s ON u.user_id = s.student_id
            LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id
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
                (SELECT COUNT(*) FROM portfolio_media pm2 WHERE pm2.portfolio_id = p.portfolio_id) as media_count
            FROM portfolio p
            INNER JOIN user u ON p.user_id = u.user_id
            LEFT JOIN student s ON u.user_id = s.student_id
            LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id
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
            (SELECT COUNT(*) FROM portfolio_media pm2 WHERE pm2.portfolio_id = p.portfolio_id) as media_count
        FROM portfolio p
        INNER JOIN user u ON p.user_id = u.user_id
        LEFT JOIN student s ON u.user_id = s.student_id
        LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id
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
    
    <!-- Optimized resource loading -->
    <link rel="preload" href="css/theme.css" as="style">
    <link rel="preload" href="css/main_menu.css" as="style">
    <link rel="preconnect" href="https://cdn-uicons.flaticon.com">
    
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/main_menu.css">
    <link rel="stylesheet" href="css/category_tabs.css">
    
    <style>
        
        @keyframes shine {
            to { background-position-x: -200%; }
        }
        
        .card-media {position: relative;}
        .lazy-image {opacity: 0; transition: opacity 0.3s;}
        .lazy-image.loaded {opacity: 1;}
        .loading-indicator {text-align: center; padding: 20px; display: none;}
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
                    <?php renderPortfolioCard($result); ?>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="loading-indicator" id="loadingIndicator">
                Loading more items...
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set up category tabs
            setupCategoryTabs();
            
            // Initialize lazy loading for images
            initializeLazyLoading();
        });
    </script>
    <script src="js/category_tabs.js" defer></script>
    <script src="js/lazy_blur.js"></script>
</body>
</html>