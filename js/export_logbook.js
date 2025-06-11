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
            const actionButtons = document.createElement('div');
            actionButtons.className = 'log-actions';
            actionButtons.dataset.entryId = entryId;
            
            actionButtons.innerHTML = `
                <button class="btn btn-view" onclick="window.location.href='view_log.php?id=${entryId}'">
                    View
                </button>
                ${!checkbox.closest('.log-entry').querySelector('.log-status.reviewed') ? 
                    `<button class="btn btn-edit" onclick="window.location.href='edit_log.php?id=${entryId}'">
                        Edit
                    </button>` : ''
                }
                <button class="btn btn-delete" onclick="confirmDelete(${entryId})">
                    Delete
                </button>
            `;
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

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_preview.php';
    form.target = '_blank';

    const entriesInput = document.createElement('input');
    entriesInput.type = 'hidden';
    entriesInput.name = 'entries';
    entriesInput.value = JSON.stringify(selectedEntries);
    form.appendChild(entriesInput);

    const mediaInput = document.createElement('input');
    mediaInput.type = 'hidden';
    mediaInput.name = 'includeMedia';
    mediaInput.value = true;
    form.appendChild(mediaInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    // Exit export mode after submitting
    toggleExportMode();
}

function resetSelectAllCheckbox() {
    const selectAllCheckbox = document.querySelector('.select-all input[type="checkbox"]');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
    }
}