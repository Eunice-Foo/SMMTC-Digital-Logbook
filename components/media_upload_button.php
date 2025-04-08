<?php
function renderMediaUploadButton($inputId = 'media', $inputName = 'media[]') {
    ?>
    <div class="media-upload-container">
        <input type="file" 
               id="<?php echo $inputId; ?>" 
               name="<?php echo $inputName; ?>" 
               multiple 
               accept="image/*,video/*" 
               onchange="showSelectedFiles(this)"
               class="media-upload-input">
        <div class="media-upload-button">
            <span class="plus-icon">+</span>
        </div>
    </div>
    <?php
}
?>