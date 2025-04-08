<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/month_bar.php'; // Add this line

// Use the function from session_check.php
checkUserLogin();

try {
    // Prepare and execute query to get log entries with media
    $stmt = $conn->prepare("
        SELECT 
            le.entry_id,
            le.entry_title,
            le.entry_description,
            le.entry_status,
            le.entry_date,
            le.entry_time,
            GROUP_CONCAT(m.file_name) as media_files
        FROM log_entry le
        LEFT JOIN log_media lm ON le.entry_id = lm.entry_id
        LEFT JOIN media m ON lm.media_id = m.media_id
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/logbook.js"></script>
    <script src="js/media_viewer.js"></script>
    <script src="js/month_bar.js"></script>
    <script src="js/export_logbook.js" defer></script>
</head>
<body data-user-id="<?php echo $_SESSION['user_id']; ?>">
    <?php include 'components/side_menu.php'; ?>
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
            <button class="view-btn active" onclick="switchView('timeline')">Timeline</button>
            <button class="view-btn" onclick="switchView('calendar')">Calendar</button>
        </div>

        <div class="timeline-view">
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
                <?php foreach ($log_entries as $entry): ?>
                    <div class="log-entry" data-date="<?php echo $entry['entry_date']; ?>">
                        <div class="log-entry-grid">
                            <?php if (!empty($entry['media_files'])): ?>
                                <div class="log-media-section">
                                    <div class="media-gallery-preview">
                                        <?php 
                                        $media_array = explode(',', $entry['media_files']);
                                        foreach (array_slice($media_array, 0, 4) as $media): // Show max 4 items
                                        ?>
                                            <div class="media-preview" onclick="initMediaViewer(<?php 
                                                echo htmlspecialchars(json_encode($media_array)); ?>, 
                                                <?php echo array_search($media, $media_array); ?>)">
                                                <?php if (strpos($media, '.mp4') !== false || strpos($media, '.mov') !== false): ?>
                                                    <?php 
                                                    require_once 'components/video_thumbnail.php';
                                                    renderVideoThumbnail($media);
                                                    ?>
                                                <?php else: ?>
                                                    <img src="uploads/<?php echo htmlspecialchars($media); ?>" alt="Log Entry Media">
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (count($media_array) > 4): ?>
                                            <div class="media-count">+<?php echo count($media_array) - 4; ?> more</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Middle - Content -->
                            <div class="log-content">
                                <div class="log-header">
                                    <div class="log-datetime">
                                        <div class="datetime-group">
                                            <h3><?php 
                                                $timestamp = strtotime($entry['entry_date'] . ' ' . $entry['entry_time']);
                                                echo date('M d, Y (l)', $timestamp);
                                            ?></h3>
                                            <span class="upload-time">
                                                <?php echo "Added on: " . date('g:i A', $timestamp); ?>
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
                                    <p><?php echo htmlspecialchars($entry['entry_description']); ?></p>
                                </div>
                            </div>

                            <!-- Right - Actions -->
                            <div class="log-actions" data-entry-id="<?php echo $entry['entry_id']; ?>">
                                <button class="btn btn-view" onclick="window.location.href='view_log.php?id=<?php echo $entry['entry_id']; ?>'">
                                    View
                                </button>
                                <?php if ($entry['entry_status'] !== 'reviewed'): ?>
                                    <button class="btn btn-edit" onclick="window.location.href='edit_log.php?id=<?php echo $entry['entry_id']; ?>'">
                                        Edit
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-delete" onclick="confirmDelete(<?php echo $entry['entry_id']; ?>)">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="calendar-view" style="display: none;">
            <?php renderMonthBar($log_entries); ?>

            <div style="display: flex; justify-content: flex-end; gap: 10px; padding: 20px;">
                <button class="btn btn-export" onclick="window.location.href='export_logbook.php'">
                    Export Logbook
                </button>
                <button class="btn btn-add" onclick="window.location.href='add_new_log.php'">
                    Add New Log
                </button>
            </div>

            <div id="calendar-container">
                <!-- Calendar will be loaded here -->
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
</body>
</html>