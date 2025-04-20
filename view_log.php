<?php
session_start();
require_once 'includes/db.php'; // Update path

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT 
            le.entry_id,
            le.entry_title,
            le.entry_description,
            le.entry_status,
            le.entry_date,
            le.entry_time,
            GROUP_CONCAT(m.file_name) as media_files,
            f.remarks,
            f.signature_date,
            f.signature_time
        FROM log_entry le
        LEFT JOIN log_media lm ON le.entry_id = lm.entry_id
        LEFT JOIN media m ON lm.media_id = m.media_id
        LEFT JOIN feedback f ON le.entry_id = f.entry_id
        WHERE le.entry_id = :entry_id AND le.user_id = :user_id
        GROUP BY le.entry_id
    ");

    $stmt->bindParam(':entry_id', $_GET['id'], PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$entry) {
        header("Location: logbook.php");
        exit();
    }
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
    <title>View Log Entry</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/log_entry.css">
    <link rel="stylesheet" href="css/media_viewer.css">
    <link rel="stylesheet" href="css/video_thumbnail.css">
    <style>
        .media-viewer-section {
            position: relative;
            width: 100%;
            height: 400px;
            background: var(--bg-secondary);
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .media-display {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
        }

        .media-display img,
        .media-display video {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .media-thumbnails {
            height: 100px;
            background: #f1f3f5;
            padding: 10px;
            display: flex;
            gap: 10px;
            overflow-x: auto;
            border-top: 1px solid var(--border-color);
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
            cursor: pointer;
            border: 2px solid transparent;
            overflow: hidden;
            border-radius: 4px;
        }

        .thumbnail.active {
            border-color: var(--primary-color);
        }

        .nav-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 20px;
            cursor: pointer;
            font-size: 24px;
            z-index: 2;
        }

        .prev-button { left: 20px; }
        .next-button { right: 20px; }

        /* Log entry styles matching logbook */
        .log-entry-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 20px;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .log-content {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .media-viewer-section { background: none; }
    </style>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    <div class="main-content">
        <div class="log-entry">
            <?php if (!empty($entry['media_files'])): ?>
                <div class="media-viewer-section">
                    <div class="media-display"></div>
                    <?php if (count(explode(',', $entry['media_files'])) > 1): ?>
                        <button class="nav-button prev-button" onclick="navigateMedia(-1)">❮</button>
                        <button class="nav-button next-button" onclick="navigateMedia(1)">❯</button>
                    <?php endif; ?>
                    <div class="media-thumbnails">
                        <?php foreach (explode(',', $entry['media_files']) as $index => $media): ?>
                            <div class="thumbnail" onclick="showMedia(<?php echo $index; ?>)">
                                <?php if (strpos($media, '.mp4') !== false || strpos($media, '.mov') !== false): ?>
                                    <?php 
                                    require_once 'components/video_thumbnail.php';
                                    renderVideoThumbnail($media, true);
                                    ?>
                                <?php else: ?>
                                    <img src="uploads/<?php echo htmlspecialchars($media); ?>" alt="Media Thumbnail">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="log-entry-grid">
                <div class="log-content">
                    <div class="log-header">
                        <div class="log-datetime">
                            <div class="datetime-group">
                                <h3><?php 
                                    $timestamp = strtotime($entry['entry_date'] . ' ' . $entry['entry_time']);
                                    echo date('M d, Y (l)', $timestamp);
                                ?></h3>
                                <span class="upload-time">
                                    <?php echo "Upload by: " . date('g:i A', $timestamp); ?>
                                </span>
                            </div>
                        </div>
                        <span class="log-status <?php echo $entry['entry_status']; ?>">
                            <?php echo htmlspecialchars($entry['entry_status']); ?>
                        </span>
                    </div>

                    <div class="log-title">
                        <?php echo htmlspecialchars($entry['entry_title']); ?>
                    </div>

                    <div class="log-description">
                        <p><?php echo nl2br(htmlspecialchars($entry['entry_description'])); ?></p>
                    </div>

                    <?php if ($entry['entry_status'] === 'reviewed'): ?>
                        <div class="supervisor-review">
                            <h4>Supervisor's Remarks:</h4>
                            <p><?php echo !empty($entry['remarks']) ? nl2br(htmlspecialchars($entry['remarks'])) : 'No remarks provided'; ?></p>
                            <div class="signature-date">
                                Signed on: <?php echo date('M d, Y g:i A', strtotime($entry['signature_date'] . ' ' . $entry['signature_time'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <?php if ($entry['entry_status'] !== 'reviewed'): ?>
                <button class="btn btn-edit" onclick="window.location.href='edit_log.php?id=<?php echo $entry['entry_id']; ?>'">
                    Edit
                </button>
            <?php endif; ?>
            <button class="btn btn-delete" onclick="confirmDelete(<?php echo $entry['entry_id']; ?>)">
                Delete
            </button>
            <a href="logbook.php" class="btn">Back to Logbook</a>
        </div>
    </div>

    <script>
        let currentMediaIndex = 0;
        const mediaFiles = <?php echo json_encode(explode(',', $entry['media_files'])); ?>;

        function showMedia(index) {
            if (index < 0 || index >= mediaFiles.length) return;
            
            currentMediaIndex = index;
            const mediaDisplay = document.querySelector('.media-display');
            const file = mediaFiles[index];
            
            // Update thumbnails active state
            document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
                thumb.classList.toggle('active', i === index);
            });
            
            // Clear previous content
            mediaDisplay.innerHTML = '';
            
            if (file.endsWith('.mp4') || file.endsWith('.mov')) {
                const video = document.createElement('video');
                video.controls = true;
                video.autoplay = true;
                video.src = `uploads/${file}`;
                mediaDisplay.appendChild(video);
            } else {
                const img = document.createElement('img');
                img.src = `uploads/${file}`;
                mediaDisplay.appendChild(img);
            }
        }

        function navigateMedia(direction) {
            showMedia(currentMediaIndex + direction);
        }

        // Initialize first media
        document.addEventListener('DOMContentLoaded', function() {
            generateVideoThumbnails();
            if (mediaFiles.length > 0) {
                showMedia(0);
            }
        });
    </script>
    <script src="js/view_log.js"></script>
</body>
</html>