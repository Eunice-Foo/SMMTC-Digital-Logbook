<?php
function renderMediaGallery($mediaFiles) {
    if (!empty($mediaFiles)): ?>
        <div class="media-gallery">
            <?php foreach (explode(',', $mediaFiles) as $media): ?>
                <div class="media-item">
                    <?php if (strpos($media, '.mp4') !== false || strpos($media, '.mov') !== false): ?>
                        <video width="100%" height="auto" controls>
                            <source src="uploads/<?php echo htmlspecialchars($media); ?>" type="video/mp4">
                        </video>
                    <?php else: ?>
                        <img src="uploads/<?php echo htmlspecialchars($media); ?>" alt="Media">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif;
}
?>