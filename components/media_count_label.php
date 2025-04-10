<?php
// filepath: c:\xampp\htdocs\log\components\media_count_label.php
function renderMediaCountLabel($mediaCount) {
    if ($mediaCount <= 1) return;
    ?>
    <div class="media-count" style="position: absolute; bottom: 10px; right: 10px; background: rgba(0, 0, 0, 0.7); color: white; padding: 4px 8px; border-radius: 8px; font-size: 12px; z-index: 3;">+<?php echo $mediaCount - 1; ?> more</div>
    <?php
}
?>