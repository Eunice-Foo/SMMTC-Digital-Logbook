// Global variables
let selectedFiles = []; // Store new files
let existingFiles = new Set(); // Store names of existing files
let deletedMediaIds = []; // Store IDs of files to delete (for edit mode)

document.addEventListener('DOMContentLoaded', function() {
    console.log('file_upload.js loaded');
    
    // Initialize existing files if any
    if (typeof document.currentMediaFiles !== 'undefined' && document.currentMediaFiles) {
        document.currentMediaFiles.forEach(file => {
            if (file.file_name) {
                existingFiles.add(file.file_name);
            }
        });
    }

    // Initialize existing media previews if in edit mode
    initializeExistingMediaPreviews();
    
    // Initialize video thumbnails for uploaded videos
    if (typeof generateVideoThumbnails === 'function') {
        generateVideoThumbnails();
    }
});

/**
 * Initialize existing media previews for edit mode
 */
function initializeExistingMediaPreviews() {
    const previewArea = document.getElementById('previewArea');
    if (!previewArea || !document.currentMediaFiles || !Array.isArray(document.currentMediaFiles)) {
        return;
    }
    
    // Clear the preview area first
    while (previewArea.firstChild) {
        previewArea.removeChild(previewArea.firstChild);
    }
    
    // Create preview containers for each existing file
    document.currentMediaFiles.forEach(media => {
        if (!media.file_name || !media.media_id || !media.file_type) {
            return;
        }
        
        const container = document.createElement('div');
        container.className = 'preview-container';
        container.dataset.mediaId = media.media_id;
        
        // Create appropriate preview based on file type
        if (media.file_type.startsWith('image/')) {
            const preview = document.createElement('div');
            preview.className = 'preview-item';
            preview.innerHTML = `<img src="uploads/${media.file_name}" alt="${media.file_name}">`;
            container.appendChild(preview);
        } else if (media.file_type.startsWith('video/')) {
            const preview = document.createElement('div');
            preview.className = 'preview-item video-preview';
            
            const thumbnailContainer = document.createElement('div');
            thumbnailContainer.className = 'video-thumbnail';
            
            const canvas = document.createElement('canvas');
            canvas.className = 'video-canvas';
            canvas.dataset.videoSrc = `uploads/${media.file_name}`;
            
            const playButton = document.createElement('div');
            playButton.className = 'play-button';
            playButton.innerHTML = '▶';
            
            thumbnailContainer.appendChild(canvas);
            thumbnailContainer.appendChild(playButton);
            preview.appendChild(thumbnailContainer);
            container.appendChild(preview);
        } else {
            // For other file types (PDF, docs, etc)
            const preview = document.createElement('div');
            preview.className = 'preview-item file-icon';
            
            let icon = '📄';
            if (media.file_type.includes('pdf')) icon = '📝';
            else if (media.file_type.includes('word') || media.file_type.includes('document')) icon = '📃';
            
            preview.innerHTML = `<div class="file-icon-container">${icon}</div>`;
            container.appendChild(preview);
        }
        
        // Add file info and remove button
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        fileInfo.innerHTML = `
            <span>${media.file_name}</span>
            <button type="button" class="remove-file-btn" onclick="removeExistingFile(this, ${media.media_id})">×</button>
        `;
        container.appendChild(fileInfo);
        
        previewArea.appendChild(container);
    });
}

function showSelectedFiles(input) {
    console.log('showSelectedFiles called');
    const previewArea = document.getElementById('previewArea');
    if (!previewArea) {
        console.error('Preview area not found');
        return;
    }
    
    const files = Array.from(input.files);
    console.log('Files selected:', files.length);
    
    files.forEach(file => {
        // Check if file already exists
        if (!existingFiles.has(file.name)) {
            // Add to selected files array for upload
            selectedFiles.push(file);
            console.log('Added file to selection:', file.name, file.type, file.size);
            
            // Create preview container
            const previewContainer = createPreviewContainer(file);
            previewArea.appendChild(previewContainer);
        } else {
            console.log('File already exists:', file.name);
            if (typeof showWarningToast === 'function') {
                showWarningToast(`File "${file.name}" already exists in this log entry.`, 'Duplicate File');
            } else {
                alert(`File "${file.name}" already exists in this log entry.`);
            }
        }
    });

    // Clear the file input to allow selecting the same file again
    input.value = '';
}

