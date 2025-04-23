document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    document.getElementById('confirm_password')?.addEventListener('input', function() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;
        
        if (newPassword !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Validate practicum dates
    document.getElementById('practicum_end_date')?.addEventListener('input', function() {
        const startDate = document.getElementById('practicum_start_date').value;
        const endDate = this.value;
        
        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
            this.setCustomValidity('End date must be after start date');
        } else {
            this.setCustomValidity('');
        }
    });

    // Initialize profile picture preview
    initProfilePicturePreview();
    
    // Initialize signature preview
    initSignaturePreview();
});

/**
 * Initialize profile picture preview functionality
 */
function initProfilePicturePreview() {
    const profileInput = document.getElementById('profile_picture');
    const previewContainer = document.querySelector('.current-profile-picture');
    
    if (profileInput && previewContainer) {
        profileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const placeholder = previewContainer.querySelector('.profile-placeholder');
                    if (placeholder) {
                        previewContainer.removeChild(placeholder);
                    }
                    
                    const existingImg = previewContainer.querySelector('img');
                    if (existingImg) {
                        previewContainer.removeChild(existingImg);
                    }
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Profile preview';
                    previewContainer.appendChild(img);
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
}

/**
 * Initialize signature preview functionality
 */
function initSignaturePreview() {
    const signatureInput = document.getElementById('signature_image');
    const previewContainer = document.querySelector('.current-signature');
    
    if (signatureInput && previewContainer) {
        signatureInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const placeholder = previewContainer.querySelector('.signature-placeholder');
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    
                    const existingImg = previewContainer.querySelector('img');
                    if (existingImg) {
                        // Update the existing image instead of removing it
                        existingImg.src = e.target.result;
                    } else {
                        // Create a new image if none exists
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Signature preview';
                        img.style.maxWidth = '100%';
                        img.style.maxHeight = '100%';
                        previewContainer.appendChild(img);
                    }
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
}