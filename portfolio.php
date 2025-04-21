<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/media_count_label.php';
require_once 'components/portfolio_card.php'; // Add this line to include the portfolio card component
require_once 'components/category_tabs.php';

$user_id = $_SESSION['user_id'];
$category_filter = isset($_REQUEST['category']) ? $_REQUEST['category'] : 'all';

try {
    // Modified query to include category filtering
    $where_clause = "WHERE p.user_id = :user_id";
    if ($category_filter !== 'all') {
        $where_clause .= " AND p.category = :category";
    }
    
    $stmt = $conn->prepare("
        SELECT 
            p.portfolio_id,
            p.portfolio_title,
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
        {$where_clause}
        GROUP BY p.portfolio_id
        ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
    ");
    
    $stmt->bindParam(':user_id', $user_id);
    if ($category_filter !== 'all') {
        $stmt->bindParam(':category', $category_filter);
    }
    $stmt->execute();
    
    $portfolio_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portfolio</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/portfolio.css">
    <link rel="stylesheet" href="css/main_menu.css">
    <link rel="stylesheet" href="css/category_tabs.css">
    <link rel="stylesheet" href="css/delete_modal.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
</head>
<body data-user-id="<?php echo $_SESSION['user_id']; ?>">
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <!-- Update the header section to have a bottom border -->
        <div class="page-header">
            <h2>My Portfolio</h2>
            <div class="add-options">
                <button class="btn-add" onclick="toggleDropdown(event)">
                    <i class="fi fi-rr-square-plus"></i> Add New
                </button>
                <div id="addOptions" class="menu-dropdown">
                    <a href="add_portfolio.php" class="menu-item">
                        <i class="fi fi-rr-cloud-upload-alt"></i> Upload Media
                    </a>
                    <a href="import_logbook_media.php" class="menu-item">
                        <i class="fi fi-rr-file-import"></i> Import from Logbook
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Update this line in portfolio.php (around line 72) -->
        <?php renderCategoryTabs($category_filter); ?>

        <?php if (empty($portfolio_items)): ?>
            <div class="empty-state">
                <h3>No portfolio items yet</h3>
                <p>Start adding items to your portfolio!</p>
            </div>
        <?php else: ?>
            <!-- Use gallery class instead of portfolio-grid for consistency with main_menu.php -->
            <div class="gallery">
                <?php foreach ($portfolio_items as $item): ?>
                    <?php renderPortfolioCard($item); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="js/category_filter.js"></script>
    <script src="js/lazy_blur.js"></script>
    <script>
    function toggleDropdown(event) {
        event.stopPropagation();
        const dropdown = document.getElementById("addOptions");
        const isVisible = dropdown.style.display === 'block';
        
        // Close any open menus first
        closeAllMenus();
        
        // Toggle this menu
        if (!isVisible) {
            dropdown.style.display = 'block';
        }
    }

    function closeAllMenus() {
        const menus = document.querySelectorAll('.menu-dropdown');
        menus.forEach(menu => {
            menu.style.display = 'none';
        });
    }

    // Close menu when clicking outside
    document.addEventListener('click', function() {
        closeAllMenus();
    });
    
    // Initialize category filtering and lazy loading
    document.addEventListener('DOMContentLoaded', function() {
        // Pass the correct selector and attribute name
        initCategoryFilter('.portfolio-card', 'data-category');
        initializeLazyLoading();
    });
    </script>
    <script src="js/delete_confirmation.js"></script>
    <?php
    require_once 'components/toast_notification.php';
    initializeToast();
    ?>
</body>
</html>
<?php
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>