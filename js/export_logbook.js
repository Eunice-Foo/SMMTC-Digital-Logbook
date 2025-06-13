console.log('export_logbook.js loaded');

// Test function accessibility
window.testExportFunction = function() {
    console.log('Test function called');
    toggleExportMode();
};

let exportMode = false;
let exportInstructions = null;

function toggleExportMode() {
    console.log('toggleExportMode called');
    exportMode = !exportMode;
    console.log('exportMode:', exportMode);
    
    const exportControls = document.getElementById('exportControls');
    const regularButtons = document.getElementById('regularButtons');
    const actionButtons = document.querySelectorAll('.log-actions');
    
    // Create instructions element if it doesn't exist
    if (!exportInstructions) {
        exportInstructions = document.createElement('div');
        exportInstructions.className = 'export-instructions';
        exportInstructions.innerHTML = `
            <div class="instruction-icon"><i class="fi fi-rc-info"></i></div>
            <div class="instruction-text">Please select at least one log entry to export.</div>
        `;
    }
    
    console.log('Elements found:', {
        exportControls: !!exportControls,
        regularButtons: !!regularButtons,
        actionButtonsCount: actionButtons.length
    });

    if (!exportControls) {
        console.error('Export controls element not found');
        return;
    }
    
    if (exportMode) {
        // Add export-mode class to the body
        document.body.classList.add('export-mode');
        
        // Show export controls
        exportControls.style.display = 'flex';
        regularButtons.style.display = 'none';
        
        // Add instructions to the export-left div as first element
        const exportLeft = exportControls.querySelector('.export-left');
        if (exportLeft && !exportLeft.querySelector('.export-instructions')) {
            exportLeft.insertBefore(exportInstructions, exportLeft.firstChild);
            exportInstructions.style.display = 'flex';
        }
        
        console.log('Replacing action buttons with checkboxes');
        actionButtons.forEach(actions => {
            const entryId = actions.dataset.entryId;
            console.log('Processing entry ID:', entryId);
            
            const checkbox = document.createElement('div');
            checkbox.className = 'log-checkbox';
            checkbox.innerHTML = `
                <input type="checkbox" 
                       class="entry-checkbox" 
                       value="${entryId}"
                       onchange="updateExportCount(); updateSelectAllCheckbox()">
            `;
            actions.replaceWith(checkbox);
        });
    } else {
        // Remove export-mode class from the body
        document.body.classList.remove('export-mode');
        
        // Hide export controls
        exportControls.style.display = 'none';
        regularButtons.style.display = 'flex';
        
        // Remove instructions from current parent
        if (exportInstructions.parentNode) {
            exportInstructions.parentNode.removeChild(exportInstructions);
        }
        
        // Reset checkboxes and restore action buttons
        document.querySelectorAll('.entry-checkbox').forEach(cb => cb.checked = false);
        updateExportCount();
        
        // Replace checkboxes with action buttons
        document.querySelectorAll('.log-checkbox').forEach(checkbox => {
            const entryId = checkbox.querySelector('.entry-checkbox').value;
            const logEntry = checkbox.closest('.log-entry');
            const entryStatusElem = logEntry.querySelector('.log-status');
            const entryStatus = entryStatusElem ? entryStatusElem.textContent.trim() : '';

            // Create a container for log actions
            const actionButtons = document.createElement('div');
            actionButtons.className = 'log-actions';
            actionButtons.dataset.entryId = entryId;
            actionButtons.dataset.entryStatus = entryStatus;
            
            // Set display style based on entry status
            if (entryStatus === 'Signed') {
                actionButtons.classList.add('signed-entry-actions');
                actionButtons.style.display = 'none'; // Hide for signed entries
            } else {
                actionButtons.style.display = 'flex'; // Show for unsigned entries
            }
            
            // Debug the user role
            console.log('User role value:', document.body.dataset.userRole);
            
            // Add appropriate buttons based on user role and entry status
            if (entryStatus !== 'Signed') {
                // Get the role from the body data attribute or fallback to role in the DOM
                const userRole = document.querySelector('.logbook-wrapper')?.dataset.userRole || 
                                document.body.dataset.userRole;
                                
                console.log('Detected user role:', userRole);
                
                // Check using multiple conditions to ensure it works
                if (userRole == 1 || userRole === 'student' || userRole === 'ROLE_STUDENT') {
                    actionButtons.innerHTML = `
                        <button class="btn btn-edit" onclick="window.location.href='edit_log.php?id=${entryId}'">
                            Edit
                        </button>
                        <button class="btn btn-delete" onclick="confirmDelete(${entryId})">
                            Delete
                        </button>
                    `;
                } else {
                    actionButtons.innerHTML = `
                        <button class="btn btn-sign" onclick="directSign(${entryId})">
                            Sign
                        </button>
                        <button class="btn btn-remark" onclick="signLog(${entryId})">
                            Remark
                        </button>
                    `;
                }
            }
            
            // Replace the checkbox with the action buttons
            checkbox.replaceWith(actionButtons);
        });
    }
}

