<?php
// filepath: c:\xampp\htdocs\log\components\portfolio_card.php
require_once 'includes/image_converter.php';
require_once 'components/media_gallery_preview.php';

function renderPortfolioCard($item) {
    global $conn; // Make sure database connection is available
    
    // Get all media files for this portfolio
    $stmt = $conn->prepare("
        SELECT m.file_name 
        FROM portfolio_media pm 
        JOIN media m ON pm.media_id = m.media_id 
        WHERE pm.portfolio_id = :portfolio_id
        LIMIT 5
    ");
    $stmt->execute([':portfolio_id' => $item['portfolio_id']]);
    $mediaFiles = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mediaFiles[] = $row['file_name'];
    }
    ?>
    
    <div class="portfolio-card" data-category="<?php echo htmlspecialchars($item['category']); ?>" 
         onclick="window.location.href='view_portfolio.php?id=<?php echo $item['portfolio_id']; ?>'">
        <div class="card-media">
            <?php renderMediaGalleryPreview($mediaFiles, 4); ?>
        </div>
        <div class="card-content">
            <div class="card-header">
                <h3><?php echo htmlspecialchars($item['portfolio_title']); ?></h3>
            </div>
            <div class="card-meta">
                <a href="user_portfolio_profile.php?id=<?php echo $item['user_id']; ?>" class="author" onclick="event.stopPropagation();">
                    <?php echo htmlspecialchars(!empty($item['full_name']) ? $item['full_name'] : $item['username']); ?>
                </a>
                <div class="timestamp">
                    <?php echo date('M d, Y', strtotime($item['portfolio_date'])); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>