function createPreviewContainer(file) {
    const container = document.createElement('div');
    container.className = 'preview-container';
    container.dataset.fileName = file.name;
    
    if (file.type.startsWith('image/')) {
        createImagePreview(file, container);
    } else if (file.type.startsWith('video/')) {
        createVideoPreview(file, container);
    } else {
        // For other file types (PDF, docs, etc)
        const preview = document.createElement('div');
        preview.className = 'preview-item file-icon';
        
        let icon = '📄';
        if (file.type.includes('pdf')) icon = '📝';
        else if (file.type.includes('word') || file.type.includes('document')) icon = '📃';
        
        preview.innerHTML = `<div class="file-icon-container">${icon}</div>`;
        container.appendChild(preview);
    }
    
    // Add file info after the preview
    const fileInfo = createFileInfo(file);
    container.appendChild(fileInfo);
    
    return container;
}

function createFileInfo(file) {
    const fileInfo = document.createElement('div');
    fileInfo.className = 'file-info';
    fileInfo.innerHTML = `
        <span>${file.name}</span>
        <button type="button" class="remove-file-btn" onclick="removeFile(this)">×</button>
    `;
    return fileInfo;
}

function createImagePreview(file, container) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.createElement('div');
        preview.className = 'preview-item';
        preview.innerHTML = `<img src="${e.target.result}" alt="${file.name}">`;
        container.appendChild(preview);
    }
    reader.readAsDataURL(file);
}

function createVideoPreview(file, container) {
    const preview = document.createElement('div');
    preview.className = 'preview-item video-preview';
    
    const thumbnailContainer = document.createElement('div');
    thumbnailContainer.className = 'video-thumbnail';
    
    const video = document.createElement('video');
    video.preload = 'metadata';
    video.style.display = 'none';
    video.src = URL.createObjectURL(file);
    
    const canvas = document.createElement('canvas');
    canvas.className = 'video-canvas';
    
    const playButton = document.createElement('div');
    playButton.className = 'play-button';
    playButton.innerHTML = '▶';
    
    thumbnailContainer.appendChild(video);
    thumbnailContainer.appendChild(canvas);
    thumbnailContainer.appendChild(playButton);
    preview.appendChild(thumbnailContainer);
    container.appendChild(preview);
    
    generateThumbnail(video, canvas);
}

function generateThumbnail(video, canvas) {
    video.addEventListener('loadeddata', function() {
        video.currentTime = 1;
    });

    video.addEventListener('seeked', function() {
        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(video.src);
        video.remove();
    });
}

function removeFile(button) {
    const container = button.closest('.preview-container');
    const fileName = container.dataset.fileName;
    selectedFiles = selectedFiles.filter(file => file.name !== fileName);
    container.remove();
}

