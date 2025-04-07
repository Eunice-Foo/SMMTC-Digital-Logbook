<?php
function renderVideoThumbnail($mediaFile, $isViewer = false) {
    ?>
    <div class="video-thumbnail">
        <div class="thumbnail-container">
            <video preload="metadata">
                <source src="uploads/<?php echo htmlspecialchars($mediaFile); ?>" type="video/mp4">
            </video>
            <canvas class="video-canvas"></canvas>
            <div class="play-button">▶</div>
        </div>
    </div>
    <?php
}
?>