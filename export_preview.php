<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/supervisor_signature.php';
require_once 'components/export_logbook_profile.php';

if (!isset($_POST['entries'])) {
    header('Location: export_logbook.php');
    exit();
}

$entries = json_decode($_POST['entries']);
$includeMedia = $_POST['includeMedia'] === 'true';

try {
    // Get student info
    $stmt = $conn->prepare("
        SELECT 
            s.full_name, 
            s.matric_no, 
            s.institution
        FROM student s
        WHERE s.student_id = :user_id
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $student_info = $stmt->fetch(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="css/export_preview.css">
    <link rel="stylesheet" href="css/export_preview_control.css">
    <script src="js/export_preview.js" defer></script>
    <script src="js/export_preview_control.js" defer></script>
    <script src="js/logbook_profile_page.js" defer></script>
</head>
<body>
    <!-- Profile Page -->
    <div class="preview-container profile-page">
        <?php renderLogbookProfile($conn, $_SESSION['user_id']); ?>
    </div>

    <!-- Log Entries -->
    <div class="preview-container">
        <!-- Log entries will be organized into pages by JavaScript -->
        <?php foreach ($log_entries as $entry): ?>
            <div class="log-entry">
                <div class="entry-header">
                    <div class="log-datetime">
                        <h3><?php 
                            $timestamp = strtotime($entry['entry_date']);
                            echo date('M d, Y (l)', $timestamp);
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
                        foreach ($media_files as $index => $file): 
                            // Check file extension to determine type
                            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            $is_video = in_array($extension, ['mp4', 'mov']);
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
                                        // Check if file is actually an image
                                        if (file_exists($image_path) && getimagesize($image_path)): ?>
                                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Entry Media">
                                        <?php else: ?>
                                            <div class="image-placeholder">
                                                <?php if (file_exists($image_path)): ?>
                                                    Invalid image format
                                                <?php else: ?>
                                                    Image not found
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($entry['supervisor_remarks'])): ?>
                    <div class="supervisor-feedback">
                        <h4>Supervisor's Remarks</h4>
                        <p><?php echo nl2br(htmlspecialchars($entry['supervisor_remarks'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <?php renderSupervisorSignature($conn, $_SESSION['user_id']); ?>
    </div>

    <?php include 'components/export_preview_control.php'; ?>
</body>
</html>