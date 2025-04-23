<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/log_entry.php';
require_once 'components/supervisor_feedback.php'; // Add this line

// Check if user is supervisor and student_id is provided
if ($_SESSION['role'] != ROLE_SUPERVISOR || !isset($_GET['student_id'])) {
    header('Location: sv_main.php');
    exit();
}

try {
    // Get student information
    $stmt = $conn->prepare("
        SELECT 
            s.full_name,
            s.matric_no,
            s.phone_number,
            s.institution,
            u.email
        FROM student s
        INNER JOIN user u ON s.student_id = u.user_id
        WHERE s.student_id = :student_id
    ");
    
    $stmt->bindParam(':student_id', $_GET['student_id']);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get pending log entries
    $stmt = $conn->prepare("
        SELECT 
            le.entry_id,
            le.entry_title,
            le.entry_description,
            le.entry_date,
            le.entry_time,
            le.entry_status,
            GROUP_CONCAT(DISTINCT m.file_name) as media_files,
            GROUP_CONCAT(DISTINCT m.file_type) as media_types,
            f.remarks,
            f.signature_date,
            f.signature_time
        FROM log_entry le
        LEFT JOIN log_media lm ON le.entry_id = lm.entry_id
        LEFT JOIN media m ON lm.media_id = m.media_id
        LEFT JOIN feedback f ON le.entry_id = f.entry_id
        WHERE le.user_id = :student_id 
        AND le.entry_status = 'Pending'
        GROUP BY le.entry_id
        ORDER BY le.entry_date DESC, le.entry_time DESC
    ");
    
    $stmt->bindParam(':student_id', $_GET['student_id'], PDO::PARAM_INT);
    $stmt->execute();
    $pending_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pending_count = count($pending_logs);

    // Get reviewed log entries
    $stmt = $conn->prepare("
        SELECT 
            le.entry_id,
            le.entry_title,
            le.entry_description,
            le.entry_date,
            le.entry_time,
            le.entry_status,
            GROUP_CONCAT(DISTINCT m.file_name) as media_files,
            GROUP_CONCAT(DISTINCT m.file_type) as media_types,
            f.remarks,
            f.signature_date,
            f.signature_time
        FROM log_entry le
        LEFT JOIN log_media lm ON le.entry_id = lm.entry_id
        LEFT JOIN media m ON lm.media_id = m.media_id
        LEFT JOIN feedback f ON le.entry_id = f.entry_id
        WHERE le.user_id = :student_id 
        AND le.entry_status = 'Signed'
        GROUP BY le.entry_id
        ORDER BY le.entry_date DESC, le.entry_time DESC
    ");
    
    $stmt->bindParam(':student_id', $_GET['student_id'], PDO::PARAM_INT);
    $stmt->execute();
    $reviewed_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $reviewed_count = count($reviewed_logs);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Logbook</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/logbook.css">
    <link rel="stylesheet" href="css/log_entry.css">
    <link rel="stylesheet" href="css/media_viewer.css">
    <link rel="stylesheet" href="css/video_thumbnail.css">
    <link rel="stylesheet" href="css/remark_modal.css">
    <style>
        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }

        .student-table th,
        .student-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .student-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .student-table tr:hover {
            background-color: #f9f9f9;
        }

        .student-info {
            background: white;
 
        }
        .section-header {
            background-color: var(--bg-secondary);
            padding: 15px;
            margin-top: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <?php 
    include 'components/topnav.php'; 
    
    // Include and initialize toast notification component
    require_once 'components/toast_notification.php';
    initializeToast();
    ?>
    
    <!-- Add Media Viewer Component -->
    <div id="mediaViewer" class="media-viewer">
        <div class="media-viewer-content">
            <button class="nav-button prev-button" onclick="navigateMedia(-1)">❮</button>
            <div class="main-media-container">
                <div class="media-display"></div>
            </div>
            <button class="nav-button next-button" onclick="navigateMedia(1)">❯</button>
            <button class="close-button" onclick="closeMediaViewer()">×</button>
        </div>
        <div class="media-thumbnails">
            <!-- Thumbnails will be generated dynamically -->
        </div>
    </div>

    <div class="main-content">
        <!-- Student Information -->
        <div class="student-info">
            <h2>Student Information</h2>
            <table class="student-table">
                <tr>
                    <th>Full Name</th>
                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                    <th>Matric No</th>
                    <td><?php echo htmlspecialchars($student['matric_no']); ?></td>
                </tr>
                <tr>
                    <th>Email Address</th>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <th>Phone</th>
                    <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
                </tr>
                <tr>
                    <th>Institution</th>
                    <td colspan="3"><?php echo htmlspecialchars($student['institution']); ?></td>
                </tr>
            </table>
        </div>

        <!-- Pending Logs Section -->
        <div class="section-header">
            <h3>Pending Review</h3>
            <span>(<?php echo $pending_count; ?> log entries)</span>
        </div>
        
        <?php if ($pending_count > 0): ?>
            <?php 
            foreach ($pending_logs as $log) {
                renderLogEntry($log);
            }
            ?>
        <?php else: ?>
            <p class="no-entries">No pending entries</p>
        <?php endif; ?>

        <!-- Reviewed Logs Section -->
        <div class="section-header">
            <h3>Reviewed and Signed</h3>
            <span>(<?php echo $reviewed_count; ?> log entries)</span>
        </div>
        
        <?php 
        foreach ($reviewed_logs as $log) {
            renderLogEntry($log, false);
        }
        ?>
    </div>

    <!-- Include required scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/video_thumbnail.js"></script>
    <script src="js/media_viewer.js"></script>
    <script src="js/sv_sign_log.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            generateVideoThumbnails();
        });
    </script>
</body>
</html>
<?php
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>