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
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .entry-details {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .media-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .media-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .back-button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-edit {
            background-color: #2196F3;
        }
        .btn-delete {
            background-color: #f44336;
        }
        .signature-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }
        .signature-info h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .signature-info p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
<div class="main-content"> 
    <div class="container">
        <div class="entry-details">
            <h2><?php echo htmlspecialchars($entry['entry_title']); ?></h2>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($entry['entry_date'] . ' ' . $entry['entry_time']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $entry['entry_status']))); ?></p>
            <p><strong>Description:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($entry['entry_description'])); ?></p>
            
            <?php if (!empty($entry['media_files'])): ?>
                <h3>Media Files</h3>
                <div class="media-gallery">
                    <?php foreach (explode(',', $entry['media_files']) as $media): ?>
                        <div class="media-item">
                            <?php if (strpos($media, '.mp4') !== false || strpos($media, '.mov') !== false): ?>
                                <video width="100%" height="auto" controls>
                                    <source src="uploads/<?php echo htmlspecialchars($media); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php else: ?>
                                <img src="uploads/<?php echo htmlspecialchars($media); ?>" alt="Log Entry Media">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($entry['entry_status'] === 'reviewed'): ?>
                <div class="signature-info">
                    <h3>Review Information</h3>
                    <p><strong>Signed Date:</strong> <?php echo htmlspecialchars($entry['signature_date'] . ' ' . $entry['signature_time']); ?></p>
                    <p><strong>Remarks:</strong></p>
                    <p><?php echo !empty($entry['remarks']) ? nl2br(htmlspecialchars($entry['remarks'])) : 'No remarks provided'; ?></p>
                </div>
            <?php endif; ?>
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
        </div>
        
        <a href="logbook.php" class="back-button">Back to Logbook</a>
    </div>
</div>

    <script>
    function confirmDelete(entryId) {
        if (confirm('Are you sure you want to delete this entry?')) {
            fetch('delete_log.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'entry_id=' + entryId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'logbook.php';
                } else {
                    alert('Error deleting entry: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting entry');
            });
        }
    }
    </script>
</body>
</html>