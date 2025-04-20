<?php
// filepath: c:\xampp\htdocs\log\components\media_gallery_preview.php
function renderMediaGalleryPreview($mediaFiles, $maxDisplay = 4) {
    if (empty($mediaFiles)) return;
    
    $media_array = is_array($mediaFiles) ? $mediaFiles : explode(',', $mediaFiles);
    $displayCount = min(count($media_array), $maxDisplay);
    ?>
    
    <div class="media-gallery-preview">
        <?php for ($i = 0; $i < $displayCount; $i++): 
            $media = $media_array[$i];
            if (empty($media)) continue;
        ?>
            <div class="media-preview">
                <?php if (strpos($media, '.mp4') !== false || strpos($media, '.mov') !== false): ?>
                    <div class="video-placeholder">
                        <span>🎬</span>
                    </div>
                <?php else: ?>
                    <img src="uploads/<?php echo htmlspecialchars($media); ?>" alt="Media Preview" loading="lazy">
                <?php endif; ?>
            </div>
        <?php endfor; ?>
        
        <?php if (count($media_array) > $maxDisplay): ?>
            <div class="media-preview more-indicator">
                <span>+<?php echo count($media_array) - $maxDisplay; ?> more</span>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>