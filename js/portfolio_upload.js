// Global variables
let selectedFiles = [];

document.addEventListener('DOMContentLoaded', function() {
    // Initialize video thumbnails for uploaded videos
    if (typeof generateVideoThumbnails === 'function') {
        generateVideoThumbnails();
    }
    
    // Initialize tools input
    initializeToolsInput();
});

function showSelectedFiles(input) {
    const previewArea = document.getElementById('previewArea');
    const files = Array.from(input.files);
    
    files.forEach(file => {
        selectedFiles.push(file);
        const previewContainer = createPreviewContainer(file);
        previewArea.appendChild(previewContainer);
    });

    // Clear the file input
    input.value = '';
}

function createPreviewContainer(file) {
    const container = document.createElement('div');
    container.className = 'preview-container';
    container.dataset.fileName = file.name;
    
    const fileInfo = createFileInfo(file);
    container.appendChild(fileInfo);
    
    if (file.type.startsWith('image/')) {
        createImagePreview(file, container);
    } else if (file.type.startsWith('video/')) {
        createVideoPreview(file, container);
    }
    
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

function uploadFiles(event) {
    event.preventDefault();
    
    // Validate form
    const form = document.getElementById('addPortfolioForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Validate that files are selected
    if (selectedFiles.length === 0) {
        if (typeof showErrorToast === 'function') {
            showErrorToast('Please select at least one media file to upload', 'No Files Selected');
        } else {
            alert('Please select at least one media file to upload');
        }
        return;
    }

    // Show loading overlay
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.classList.add('active');
    }
    
    // Add client-side image resizing for better upload performance
    const optimizedFormData = new FormData(form);
    const imageOptimizationPromises = [];
    
    // Clear existing media[] entries
    optimizedFormData.delete('media[]');
    
    // Optimize images before upload
    for (const file of selectedFiles) {
        if (file.type.startsWith('image/') && file.size > 1024 * 1024) {
            // Only optimize images larger than 1MB
            imageOptimizationPromises.push(
                resizeImageFile(file).then(optimizedFile => {
                    optimizedFormData.append('media[]', optimizedFile);
                })
            );
        } else {
            // Don't optimize videos or small images
            optimizedFormData.append('media[]', file);
        }
    }
    
    // Wait for all image optimizations to complete
    Promise.all(imageOptimizationPromises).then(() => {
        // Use the optimized form data for upload
        performUpload(optimizedFormData);
    });
}

// Add this new function for client-side image resizing
function resizeImageFile(file) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                // Calculate new dimensions (max 1920px width/height)
                let width = img.width;
                let height = img.height;
                const maxDimension = 1920;
                
                if (width > height && width > maxDimension) {
                    height = Math.round(height * (maxDimension / width));
                    width = maxDimension;
                } else if (height > maxDimension) {
                    width = Math.round(width * (maxDimension / height));
                    height = maxDimension;
                }
                
                // Resize image
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                // Convert to blob with good quality (0.85)
                canvas.toBlob((blob) => {
                    const optimizedFile = new File([blob], file.name, {
                        type: file.type,
                        lastModified: file.lastModified
                    });
                    resolve(optimizedFile);
                }, file.type, 0.85);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

// Move the actual upload code to a separate function
function performUpload(formData) {
    const progressBar = $('.progress-bar');
    const progressContainer = $('.progress');
    progressBar.width('0%').text('0%');
    progressContainer.show();
    
    $.ajax({
        url: document.getElementById('addPortfolioForm').action,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 300000, // 5-minute timeout for large uploads
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
            // Log raw response from server
            console.log('Raw response from server:', response);
            
            // Hide loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.classList.remove('active');
            }
            
            try {
                // Parse response if it's a string
                let result;
                if (typeof response === 'object') {
                    result = response;
                } else {
                    // Add proper error handling for parsing
                    try {
                        result = JSON.parse(response);
                    } catch(parseError) {
                        console.error('Failed to parse server response:', response);
                        throw new Error('Invalid JSON response from server');
                    }
                }
                
                if (result.success) {
                    // Show success toast
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast(result.message || 'Portfolio item added successfully!', 'Success');
                    } else {
                        alert(result.message || 'Portfolio item added successfully!');
                    }
                    
                    // Reset the form and preview area
                    document.getElementById('addPortfolioForm').reset();
                    selectedFiles = [];
                    document.getElementById('previewArea').innerHTML = '';
                    document.getElementById('selectedTools').innerHTML = '';
                    
                    // Redirect to portfolio page after delay
                    setTimeout(() => {
                        window.location.href = 'portfolio.php';
                    }, 2000);
                } else {
                    // Show error toast
                    if (typeof showErrorToast === 'function') {
                        showErrorToast(result.message || 'Unknown error occurred', 'Upload Failed');
                    } else {
                        alert(result.message || 'Unknown error occurred');
                    }
                }
            } catch (e) {
                console.error('Error processing response:', e);
                console.error('Raw response:', response);
                if (typeof showErrorToast === 'function') {
                    showErrorToast('There was a problem processing the server response', 'Error');
                } else {
                    alert('There was a problem processing the server response');
                }
            }
            
            // Hide progress bar
            progressContainer.hide();
        },
        error: function(xhr, status, error) {
            // Hide loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.classList.remove('active');
            }
            
            // Show specific error message based on HTTP status
            let errorMessage = 'Upload failed';
            if (xhr.status === 413) {
                errorMessage = 'File too large. Please reduce file size and try again.';
            } else if (xhr.status === 0) {
                errorMessage = 'Connection lost or timed out. Please try again.';
            } else {
                errorMessage = `${error} (Status: ${xhr.status})`;
            }
            
            // Show error toast notification
            if (typeof showErrorToast === 'function') {
                showErrorToast(errorMessage, 'Connection Error');
            } else {
                alert('Error: ' + errorMessage);
            }
            
            // Hide progress bar
            progressContainer.hide();
        }
    });
}