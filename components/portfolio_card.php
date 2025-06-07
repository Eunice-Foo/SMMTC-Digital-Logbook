<?php
// filepath: c:\xampp\htdocs\log\components\portfolio_card.php
require_once 'includes/image_converter.php';
require_once 'components/media_gallery_preview.php';
require_once 'includes/profile_functions.php';
require_once 'components/media_count_label.php';

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
    
    // Get user's profile picture
    $stmt = $conn->prepare("
        SELECT 
            u.profile_picture
        FROM user u
        WHERE u.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $item['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $profilePicture = $user['profile_picture'] ?? null;
    ?>
    
    <div class="portfolio-card" data-category="<?php echo htmlspecialchars($item['category']); ?>" 
         onclick="window.location.href='view_portfolio.php?id=<?php echo $item['portfolio_id']; ?>'">
        <div class="card-media">
            <?php 
            renderMediaGalleryPreview($mediaFiles, 4);
            
            // Check if we need to show the media count
            if (isset($item['media_count']) && $item['media_count'] > 4) {
                renderMediaCountLabel($item['media_count'], 4);
            }
            ?>
        </div>
        <div class="card-content">
            <div class="card-header">
                <h3><?php echo htmlspecialchars($item['portfolio_title']); ?></h3>
            </div>
            <div class="card-meta">
                <a href="user_portfolio_profile.php?id=<?php echo $item['user_id']; ?>" class="author-info" onclick="event.stopPropagation();">
                    <div class="author-avatar">
                        <img src="<?php echo getProfileImagePath($profilePicture, 'sm'); ?>" alt="Author profile picture">
                    </div>
                    <span class="author-name">
                        <?php echo htmlspecialchars(!empty($item['full_name']) ? $item['full_name'] : $item['username']); ?>
                    </span>
                </a>
                
                <!-- Date removed as requested -->
            </div>
        </div>
    </div>
    <?php
}
?>