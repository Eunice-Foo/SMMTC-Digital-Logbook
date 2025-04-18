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
    
    // Proceed with deletion
    fetch('delete_log.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'entry_id=' + entryId
    })
    .then(response => response.json())
    .then(handleDeleteResponse)
    .catch(handleDeleteError);
}

function handleDeleteResponse(data) {
    if (data.success) {
        // Show success toast if available
        if (typeof showSuccessToast === 'function') {
            showSuccessToast('Log entry deleted successfully!', 'Deleted');
            setTimeout(() => {
                window.location.href = 'logbook.php';
            }, 1500);
        } else {
            window.location.href = 'logbook.php';
        }
    } else {
        // Show error toast if available
        if (typeof showErrorToast === 'function') {
            showErrorToast(data.message || 'Failed to delete log entry', 'Error');
        } else {
            alert('Error deleting entry: ' + data.message);
        }
    }
}

function handleDeleteError(error) {
    console.error('Error:', error);
    if (typeof showErrorToast === 'function') {
        showErrorToast('Failed to connect to the server', 'Connection Error');
    } else {
        alert('Error deleting entry');
    }
}