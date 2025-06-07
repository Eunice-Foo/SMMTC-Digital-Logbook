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
            
            // Extract filename without extension for thumbnail paths
            $filename = pathinfo($media, PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));
            $isVideo = in_array($ext, ['mp4', 'mov']);
        ?>
            <div class="media-preview">
                <?php if ($isVideo): ?>
                    <div class="video-thumbnail-container">
                        <?php
                        // Use appropriate video thumbnail path
                        $videoThumb = "uploads/thumbnails/{$filename}.jpg";
                        if (file_exists($videoThumb)):
                        ?>
                            <img src="<?php echo $videoThumb; ?>" alt="Video thumbnail" class="thumbnail-image">
                            <div class="play-indicator">▶</div>
                        <?php else: ?>
                            <div class="video-placeholder">
                                <span>🎬</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php
                    // For images, check thumbnail with fallbacks using new naming convention
                    $thumbWebP = "uploads/thumbnails/{$filename}_thumb.webp";
                    $smWebP = "uploads/thumbnails/{$filename}_sm.webp";   // Legacy fallback
                    $mdWebP = "uploads/thumbnails/{$filename}_md.webp";   // Legacy fallback
                    $mainWebP = "uploads/{$filename}.webp";
                    $fallbackOriginal = "uploads/{$media}";
                    ?>
                    
                    <img 
                        <?php if (file_exists($thumbWebP)): ?>
                            src="<?php echo $thumbWebP; ?>"
                        <?php elseif (file_exists($smWebP)): ?>
                            src="<?php echo $smWebP; ?>"
                        <?php elseif (file_exists($mdWebP)): ?>
                            src="<?php echo $mdWebP; ?>"
                        <?php elseif (file_exists($mainWebP)): ?>
                            src="<?php echo $mainWebP; ?>"
                        <?php else: ?>
                            src="<?php echo $fallbackOriginal; ?>"
                        <?php endif; ?>
                        alt="Media thumbnail" 
                        loading="lazy"
                        class="thumbnail-image">
                <?php endif; ?>
            </div>
        <?php endfor; ?>
        
        <?php 
        // Replace the current media indicator with the same component from logbook
        require_once 'components/media_count_label.php';
        renderMediaCountLabel(count($media_array), $maxDisplay);
        ?>
    </div>
    <?php
}
?>