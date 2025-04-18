<?php
// filepath: c:\xampp\htdocs\log\components\portfolio_card.php
require_once 'includes/image_converter.php';

function renderPortfolioCard($item) {
    ?>
    <div class="portfolio-card" data-category="<?php echo htmlspecialchars($item['category']); ?>" onclick="window.location.href='view_portfolio.php?id=<?php echo $item['portfolio_id']; ?>'">
        <div class="card-media">
            <div class="placeholder-image"></div>
            <?php if (strpos($item['file_type'], 'video/') === 0): ?>
                <?php 
                require_once 'components/video_thumbnail.php';
                renderVideoThumbnail($item['media']);
                ?>
                <div class="video-badge">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 0C3.6 0 0 3.6 0 8C0 12.4 3.6 16 8 16C12.4 16 16 12.4 16 8C16 3.6 12.4 0 8 0ZM6 11.5V4.5L12 8L6 11.5Z" fill="white"/>
                    </svg>
                </div>
            <?php else: ?>
                <?php 
                // Use the optimized image renderer
                echo renderOptimizedImage(
                    'uploads/' . $item['media'],
                    htmlspecialchars($item['portfolio_title']),
                    'portfolio-image',
                    'loading="lazy" width="100%" height="100%"'
                );
                ?>
            <?php endif; ?>
            
            <?php 
            require_once 'components/media_count_label.php';
            renderMediaCountLabel($item['media_count']); 
            ?>
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