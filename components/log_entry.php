<?php
function renderLogEntry($entry, $showActions = true) {
    require_once 'components/media_count_label.php';
    ?>
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
                        
                        <?php renderMediaCountLabel(count($media_array), 4); // Pass 4 as maxDisplay value ?>
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
                    <p><?php echo nl2br(htmlspecialchars($entry['entry_description'])); ?></p>
                </div>
            </div>

            <!-- Right - Actions -->
            <?php 
            if ($showActions): 
                require_once 'components/log_actions.php';
                renderLogActions($entry['entry_id'], $entry['entry_status'], $_SESSION['role']);
            endif; 
            ?>
        </div>
    </div>
    <?php
}
?>