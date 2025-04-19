function confirmDelete(entryId) {
    // Remove any existing modals first
    const existingModals = document.querySelectorAll('.delete-modal');
    existingModals.forEach(modal => modal.remove());
    
    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'delete-modal';
    modal.style.position = 'fixed';
    modal.style.zIndex = '2000';
    modal.innerHTML = `
        <div class="delete-modal-content">
            <div class="delete-modal-icon">
                <i class="fi fi-rr-trash"></i>
            </div>
            <h3>Are you sure you want to delete this log entry?</h3>
            <p>This action cannot be undone.</p>
            <div class="delete-modal-buttons">
                <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                <button class="delete-btn" onclick="proceedDelete(${entryId})">Delete</button>
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

function closeDeleteModal() {
    const modal = document.querySelector('.delete-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
        }, 300); // Match the transition duration in CSS
    }
}

function proceedDelete(entryId) {
    // Close the modal first
    closeDeleteModal();
    
    // Remove the warning toast code - no loading indicator needed
    
    // Proceed with deletion
    fetch('delete_log.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'entry_id=' + entryId
    })
    .then(response => response.json())
    .then(data => handleDeleteResponse(data))
    .catch(error => handleDeleteError(error));
}

function handleDeleteResponse(data) {
    if (data.success) {
        // Show success toast if available
        if (typeof showSuccessToast === 'function') {
            // Make sure the toast is initialized in the page
            const toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) {
                // Create toast container if it doesn't exist
                const container = document.createElement('div');
                container.id = 'toastContainer';
                container.style.display = 'block';
                document.body.appendChild(container);
            }
            
            showSuccessToast('Log entry deleted successfully!', 'Deleted');
            
            // Redirect after a short delay to allow toast to be seen
            setTimeout(() => {
                window.location.href = 'logbook.php';
            }, 2000); // Keep 2 seconds for better visibility
        } else {
            // Fallback if toast function isn't available
            alert('Log entry deleted successfully!');
            window.location.href = 'logbook.php';
        }
    } else {
        // Show error toast if available
        if (typeof showErrorToast === 'function') {
            showErrorToast(data.message || 'Failed to delete log entry', 'Error');
        } else {
            alert('Error deleting entry: ' + (data.message || 'Unknown error'));
        }
    }
}

function handleDeleteError(error) {
    console.error('Error:', error);
    
    // Show error toast if available
    if (typeof showErrorToast === 'function') {
        showErrorToast('Failed to connect to the server', 'Connection Error');
    } else {
        alert('Error deleting entry: Connection failed');
    }
}