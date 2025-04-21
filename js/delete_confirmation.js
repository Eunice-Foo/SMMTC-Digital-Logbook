function confirmDelete(itemId, itemType = 'log') {
    // Remove any existing modals first
    const existingModals = document.querySelectorAll('.delete-modal');
    existingModals.forEach(modal => modal.remove());
    
    // Set content based on item type
    const title = itemType === 'portfolio' 
        ? 'Are you sure you want to delete this portfolio item?' 
        : 'Are you sure you want to delete this log entry?';
    
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
            <h3>${title}</h3>
            <p>This action cannot be undone.</p>
            <div class="delete-modal-buttons">
                <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                <button class="delete-btn" onclick="proceedDelete(${itemId}, '${itemType}')">Delete</button>
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

function proceedDelete(itemId, itemType = 'log') {
    // Close the modal first
    closeDeleteModal();
    
    // Determine API endpoint and parameter name based on item type
    const endpoint = itemType === 'portfolio' ? 'delete_portfolio.php' : 'delete_log.php';
    const paramName = itemType === 'portfolio' ? 'portfolio_id' : 'entry_id';
    const redirectPage = itemType === 'portfolio' ? 'portfolio.php' : 'logbook.php';
    const successMessage = itemType === 'portfolio' 
        ? 'Portfolio deleted successfully!' 
        : 'Log entry deleted successfully!';
    
    // Proceed with deletion
    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `${paramName}=${itemId}`
    })
    .then(response => response.json())
    .then(data => handleDeleteResponse(data, redirectPage, successMessage))
    .catch(error => handleDeleteError(error));
}

function handleDeleteResponse(data, redirectPage, successMessage) {
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
            
            showSuccessToast(successMessage, 'Deleted');
            
            // Redirect after a short delay to allow toast to be seen
            setTimeout(() => {
                window.location.href = redirectPage;
            }, 2000); // Keep 2 seconds for better visibility
        } else {
            // Fallback if toast function isn't available
            alert(successMessage);
            window.location.href = redirectPage;
        }
    } else {
        // Show error toast if available
        if (typeof showErrorToast === 'function') {
            showErrorToast(data.message || 'Failed to delete item', 'Error');
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    }
}

function handleDeleteError(error) {
    console.error('Error:', error);
    
    // Show error toast if available
    if (typeof showErrorToast === 'function') {
        showErrorToast('Failed to connect to the server', 'Connection Error');
    } else {
        alert('Error: Connection failed');
    }
}