<?php
// filepath: c:\xampp\htdocs\log\components\media_count_label.php
function renderMediaCountLabel($mediaCount, $maxDisplay = 1) {
    // For log entries, we display up to 4 items, so only show count if there are more than 4
    if ($mediaCount <= $maxDisplay) return;
    ?>
    <div class="media-count" style="position: absolute; bottom: 10px; right: 10px; background: rgba(0, 0, 0, 0.7); color: white; padding: 4px 8px; border-radius: 8px; font-size: 12px; z-index: 3;">+<?php echo $mediaCount - $maxDisplay; ?> more</div>
    <?php
}
?>