<?php
// filepath: c:\xampp\htdocs\log\components\media_upload_button.php

function renderMediaUploadButton() {
    ?>
    <div class="media-upload-section">
        <!-- File info text at the top with no margin -->
        <small class="file-info-text">Supported formats: images (.jpg, .png), videos (.mp4), documents (.docx, .pdf)</small>
        
        <!-- Container for the upload button and preview section in a row -->
        <div class="upload-preview-row">
            <div class="media-upload-container">
                <label for="media" class="media-upload-button">
                    <span class="plus-icon">+</span>
                </label>
                <input 
                    type="file" 
                    name="media[]" 
                    id="media" 
                    class="media-upload-input" 
                    multiple 
                    onchange="showSelectedFiles(this)" 
                    accept="image/*,video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                >
            </div>
            
            <div class="media-preview-section">
                <div id="selectedFiles" class="selected-files"></div>
                <div id="previewArea" class="preview-area"></div>
            </div>
        </div>
    </div>
    <?php
}
?>