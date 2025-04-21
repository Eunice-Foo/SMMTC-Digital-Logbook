<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/media_count_label.php';
require_once 'components/portfolio_card.php'; // Add this line to include the portfolio card component
require_once 'components/category_tabs.php';

$user_id = $_SESSION['user_id'];

try {
    // Modified query to match the structure expected by portfolio_card component
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
        WHERE p.user_id = :user_id
        GROUP BY p.portfolio_id
        ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
    ");
    
    $stmt->bindParam(':user_id', $user_id);
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
    <link rel="stylesheet" href="css/main_menu.css"> <!-- Add main_menu.css for portfolio card styles -->
    <link rel="stylesheet" href="css/category_tabs.css">
    <link rel="stylesheet" href="css/delete_modal.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
</head>
<body data-user-id="<?php echo $_SESSION['user_id']; ?>">
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <h2>My Portfolio</h2>
        
        <!-- Using the category tabs component -->
        <?php renderCategoryTabs('all'); ?>

        <div class="add-options">
            <button class="btn-add" onclick="toggleDropdown()">
                <i class="fi fi-rr-square-plus"></i> Add New
            </button>
            <div id="addOptions" class="dropdown-content">
                <a href="add_portfolio.php">Upload Media</a>
                <a href="import_logbook_media.php">Import from Logbook</a>
            </div>
        </div>
        
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
    function toggleDropdown() {
        const dropdown = document.getElementById("addOptions");
        dropdown.classList.toggle("show");
    }

    // Close dropdown when clicking outside
    window.onclick = function(event) {
        // Change from .btn-dropdown to .btn-add
        if (!event.target.matches('.btn-add') && !event.target.matches('.fi-rr-square-plus')) {
            const dropdowns = document.getElementsByClassName("dropdown-content");
            for (let dropdown of dropdowns) {
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }
    }
    
    // Initialize category filtering and lazy loading
    document.addEventListener('DOMContentLoaded', function() {
        initCategoryFilter();
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