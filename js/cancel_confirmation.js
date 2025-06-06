/**
 * Cancel Confirmation
 * Shows a confirmation dialog when a user attempts to cancel a form 
 * with unsaved changes
 */

function confirmCancel(returnUrl = 'logbook.php') {
    // Remove any existing modals first
    const existingModals = document.querySelectorAll('.cancel-modal');
    existingModals.forEach(modal => modal.remove());
    
    // Play warning sound when modal appears
    playCancelWarningSound();
    
    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'cancel-modal';
    modal.innerHTML = `
        <div class="cancel-modal-content">
            <div class="cancel-modal-icon">
                <i class="fi fi-rr-exclamation"></i>
            </div>
            <h3>Are you sure you want to cancel?</h3>
            <p>All your changes will be lost.</p>
            <div class="cancel-modal-buttons">
                <button class="keep-btn" onclick="closeCancelModal()">Keep Editing</button>
                <button class="discard-btn" onclick="discardEntry('${returnUrl}')">Discard</button>
            </div>
        </div>
    `;
    
    // Make sure it's added directly to the body
    document.body.appendChild(modal);
    
    // Force reflow to ensure proper CSS application
    void modal.offsetWidth;
    
    // Add fade-in effect
    setTimeout(() => {
        modal.classList.add('active');
    }, 10);
}

/**
 * Plays a warning sound when the cancel confirmation modal appears
 * Integrates with the existing toast notification sound system if available
 */
function playCancelWarningSound() {
    // Check if toast notification sound system is available
    if (typeof playSound === 'function' && typeof sounds !== 'undefined' && sounds.warning) {
        // Use existing toast sound system
        playSound('warning');
        return;
    }
    
    // Fallback: Use our own implementation
    try {
        // Try to create audio element
        const warningSound = new Audio('audio/notifications/warning.mp3');
        warningSound.volume = 0.5; // Set volume to 50%
        
        // Play the sound (wrapped in try-catch to handle browser autoplay restrictions)
        warningSound.play().catch(e => {
            console.log('Cancel warning sound prevented by browser:', e);
            // This is normal on some browsers that require user interaction first
        });
    } catch (e) {
        console.error('Error playing cancel warning sound:', e);
    }
}

function closeCancelModal() {
    const modal = document.querySelector('.cancel-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
        }, 300); // Match the transition duration in CSS
    }
}

function discardEntry(returnUrl) {
    // Close the modal
    closeCancelModal();
    
    // Navigate back to the return URL
    window.location.href = returnUrl;
}

/**
 * More robust function to find cancel buttons
 */
function setupCancelConfirmations(defaultReturnUrl = 'logbook.php') {
    // Better selector that includes buttons/links with text containing "Cancel"
    const cancelButtons = document.querySelectorAll('.cancel-btn, .btn.cancel-btn, button:contains("Cancel"), a:contains("Cancel")');
    
    cancelButtons.forEach(button => {
        // Check if this button already has a cancel handler
        if (!button.getAttribute('data-cancel-handler')) {
            button.setAttribute('data-cancel-handler', 'true');
            
            // Try to get return URL from data attribute, href, or use default
            let returnUrl = button.dataset.returnUrl;
            
            if (!returnUrl && button.hasAttribute('href') && button.getAttribute('href') !== '#' && button.getAttribute('href') !== 'javascript:void(0)') {
                returnUrl = button.getAttribute('href');
            }
            
            returnUrl = returnUrl || defaultReturnUrl;
            
            button.addEventListener('click', function(event) {
                event.preventDefault();
                confirmCancel(returnUrl);
            });
        }
    });
}

// Auto-setup all cancel confirmations when the page loads
document.addEventListener('DOMContentLoaded', function() {
    setupCancelConfirmations();
});