<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        // Get the log entry title
        $stmt = $conn->prepare("
            SELECT entry_title 
            FROM log_entry 
            WHERE entry_id = (
                SELECT entry_id 
                FROM log_media 
                WHERE media_id = :first_media_id
                LIMIT 1
            )
        ");
        $stmt->execute([':first_media_id' => $_POST['selected_media'][0]]);
        $entry_title = $stmt->fetchColumn();

        // Insert new portfolio entry with log entry title
        $stmt = $conn->prepare("
            INSERT INTO portfolio 
            (user_id, portfolio_title, portfolio_description, portfolio_date, portfolio_time)
            VALUES 
            (:user_id, :title, '', CURDATE(), CURTIME())
        ");

        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':title' => $entry_title
        ]);

        $portfolio_id = $conn->lastInsertId();

        // Handle selected media
        if (!empty($_POST['selected_media'])) {
            foreach ($_POST['selected_media'] as $media_id) {
                $stmt = $conn->prepare("
                    INSERT INTO portfolio_media (media_id, portfolio_id)
                    VALUES (:media_id, :portfolio_id)
                ");
                
                $stmt->execute([
                    ':media_id' => $media_id,
                    ':portfolio_id' => $portfolio_id
                ]);
            }
        }

        $conn->commit();
        header("Location: portfolio.php");
        exit();

    } catch(Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

// Get all media from user's logbook entries
$stmt = $conn->prepare("
    SELECT DISTINCT 
        m.media_id,
        m.file_name,
        m.file_type,
        le.entry_id,
        le.entry_title,
        le.entry_date
    FROM log_entry le
    INNER JOIN log_media lm ON le.entry_id = lm.entry_id
    INNER JOIN media m ON lm.media_id = m.media_id
    WHERE le.user_id = :user_id
    ORDER BY le.entry_date DESC
");

$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$logbook_media = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import from Logbook</title>
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .media-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* Fixed 4 columns */
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .media-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            box-shadow: var(--box-shadow);
            transition: transform 0.2s ease;
            aspect-ratio: 1; /* Make items square */
        }

        .media-preview {
            width: 100%;
            height: 70%; /* Media takes 70% of square */
            position: relative;
            background-color: #f5f5f5; /* Add background for empty space */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .media-preview img,
        .media-preview video {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Changed from cover to contain */
        }

        .video-thumbnail {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-thumbnail img {
            object-fit: contain; /* Changed from cover to contain */
            max-width: 100%;
            max-height: 100%;
        }

        .media-info {
            height: 30%; /* Info takes 30% of square */
            padding: 10px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .media-select {
            position: absolute;
            top: 10px;
            right: 10px;
            transform: scale(1.5);
            z-index: 2;
            background: white;
            border-radius: 3px;
            cursor: pointer;
        }

        /* Responsive adjustments */
        @media screen and (max-width: 1200px) {
            .media-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media screen and (max-width: 900px) {
            .media-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media screen and (max-width: 576px) {
            .media-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/topnav.php'; ?>
    
    <div class="main-content">
        <h2>Import Media from Logbook</h2>
        <div class="selection-counter">
            <span id="selectedCount">0</span> media selected
        </div>
        
        <form method="POST" class="import-form">
            <div class="media-grid">
                <?php foreach ($logbook_media as $media): ?>
                    <div class="media-item">
                        <div class="media-preview">
                            <?php if (strpos($media['file_type'], 'video/') === 0): ?>
                                <div class="video-thumbnail">
                                    <?php
                                    $thumbnail_path = 'uploads/thumbnails/' . pathinfo($media['file_name'], PATHINFO_FILENAME) . '.jpg';
                                    if (file_exists($thumbnail_path)): ?>
                                        <img src="<?php echo htmlspecialchars($thumbnail_path); ?>" alt="Video Thumbnail">
                                    <?php else: ?>
                                        <div class="video-placeholder">🎥</div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <img src="uploads/<?php echo htmlspecialchars($media['file_name']); ?>" alt="Media">
                            <?php endif; ?>
                            <input type="checkbox" class="media-select" name="selected_media[]" value="<?php echo $media['media_id']; ?>">
                        </div>
                        <div class="media-info">
                            <p class="entry-title"><?php echo htmlspecialchars($media['entry_title']); ?></p>
                            <p class="entry-date"><?php echo date('F j, Y', strtotime($media['entry_date'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="submit-button">Add Selected to Portfolio</button>
        </form>
    </div>

    <script>
    // Counter function
    function updateSelectedCount() {
        const count = document.querySelectorAll('input[name="selected_media[]"]:checked').length;
        document.getElementById('selectedCount').textContent = count;
    }

    // Add click handler to media items
    document.querySelectorAll('.media-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                updateSelectedCount();
            }
        });
    });

    // Add change handler to checkboxes
    document.querySelectorAll('input[name="selected_media[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    </script>
</body>
</html>