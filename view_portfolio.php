<?php
// filepath: c:\xampp\htdocs\log\view_portfolio.php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/media_viewer.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: portfolio.php");
    exit();
}

$portfolio_id = $_GET['id'];

try {
    // Get portfolio details
    $stmt = $conn->prepare("
        SELECT 
            p.portfolio_id,
            p.portfolio_title,
            p.portfolio_description,
            p.portfolio_date,
            p.portfolio_time,
            u.user_name,
            u.user_id,
            COALESCE(s.full_name, sv.supervisor_name) as full_name
        FROM portfolio p
        INNER JOIN user u ON p.user_id = u.user_id
        LEFT JOIN student s ON u.user_id = s.student_id
        LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id
        WHERE p.portfolio_id = :portfolio_id
    ");
    
    $stmt->bindParam(':portfolio_id', $portfolio_id, PDO::PARAM_INT);
    $stmt->execute();
    $portfolio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$portfolio) {
        header("Location: portfolio.php");
        exit();
    }

    // Get all media for this portfolio
    $stmt = $conn->prepare("
        SELECT 
            m.media_id,
            m.file_name,
            m.file_type
        FROM portfolio_media pm
        INNER JOIN media m ON pm.media_id = m.media_id
        WHERE pm.portfolio_id = :portfolio_id
    ");
    
    $stmt->bindParam(':portfolio_id', $portfolio_id, PDO::PARAM_INT);
    $stmt->execute();
    $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format media files as comma-separated list for media viewer
    $media_files = array_map(function($item) {
        return $item['file_name'];
    }, $media);
    $media_files_str = implode(',', $media_files);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($portfolio['portfolio_title']); ?> - Portfolio</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/media_viewer.css">
    <link rel="stylesheet" href="css/video_thumbnail.css">
    <style>
        .portfolio-container {
            padding: 20px;
        }

        .portfolio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .portfolio-title {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .portfolio-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .portfolio-user {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .portfolio-datetime {
            font-size: 14px;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        .portfolio-description {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            line-height: 1.6;
        }

        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .media-item {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            cursor: pointer;
            position: relative;
            aspect-ratio: 16/9;
            background: #f5f5f5;
        }

        .media-item img,
        .media-item .video-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: white;
        }

        .play-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 12px;
        }

        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .back-button:hover {
            text-decoration: underline;
        }

        .user-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .user-link:hover {
            text-decoration: underline;
            color: var(--primary-hover);
        }
    </style>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <?php 
    // Include media viewer component
    if (!empty($media_files_str)) {
        require_once 'components/media_viewer.php';
        renderMediaViewer($media_files_str);
    }
    ?>

    <div class="main-content">
        <a href="portfolio.php" class="back-button">← Back to Portfolio</a>
        
        <div class="portfolio-container">
            <div class="portfolio-header">
                <h1 class="portfolio-title"><?php echo htmlspecialchars($portfolio['portfolio_title']); ?></h1>
                <div class="portfolio-meta">
                    <div class="portfolio-user">
                        By: <a href="user_portfolio_profile.php?id=<?php echo $portfolio['user_id']; ?>" class="user-link"><?php echo htmlspecialchars(!empty($portfolio['full_name']) ? $portfolio['full_name'] : $portfolio['user_name']); ?></a>
                    </div>
                    <div class="portfolio-datetime">
                        Uploaded on <?php 
                            $timestamp = strtotime($portfolio['portfolio_date'] . ' ' . $portfolio['portfolio_time']);
                            echo date('d M, Y h:i A', $timestamp); 
                        ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($portfolio['portfolio_description'])): ?>
                <div class="portfolio-description">
                    <?php echo nl2br(htmlspecialchars($portfolio['portfolio_description'])); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($media)): ?>
                <div class="media-grid">
                    <?php foreach ($media as $index => $item): ?>
                        <div class="media-item" onclick="initMediaViewer('<?php echo $media_files_str; ?>', <?php echo $index; ?>)">
                            <?php if (strpos($item['file_type'], 'video/') === 0): ?>
                                <?php 
                                require_once 'components/video_thumbnail.php';
                                renderVideoThumbnail($item['file_name']);
                                ?>
                                <div class="play-indicator">🎥 Video</div>
                            <?php else: ?>
                                <img src="uploads/<?php echo htmlspecialchars($item['file_name']); ?>" 
                                     alt="Portfolio media">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No media available for this portfolio item.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/video_thumbnail.js"></script>
    <script src="js/media_viewer.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            generateVideoThumbnails();
        });
    </script>
</body>
</html>