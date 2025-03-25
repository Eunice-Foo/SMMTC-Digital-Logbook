<?php
require_once 'includes/upload_validation.php';
require_once 'includes/file_naming.php';

function renderFileUpload($inputName = 'media', $multiple = true) { ?>
    <div class="form-group">
        <label for="<?php echo $inputName; ?>">Upload Media Files:</label>
        <input type="file" 
               id="<?php echo $inputName; ?>" 
               name="<?php echo $inputName; ?><?php echo $multiple ? '[]' : ''; ?>" 
               <?php echo $multiple ? 'multiple' : ''; ?> 
               accept="image/*,video/*" 
               onchange="showSelectedFiles(this)">
        <button type="button" class="clear-files-btn" onclick="clearFiles()">Clear Selected Files</button>
        <div id="selectedFiles" class="selected-files"></div>
        <div id="previewArea" class="preview-area"></div>
    </div>
    <div class="progress">
        <div class="progress-bar" style="width: 0%"></div>
    </div>
<?php }
?>