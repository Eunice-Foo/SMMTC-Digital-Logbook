<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/month_bar.php'; // Add this line
require_once 'components/toast_notification.php'; // Include and initialize toast notification component

// Use the function from session_check.php
checkUserLogin();

try {
    // Prepare and execute query to get log entries with media and feedback information
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
        WHERE le.user_id = :user_id
        GROUP BY le.entry_id
        ORDER BY le.entry_date DESC, le.entry_time DESC
    ");

    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $log_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Add this query near the top after getting user info
$stmt = $conn->prepare("
    SELECT practicum_start_date, practicum_duration 
    FROM student 
    WHERE student_id = :user_id
");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$practicum_info = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Logbook</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/logbook.css">
    <link rel="stylesheet" href="css/media_viewer.css">
    <link rel="stylesheet" href="css/month_bar.css">
    <link rel="stylesheet" href="css/video_thumbnail.css">
    <link rel="stylesheet" href="css/export_logbook.css">
    <link rel="stylesheet" href="css/log_actions.css">
    <link rel="stylesheet" href="css/delete_modal.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/logbook.js"></script>
    <script src="js/media_viewer.js"></script>
    <script src="js/month_bar.js"></script>
    <script src="js/export_logbook.js" defer></script>
    <script src="js/delete_confirmation.js"></script>
</head>
<body data-user-id="<?php echo $_SESSION['user_id']; ?>">
    <?php 
    include 'components/side_menu.php'; 
    initializeToast(); // Initialize toast inside the body
    ?>
    <!-- Move the media viewer component call after the side menu -->
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
        <div class="view-switcher">
            <h1>My Logbook</h1>
        </div>

        <div class="logbook-content">
            <?php renderMonthBar($log_entries, $practicum_info['practicum_start_date'], $practicum_info['practicum_duration']); ?>
            
            <div class="button-container" style="display: flex; justify-content: flex-end; gap: 10px; padding: 20px;">
                <!-- Regular buttons -->
                <div id="regularButtons">
                    <button class="btn btn-export" onclick="toggleExportMode()">
                        Export Logbook
                    </button>
                    <button class="btn btn-add" onclick="window.location.href='add_new_log.php'">
                        Add New Log
                    </button>
                </div>

                <!-- Export controls -->
                <div id="exportControls" style="display: none;">
                    <!-- Left side -->
                    <div class="export-left">
                        <div class="selected-count">
                            <span id="selectedCount">0</span> selected
                        </div>
                        <label class="select-all">
                            <input type="checkbox" onchange="selectAllEntries(this.checked)">
                            Select All
                        </label>
                    </div>
                    <!-- Right side -->
                    <div class="export-buttons">
                        <button id="downloadButton" class="btn btn-export" onclick="downloadExport()" disabled>
                            Export
                        </button>
                        <button class="btn btn-secondary" onclick="toggleExportMode()">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <div id="previewArea" style="display: none;"></div>
            
            <div class="logbook-entries">
                <?php 
                require_once 'components/log_entry.php';
                foreach ($log_entries as $entry):
                    renderLogEntry($entry, true);
                endforeach; 
                ?>
            </div>
        </div>
    </div>
    
    <script src="js/video_thumbnail.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded');
        const exportButton = document.querySelector('.btn-export');
        console.log('Export button found:', !!exportButton);
        
        if (exportButton) {
            exportButton.addEventListener('click', function() {
                console.log('Export button clicked');
            });
        }
        
        // Test if toggleExportMode is available
        console.log('toggleExportMode function available:', typeof toggleExportMode === 'function');
    });
    </script>
    <script src="js/delete_confirmation.js"></script> <!-- Include the delete confirmation script at the end of the body -->
</body>
</html>