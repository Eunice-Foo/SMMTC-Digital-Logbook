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
                    // For images, prioritize thumbnails with fallbacks
                    $smThumb = "uploads/thumbnails/{$filename}_sm.webp";
                    $mdThumb = "uploads/thumbnails/{$filename}_md.webp";
                    $thumbWebP = "uploads/{$filename}.webp";
                    $fallbackOriginal = "uploads/{$media}";
                    ?>
                    
                    <img 
                        <?php if (file_exists($smThumb)): ?>
                            src="<?php echo $smThumb; ?>"
                        <?php elseif (file_exists($mdThumb)): ?>
                            src="<?php echo $mdThumb; ?>"
                        <?php elseif (file_exists($thumbWebP)): ?>
                            src="<?php echo $thumbWebP; ?>"
                        <?php else: ?>
                            src="<?php echo $fallbackOriginal; ?>"
                        <?php endif; ?>
                        alt="Media thumbnail" 
                        loading="lazy"
                        class="thumbnail-image">
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