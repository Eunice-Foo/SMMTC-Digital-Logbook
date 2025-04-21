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
                    <div class="video-placeholder">
                        <span>🎬</span>
                    </div>
                <?php else: ?>
                    <!-- Low-quality image placeholder (LQIP) with lazy loaded thumbnail -->
                    <?php
                        // Check if base64 encoded LQIP exists
                        $lqipBase64 = '';
                        $lqipPath = "uploads/thumbnails/{$filename}_lqip.b64";
                        if (file_exists($lqipPath)) {
                            $lqipBase64 = file_get_contents($lqipPath);
                        }
                    ?>
                    <?php if (!empty($lqipBase64)): ?>
                    <img 
                        src="data:image/webp;base64,<?php echo $lqipBase64; ?>"
                        data-src="uploads/thumbnails/<?php echo $filename; ?>_sm.webp"
                        class="lazy-image"
                        loading="lazy" 
                        alt="Media Preview"
                        style="filter: blur(10px); transition: filter 0.3s ease-out;">
                    <?php else: ?>
                    <!-- Fallback for images without thumbnails -->
                    <img 
                        src="uploads/thumbnails/<?php echo $filename; ?>_sm.webp"
                        onerror="this.onerror=null; this.src='uploads/<?php echo htmlspecialchars($media); ?>'"
                        class="media-image"
                        loading="lazy" 
                        alt="Media Preview">
                    <?php endif; ?>
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