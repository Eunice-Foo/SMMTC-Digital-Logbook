<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

try {
    // Query to get all media from user's portfolios
    $stmt = $conn->prepare("
        SELECT 
            p.portfolio_id,
            p.portfolio_title,
            p.portfolio_date,
            p.portfolio_time,
            m.file_name,
            m.file_type
        FROM portfolio p
        INNER JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
        INNER JOIN media m ON pm.media_id = m.media_id
        WHERE p.user_id = :user_id
        ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
    ");

    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $portfolio_media = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portfolio</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/portfolio.css">
        
</head>
<body data-user-id="<?php echo $_SESSION['user_id']; ?>">
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <h2>My Portfolio</h2>

        <div class="add-options">
            <button class="btn-dropdown" onclick="toggleDropdown()">Add to Portfolio ▼</button>
            <div id="addOptions" class="dropdown-content">
                <a href="add_portfolio.php">Upload Media</a>
                <a href="import_logbook_media.php">Import from Logbook</a>
            </div>
        </div>
        
        <?php if (empty($portfolio_media)): ?>
            <div class="empty-state">
                <h3>No portfolio items yet</h3>
                <p>Start adding items to your portfolio!</p>
            </div>
        <?php else: ?>
            <div class="portfolio-grid">
                <?php foreach ($portfolio_media as $item): ?>
                    <div class="portfolio-item">
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

    <script>
    function playVideo(filename) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <video controls autoplay>
                    <source src="uploads/${filename}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <button class="close-modal">×</button>
            </div>
        `;
        
        modal.onclick = function(e) {
            if (e.target === modal || e.target.className === 'close-modal') {
                const video = modal.querySelector('video');
                video.pause();
                modal.remove();
            }
        };
           
        document.body.appendChild(modal);
        modal.style.display = 'block';
    }

    function toggleDropdown() {
        document.getElementById("addOptions").classList.toggle("show");
    }

    function showUploadForm() {
        document.getElementById("addPortfolioForm").style.display = "block";
        document.getElementById("addOptions").classList.remove("show");
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
    </script>

    <style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal-content {
        max-width: 90%;
        max-height: 90%;
        position: relative;
    }

    .modal video {
        max-width: 100%;
        max-height: 90vh;
    }

    .close-modal {
        position: absolute;
        top: -40px;
        right: 0;
        background: none;
        border: none;
        color: white;
        font-size: 30px;
        cursor: pointer;
    }
    </style>
</body>
</html>
<?php
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>