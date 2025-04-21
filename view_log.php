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
            GROUP_CONCAT(m.file_type) as media_types,
            GROUP_CONCAT(m.media_id) as media_ids,
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

    // Prepare media files array for the slideshow gallery
    $mediaFiles = [];
    if (!empty($entry['media_files'])) {
        $fileNames = explode(',', $entry['media_files']);
        $fileTypes = !empty($entry['media_types']) ? explode(',', $entry['media_types']) : [];
        $mediaIds = !empty($entry['media_ids']) ? explode(',', $entry['media_ids']) : [];
        
        foreach ($fileNames as $index => $fileName) {
            $mediaFiles[] = [
                'file_name' => $fileName,
                'file_type' => isset($fileTypes[$index]) ? $fileTypes[$index] : '',
                'media_id' => isset($mediaIds[$index]) ? $mediaIds[$index] : 0
            ];
        }
    }

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
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <style>
        /* Log entry styles matching logbook */
        .log-entry-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 20px;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .log-content {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* Slideshow-specific adjustments for log view */
        .slideshow-gallery-compact {
            margin: 20px auto;
            max-width: 1000px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .slideshow-gallery-compact .slide-media {
            max-height: 400px;
        }

        .slideshow-gallery-compact .video-container {
            height: 400px;
        }

        @media (max-width: 768px) {
            .slideshow-gallery-compact {
                padding: 10px;
            }
            
            .slideshow-gallery-compact .slide-media,
            .slideshow-gallery-compact .video-container {
                max-height: 300px;
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    <div class="main-content">
        <div class="log-entry">
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

            <!-- Media section with slideshow gallery -->
            <?php if (!empty($mediaFiles)): ?>
                <?php 
                require_once 'components/media_slideshow_gallery.php';
                renderMediaSlideshowGallery($mediaFiles, true); // Pass true for compact view
                ?>
            <?php endif; ?>

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
    </div>
    <script src="js/view_log.js"></script>
</body>
</html>