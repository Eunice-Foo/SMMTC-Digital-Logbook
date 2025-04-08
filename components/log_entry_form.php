<?php
function renderLogEntryForm($formData = null, $isEdit = false) {
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i', strtotime('+8 hours')); // UTC+8
    
    // Set default values or use existing data
    $title = $isEdit ? $formData['entry_title'] : '';
    $description = $isEdit ? $formData['entry_description'] : '';
    $date = $isEdit ? $formData['entry_date'] : $currentDate;
    $mediaFiles = $isEdit ? $formData['media_files'] : [];
    
    // If it's a new entry, set the current time
    if (!$isEdit) {
        $_SESSION['entry_time'] = $currentTime;
    }
    ?>
    
    <div class="form-header">
        <div class="form-group">
            <label for="date">Date:</label>
            <input type="date" 
                   id="date" 
                   name="date" 
                   value="<?php echo htmlspecialchars($date); ?>" 
                   required>
        </div>

        <div class="form-group">
            <label for="title">Title: (Optional)</label>
            <input type="text" 
                   id="title" 
                   name="title" 
                   value="<?php echo htmlspecialchars($title); ?>"
                   placeholder="Enter log title">
        </div>
    </div>

    <div class="form-group">
        <label for="description">Description:</label>
        <textarea id="description" 
                  name="description" 
                  rows="4" 
                  required
                  placeholder="Enter log description"><?php echo htmlspecialchars($description); ?></textarea>
    </div>

    <div class="form-group">
        <label for="media">Upload Media Files:</label>
        <?php 
        require_once 'components/media_upload_button.php';
        renderMediaUploadButton();
        ?>
        <div id="selectedFiles" class="selected-files"></div>
        <div id="previewArea" class="preview-area">
            <?php 
            // Fix the media files check
            if ($isEdit && !empty($mediaFiles) && is_array($mediaFiles)): 
                foreach ($mediaFiles as $media):
                    if (is_array($media) && isset($media['media_id']) && isset($media['file_name']) && isset($media['file_type'])):
            ?>
                <div class="preview-container" data-media-id="<?php echo $media['media_id']; ?>">
                    <?php if (strpos($media['file_type'], 'video/') === 0): ?>
                        <?php 
                        require_once 'components/video_thumbnail.php';
                        renderVideoThumbnail($media['file_name']);
                        ?>
                    <?php else: ?>
                        <div class="preview-item">
                            <img src="uploads/<?php echo htmlspecialchars($media['file_name']); ?>" alt="Media Preview">
                        </div>
                    <?php endif; ?>
                    <div class="file-info">
                        <span><?php echo htmlspecialchars($media['file_name']); ?></span>
                        <button type="button" class="remove-file-btn" onclick="removeExistingFile(this, <?php echo $media['media_id']; ?>)">×</button>
                    </div>
                </div>
            <?php 
                    endif;
                endforeach; 
            endif; 
            ?>
        </div>
    </div>
    <?php
}
?>