function removeExistingFile(button, mediaId) {
    if (typeof showWarningToast === 'function') {
        // Create unique toast ID for this confirmation
        const confirmToastId = 'delete-confirm-' + mediaId;
        
        // Show the warning toast first
        showWarningToast(
            'Are you sure you want to remove this file?', 
            'Confirm Deletion', 
            'Delete'
        );
        
        // Get the toast button and add a direct click handler
        setTimeout(() => {
            const toastButton = document.querySelector('.toast.warning .toast-button');
            if (toastButton) {
                // Replace the default click handler with our custom one
                toastButton.onclick = function() {
                    // Close all toasts
                    const toasts = document.querySelectorAll('.toast');
                    toasts.forEach(toast => {
                        toast.classList.add('toast-hide');
                        setTimeout(() => {
                            if (toast && toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    });
                    
                    // Store the media ID for server-side deletion
                    deletedMediaIds.push(mediaId);
                    
                    // Get the container and remove it from the DOM immediately
                    const container = button.closest('.preview-container');
                    if (container) {
                        // Add a fade-out animation before removal
                        container.style.transition = 'opacity 0.3s ease';
                        container.style.opacity = '0';
                        
                        // Remove from DOM after animation completes
                        setTimeout(() => {
                            container.remove();
                        }, 300);
                    }
                };
            }
        }, 100); // Small delay to ensure toast is rendered
    } else if (confirm('Are you sure you want to remove this file?')) {
        // Fallback for when toast function isn't available
        deletedMediaIds.push(mediaId);
        
        const container = button.closest('.preview-container');
        if (container) {
            container.remove();
        }
    }
}

function uploadFiles(event) {
    event.preventDefault();
    
    // Determine if we're adding or editing
    const isEditMode = window.location.pathname.includes('edit_log.php');
    const formId = isEditMode ? 'editLogForm' : 'addLogForm';
    const form = document.getElementById(formId);
    
    if (!form) {
        console.error(`Form with id '${formId}' not found`);
        return;
    }
    
    const formData = new FormData(form);
    
    // Show progress bar
    const progressBar = $('.progress-bar');
    const progressContainer = $('.progress');
    progressBar.width('0%').text('0%');
    progressContainer.show();
    
    // Clear any existing media[] entries
    for (const pair of formData.entries()) {
        if (pair[0] === 'media[]') {
            formData.delete(pair[0]);
        }
    }
    
    // Add files to formData
    selectedFiles.forEach(file => {
        formData.append('media[]', file);
    });
    
    // If in edit mode, add deleted media IDs
    if (isEditMode && deletedMediaIds.length > 0) {
        formData.append('deleted_media_ids', JSON.stringify(deletedMediaIds));
    }
    
    $.ajax({
        url: form.action,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = (evt.loaded / evt.total) * 100;
                    progressBar.width(percentComplete + '%');
                    progressBar.text(Math.round(percentComplete) + '%');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            try {
                // Try to parse the response as JSON
                let result;
                if (typeof response === 'object') {
                    result = response;
                } else {
                    result = JSON.parse(response);
                }
                
                if (result.success) {
                    // Use success toast notification
                    showSuccessToast(result.message);
                    
                    // Reset form after a short delay
                    setTimeout(function() {
                        // Redirect to logbook with appropriate parameters
                        if (isEditMode) {
                            // Get entry ID and entry date
                            const entryId = new URLSearchParams(window.location.search).get('id');
                            const entryDate = document.getElementById('date').value;
                            const entryMonth = new Date(entryDate).getMonth();
                            
                            // Redirect to logbook with highlight params
                            window.location.href = `logbook.php?highlight=${entryId}&month=${entryMonth}`;
                        } else {
                            // For new entries, we don't have the ID yet, so just go to logbook
                            // The backend should return the new entry ID in the response
                            if (result.entry_id) {
                                const entryDate = document.getElementById('date').value;
                                const entryMonth = new Date(entryDate).getMonth();
                                
                                window.location.href = `logbook.php?highlight=${result.entry_id}&month=${entryMonth}`;
                            } else {
                                window.location.href = 'logbook.php';
                            }
                        }
                    }, 2000);
                } else {
                    // Use error toast notification
                    showErrorToast(result.message || 'Unknown error occurred', 'Upload Failed', 'OK');
                }
            } catch (e) {
                console.error('Parse error:', e);
                console.error('Response:', response);
                
                // Show error toast for parsing errors
                showErrorToast('There was a problem processing your request!', 'Error', 'OK');
            }
            
            // Hide progress bar
            progressContainer.hide();
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', {xhr, status, error});
            
            // Show error toast for AJAX errors
            showErrorToast('Upload failed: ' + error, 'Connection Error', 'Try Again');
            
            // Hide progress bar
            progressContainer.hide();
        }
    });
}