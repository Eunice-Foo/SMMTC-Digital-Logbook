<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/media_count_label.php';
require_once 'components/category_tabs.php';

$user_id = $_SESSION['user_id'];

try {
    // Modified query to get all media grouped by portfolio
    $stmt = $conn->prepare("
        SELECT 
            p.portfolio_id,
            p.portfolio_title,
            p.portfolio_date,
            p.portfolio_time,
            p.category,
            m.file_name,
            m.file_type,
            (SELECT COUNT(*) FROM portfolio_media pm2 WHERE pm2.portfolio_id = p.portfolio_id) as media_count
        FROM portfolio p
        INNER JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
        INNER JOIN media m ON pm.media_id = m.media_id
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
    <link rel="stylesheet" href="css/category_tabs.css">
    <link rel="stylesheet" href="css/delete_modal.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <style>
        .media-count {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 12px;
            z-index: 3;
        }
    </style>
</head>
<body data-user-id="<?php echo $_SESSION['user_id']; ?>">
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <h2>My Portfolio</h2>
        
        <!-- Using the category tabs component -->
        <?php renderCategoryTabs('all'); ?>

        <div class="add-options">
            <button class="btn-dropdown" onclick="toggleDropdown()">Add to Portfolio ▼</button>
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
            <div class="portfolio-grid">
                <?php foreach ($portfolio_items as $item): ?>
                    <div class="portfolio-item" data-category="<?php echo htmlspecialchars($item['category']); ?>" onclick="window.location.href='view_portfolio.php?id=<?php echo $item['portfolio_id']; ?>'">
                        <div class="media-container">
                            <?php if (strpos($item['file_type'], 'video/') === 0): ?>
                                <?php 
                                require_once 'components/video_thumbnail.php';
                                renderVideoThumbnail($item['file_name']);
                                ?>
                                <div class="video-indicator">🎥 Video</div>
                            <?php else: ?>
                                <img src="uploads/<?php echo htmlspecialchars($item['file_name']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['portfolio_title']); ?>">
                            <?php endif; ?>
                            
                            <?php renderMediaCountLabel($item['media_count']); ?>
                        </div>
                        <div class="portfolio-info">
                            <div class="portfolio-title"><?php echo htmlspecialchars($item['portfolio_title']); ?></div>
                            <div class="portfolio-date">
                                <?php echo date('F j, Y', strtotime($item['portfolio_date'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="js/category_filter.js"></script>
    <script>
    function toggleDropdown() {
        document.getElementById("addOptions").classList.toggle("show");
    }

    // Close dropdown when clicking outside
    window.onclick = function(event) {
        if (!event.target.matches('.btn-dropdown')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
    
    // Initialize category filtering
    initCategoryFilter();
    </script>
    <script src="js/delete_confirmation.js"></script>
    <script src="js/lazy_blur.js"></script>
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