<?php
function renderLogEntryForm($formData = null) {
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i', strtotime('+8 hours')); // UTC+8
    
    // Set default values or use existing data
    $title = isset($formData['title']) ? $formData['title'] : '';
    $description = isset($formData['description']) ? $formData['description'] : '';
    $date = isset($formData['date']) ? $formData['date'] : $currentDate;
    $mediaFiles = isset($formData['mediaFiles']) ? $formData['mediaFiles'] : [];
    $isEdit = isset($formData['isEdit']) ? $formData['isEdit'] : false;
    $showCustomButtons = isset($formData['showCustomButtons']) ? $formData['showCustomButtons'] : false;
    
    // If it's a new entry, set the current time
    if (!$isEdit) {
        $_SESSION['entry_time'] = $currentTime;
    }
    
    $formId = $isEdit ? 'editLogForm' : 'addLogForm';
    ?>
    
    <!-- IMPORTANT: Ensure enctype is multipart/form-data -->
    <form id="<?php echo $formId; ?>" action="<?php echo $isEdit ? 'edit_log.php?id=' . $_GET['id'] : 'add_new_log.php'; ?>" method="POST" enctype="multipart/form-data">
        <div class="form-header">
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" 
                       id="date" 
                       name="date" 
                       value="<?php echo htmlspecialchars($date); ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="title">Title (Optional)</label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       value="<?php echo htmlspecialchars($title); ?>"
                       placeholder="Enter log title">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" 
                      name="description" 
                      rows="4" 
                      required
                      placeholder="Enter log description"><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <div class="form-group">
            <label for="media">Upload Media Files</label>
            <?php 
            require_once 'components/media_upload_button.php';
            renderMediaUploadButton();
            ?>
        </div>

        <div class="form-group">
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
        
        <input type="hidden" name="time" value="<?php echo isset($formData['time']) ? $formData['time'] : $currentTime; ?>">
        
        <!-- Form buttons container with right alignment -->
        <div class="form-buttons">
            <?php if ($showCustomButtons): ?>
                <button type="button" class="cancel-btn">Cancel</button>
                <button type="submit" class="submit-btn">
                    <i class="fi fi-rr-square-plus"></i> Add New
                </button>
            <?php else: ?>
                <button type="submit" class="submit-button">
                    <?php if ($isEdit): ?>
                        Save Changes
                    <?php else: ?>
                        <i class="fi fi-rr-square-plus"></i> Add New
                    <?php endif; ?>
                </button>
            <?php endif; ?>
        </div>
    </form>
    <?php
}
?>