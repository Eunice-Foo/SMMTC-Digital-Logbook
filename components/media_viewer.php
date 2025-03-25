<?php
function renderMediaViewer($mediaFiles) {
    if (empty($mediaFiles)) return;
    ?>
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
            <?php
            $media_array = explode(',', $mediaFiles);
            if (isset($media_array)):
                foreach ($media_array as $index => $media): ?>
                    <div class="thumbnail" onclick="showMedia(<?php echo $index; ?>)">
                        <?php if (strpos($media, '.mp4') !== false || strpos($media, '.mov') !== false): ?>
                            <?php 
                            require_once 'components/video_thumbnail.php';
                            renderVideoThumbnail($media, true); // Pass true for media viewer context
                            ?>
                        <?php else: ?>
                            <img src="uploads/<?php echo htmlspecialchars($media); ?>" alt="Media Thumbnail">
                        <?php endif; ?>
                    </div>
                <?php endforeach;
            endif; ?>
        </div>
    </div>
    <?php
}
?>