function updateExportCount() {
    const count = document.querySelectorAll('.entry-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('downloadButton').disabled = count === 0;
    
    // No need to modify the text since we already changed it in the HTML
}

function updateSelectAllCheckbox() {
    const selectAllCheckbox = document.querySelector('.select-all input[type="checkbox"]');
    if (selectAllCheckbox) {
        const visibleEntries = document.querySelectorAll('.log-entry[style*="flex"] .entry-checkbox');
        const checkedEntries = Array.from(visibleEntries).filter(checkbox => checkbox.checked);
        
        // Set checkbox state based on all visible entries being checked
        selectAllCheckbox.checked = visibleEntries.length > 0 && checkedEntries.length === visibleEntries.length;
    }
}

function filterLogsByMonth(monthIndex) {
    // Remove active class from all buttons
    document.querySelectorAll('.month-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Add active class to selected month
    document.querySelector(`[data-month="${monthIndex}"]`).classList.add('active');

    // Handle timeline view
    const logEntries = document.querySelectorAll('.log-entry');
    logEntries.forEach(entry => {
        const entryDate = new Date(entry.dataset.date);
        if (entryDate.getMonth() === monthIndex) {
            entry.style.display = 'flex';
        } else {
            entry.style.display = 'none';
        }
    });

    // Update select all checkbox state based on visible entries
    if (document.getElementById('exportControls').style.display === 'flex') {
        updateSelectAllCheckbox();
    }
    
    // Removed calendar-related code here
}

function selectAllEntries(checked) {
    const visibleEntries = document.querySelectorAll('.log-entry[style*="flex"] .entry-checkbox');
    visibleEntries.forEach(checkbox => {
        checkbox.checked = checked;
    });
    updateExportCount();
    updateSelectAllCheckbox(); // Add this line
}

function downloadExport() {
    const selectedEntries = Array.from(document.querySelectorAll('.entry-checkbox:checked'))
        .map(cb => cb.value);
    
    // Show confirmation modal before opening new tab
    confirmExportPreview(selectedEntries);
}

function confirmExportPreview(selectedEntries) {
    // Remove any existing modals first
    const existingModals = document.querySelectorAll('.export-modal');
    existingModals.forEach(modal => modal.remove());
    
    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'export-modal';
    modal.style.position = 'fixed';
    modal.style.zIndex = '2000';
    modal.innerHTML = `
        <div class="export-modal-content">
            <div class="export-modal-icon">
                <i class="fi fi-rr-browsers"></i>
            </div>
            <h3>You will be redirected to a new tab for export preview.</h3>
            <p>Do you wish to continue?</p>
            <div class="export-modal-buttons">
                <button class="cancel-btn" onclick="closeExportModal()">Cancel</button>
                <button class="continue-btn" id="proceedExportBtn">Continue</button>
            </div>
        </div>
    `;
    
    // Make sure it's added directly to the body
    document.body.appendChild(modal);
    
    // Add event listener to continue button AFTER adding to DOM
    document.getElementById('proceedExportBtn').addEventListener('click', function() {
        proceedWithExport(selectedEntries);
    });
    
    // Play alert sound for notification
    playExportModalSound();
    
    // Force reflow to ensure proper CSS application
    void modal.offsetWidth;
    
    // Add fade-in effect
    setTimeout(() => {
        modal.classList.add('active');
    }, 10);
}

function playExportModalSound() {
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
            console.log('Export modal sound prevented by browser:', e);
            // This is normal on some browsers that require user interaction first
        });
    } catch (e) {
        console.error('Error playing export modal sound:', e);
    }
}

function closeExportModal() {
    const modal = document.querySelector('.export-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
        }, 300); // Match transition duration
    }
}

function proceedWithExport(selectedEntries) {
    // Close the modal
    closeExportModal();
    
    console.log('Proceeding with export for entries:', selectedEntries);
    
    // Create the form for submission - this should be directly connected to the click event
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_preview.php';
    form.target = '_blank'; // Open in new tab without pre-creating a window
    
    // Add entry IDs
    const entriesInput = document.createElement('input');
    entriesInput.type = 'hidden';
    entriesInput.name = 'entries';
    entriesInput.value = JSON.stringify(selectedEntries);
    form.appendChild(entriesInput);
    
    // Add media inclusion parameter
    const mediaInput = document.createElement('input');
    mediaInput.type = 'hidden';
    mediaInput.name = 'includeMedia';
    mediaInput.value = 'true';
    form.appendChild(mediaInput);
    
    // Add form to document
    document.body.appendChild(form);
    
    // Submit the form directly - this should be triggered by the user click
    try {
        console.log('Submitting export form...');
        form.submit();
        console.log('Form submitted successfully');
        
        // Clean up and exit export mode after a short delay
        setTimeout(() => {
            document.body.removeChild(form);
            toggleExportMode();
        }, 1000);
    } catch (e) {
        console.error('Error submitting form:', e);
        alert('There was a problem opening the export preview. Please try again.');
    }
}