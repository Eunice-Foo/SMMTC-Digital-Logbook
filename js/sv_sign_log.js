function directSign(entryId) {
    fetch('sv_sign_log.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `entry_id=${entryId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success toast notification
            if (typeof showSuccessToast === 'function') {
                showSuccessToast('Log entry has been successfully signed', 'Signed!');
                // Reload after a short delay to allow the user to see the notification
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                // Fallback if toast function is not available
                alert('Log entry signed successfully!');
                window.location.reload();
            }
        } else {
            // Show error toast notification
            if (typeof showErrorToast === 'function') {
                showErrorToast(data.message || 'Unknown error occurred', 'Signing Failed');
            } else {
                alert('Error signing log: ' + data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error toast notification
        if (typeof showErrorToast === 'function') {
            showErrorToast('Failed to communicate with server', 'Connection Error');
        } else {
            alert('Error signing log');
        }
    });
}

function signLog(entryId) {
    // Create modal overlay for remarks
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <h3>Add Remarks</h3>
            <div class="form-group">
                <textarea 
                    id="remarks" 
                    placeholder="Enter your remarks here..."
                    maxlength="1000"
                ></textarea>
            </div>
            <div class="modal-buttons">
                <button onclick="closeModal()" class="btn btn-cancel">Cancel</button>
                <button onclick="submitSignature(${entryId})" class="btn btn-sign">
                    <i class="fi fi-rr-attribution-pen"></i> Sign with Remarks
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeModal() {
    const modal = document.querySelector('.modal');
    if (modal) {
        modal.remove();
    }
}

function submitSignature(entryId) {
    const remarks = document.getElementById('remarks').value;
    
    fetch('sv_sign_log.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `entry_id=${entryId}&remarks=${encodeURIComponent(remarks)}`
    })
    .then(response => response.json())
    .then(data => {
        closeModal(); // Close the remarks modal
        
        if (data.success) {
            // Show success toast notification
            if (typeof showSuccessToast === 'function') {
                showSuccessToast('Log entry signed with remarks', 'Signed!');
                // Reload after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                // Fallback if toast function is not available
                alert('Log entry signed successfully!');
                window.location.reload();
            }
        } else {
            // Show error toast notification
            if (typeof showErrorToast === 'function') {
                showErrorToast(data.message || 'Unknown error occurred', 'Signing Failed');
            } else {
                alert('Error signing log: ' + data.message);
            }
        }
    })
    .catch(error => {
        closeModal(); // Make sure to close the modal even on error
        console.error('Error:', error);
        
        // Show error toast notification
        if (typeof showErrorToast === 'function') {
            showErrorToast('Failed to communicate with server', 'Connection Error');
        } else {
            alert('Error signing log');
        }
    });
}