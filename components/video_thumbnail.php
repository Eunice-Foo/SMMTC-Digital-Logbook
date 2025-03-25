<?php
function renderVideoThumbnail($mediaFile, $isViewer = false) {
    ?>
    <div class="video-thumbnail" data-video="uploads/<?php echo htmlspecialchars($mediaFile); ?>">
        <div class="thumbnail-container">
            <video preload="metadata">
                <source src="uploads/<?php echo htmlspecialchars($mediaFile); ?>" type="video/mp4">
            </video>
            <canvas class="video-canvas"></canvas>
            <div class="<?php echo $isViewer ? 'play-indicator' : 'play-button'; ?>">▶</div>
        </div>
    </div>
    <?php
}
?>