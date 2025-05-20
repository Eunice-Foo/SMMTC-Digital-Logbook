// Add styles for cancel confirmation
(function() {
    const style = document.createElement('style');
    style.innerHTML = `
        /* Additional styles for cancel confirmation */
        .warning-icon {
            background-color: #ff9800; /* Warning color */
        }

        .warning-icon i {
            color: white;
        }
    `;
    document.head.appendChild(style);
})();

/**
 * Cancel Confirmation
 * Shows a confirmation dialog when a user attempts to cancel a form
 * with unsaved changes
 */

function confirmCancel(returnUrl = 'logbook.php') {
    // Remove any existing modals first
    const existingModals = document.querySelectorAll('.cancel-modal, .delete-modal');
    existingModals.forEach(modal => modal.remove());
    
    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'delete-modal cancel-modal'; // Use existing delete-modal class + our new class
    modal.innerHTML = `
        <div class="delete-modal-content">
            <div class="delete-modal-icon warning-icon">
                <i class="fi fi-rr-exclamation"></i>
            </div>
            <h3>Are you sure you want to cancel?</h3>
            <p>All your changes will be lost.</p>
            <div class="delete-modal-buttons">
                <button class="keep-btn" onclick="closeCancelModal()">Keep Editing</button>
                <button class="discard-btn" onclick="discardEntry('${returnUrl}')">Discard Entry</button>
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
    
    // Play warning sound if available
    if (typeof playSound === 'function' && typeof sounds !== 'undefined' && sounds.warning) {
        playSound('warning');
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