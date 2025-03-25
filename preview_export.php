<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

if (!isset($_POST['entries'])) {
    header('Location: export_logbook.php');
    exit();
}

$entries = json_decode($_POST['entries']);
$includeMedia = $_POST['includeMedia'] === 'true';

try {
    // Get user info for header
    $stmt = $conn->prepare("
        SELECT s.full_name, s.matric_no, s.institution 
        FROM student s 
        WHERE s.student_id = :user_id
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get log entries with media and feedback
    $stmt = $conn->prepare("
        SELECT 
            le.*,
            GROUP_CONCAT(DISTINCT m.file_name) as media_files,
            GROUP_CONCAT(DISTINCT m.file_type) as media_types,
            f.remarks as supervisor_remarks,
            f.signature_date,
            f.signature_time
        FROM log_entry le
        LEFT JOIN log_media lm ON le.entry_id = lm.entry_id
        LEFT JOIN media m ON lm.media_id = m.media_id
        LEFT JOIN feedback f ON le.entry_id = f.entry_id
        WHERE le.entry_id IN (" . str_repeat('?,', count($entries)-1) . "?)
        GROUP BY le.entry_id
        ORDER BY le.entry_date, le.entry_time
    ");
    $stmt->execute($entries);
    $log_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Logbook Export Preview</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/preview_export.css">
    <link rel="stylesheet" href="css/video_thumbnail.css">
    <script src="js/video_thumbnail.js" defer></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize video thumbnails after page loads
        generateVideoThumbnails();
    });
    </script>
</head>
<body>
    <div class="preview-container">
        <div class="document-header">
            <h1>Internship Logbook</h1>
            <div class="student-info">
                <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
                <p>Matric No: <?php echo htmlspecialchars($student['matric_no']); ?></p>
                <p><?php echo htmlspecialchars($student['institution']); ?></p>
            </div>
        </div>

        <?php foreach ($log_entries as $entry): ?>
            <div class="log-entry">
                <div class="entry-header">
                    <div class="log-datetime">
                        <h3><?php 
                            $timestamp = strtotime($entry['entry_date'] . ' ' . $entry['entry_time']);
                            echo date('M d, Y (l), g:i A', $timestamp);
                        ?></h3>
                    </div>
                </div>

                <div class="log-content">
                    <?php if (!empty($entry['entry_title'])): ?>
                        <div class="log-title">
                            <h3><?php echo htmlspecialchars($entry['entry_title']); ?></h3>
                        </div>
                    <?php endif; ?>
                    
                    <div class="log-description">
                        <p><?php echo nl2br(htmlspecialchars($entry['entry_description'])); ?></p>
                    </div>
                </div>

                <?php if ($includeMedia && !empty($entry['media_files'])): ?>
                    <div class="media-gallery">
                        <?php 
                        $media_files = explode(',', $entry['media_files']);
                        $media_types = !empty($entry['media_types']) ? explode(',', $entry['media_types']) : array();
                        
                        foreach ($media_files as $index => $file): 
                            $is_video = isset($media_types[$index]) && strpos($media_types[$index], 'video/') === 0;
                            ?>
                            <div class="media-item">
                                <?php if ($is_video): ?>
                                    <?php 
                                    require_once 'components/video_thumbnail.php';
                                    renderVideoThumbnail($file);
                                    ?>
                                <?php else: ?>
                                    <div class="image-container">
                                        <?php 
                                        $image_path = 'uploads/' . $file;
                                        if (file_exists($image_path)): ?>
                                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Entry Media">
                                        <?php else: ?>
                                            <div class="image-placeholder">Image not found</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($entry['supervisor_remarks'])): ?>
                    <div class="supervisor-feedback">
                        <h4>Supervisor Remarks</h4>
                        <p><?php echo nl2br(htmlspecialchars($entry['supervisor_remarks'])); ?></p>
                        <small>Signed on: <?php echo date('F j, Y g:i A', strtotime($entry['signature_date'] . ' ' . $entry['signature_time'])); ?></small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <button onclick="window.print()" class="print-button no-print">Print Document</button>
</body>
</html>