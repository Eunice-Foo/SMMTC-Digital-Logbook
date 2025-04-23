<?php
// filepath: c:\xampp\htdocs\log\media_slideshow.php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/media_slideshow_gallery.php';

// Check if viewing a specific portfolio
$portfolio_id = isset($_GET['id']) ? $_GET['id'] : null;
$user_id = $_SESSION['user_id'];

try {
    // Get portfolio info if ID is provided
    $portfolio = null;
    if ($portfolio_id) {
        $stmt = $conn->prepare("
            SELECT portfolio_title, portfolio_description, category
            FROM portfolio 
            WHERE portfolio_id = :portfolio_id
        ");
        $stmt->bindParam(':portfolio_id', $portfolio_id);
        $stmt->execute();
        $portfolio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$portfolio) {
            header('Location: portfolio.php');
            exit();
        }
        
        // Get media for this portfolio
        $stmt = $conn->prepare("
            SELECT m.media_id, m.file_name, m.file_type
            FROM portfolio_media pm
            JOIN media m ON pm.media_id = m.media_id
            WHERE pm.portfolio_id = :portfolio_id
        ");
        $stmt->bindParam(':portfolio_id', $portfolio_id);
        $stmt->execute();
        $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // If no portfolio_id, get all user's media
        $stmt = $conn->prepare("
            SELECT media_id, file_name, file_type
            FROM media
            WHERE user_id = :user_id
            ORDER BY upload_date DESC, upload_time DESC
            LIMIT 50
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Slideshow</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/main_menu.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
</head>
<body>
    <?php include 'components/topnav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <a href="<?php echo $portfolio_id ? 'view_portfolio.php?id=' . $portfolio_id : 'portfolio.php'; ?>" class="back-button">
                <i class="fi fi-rr-arrow-left"></i> Back
            </a>
            <h1>
                <?php if ($portfolio): ?>
                    <?php echo htmlspecialchars($portfolio['portfolio_title']); ?> Slideshow
                <?php else: ?>
                    Media Gallery
                <?php endif; ?>
            </h1>
        </div>
        
        <?php if (empty($media)): ?>
            <div class="empty-state">
                <h3>No media found</h3>
                <p>There are no media files to display.</p>
            </div>
        <?php else: ?>
            <?php 
            // Render the slideshow gallery with the media files
            renderMediaSlideshowGallery($media, $portfolio ? $portfolio['portfolio_title'] : 'My Media');
            ?>
        <?php endif; ?>
    </div>
</body>
</html>