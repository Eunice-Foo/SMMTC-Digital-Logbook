document.addEventListener('DOMContentLoaded', function() {
    initializeMonthFilter();
    generateVideoThumbnails();
    
    // Check for entry to highlight from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const highlightEntryId = urlParams.get('highlight');
    const monthToShow = urlParams.get('month');
    
    // If month is specified, activate that month tab
    if (monthToShow !== null) {
        const monthBtn = document.querySelector(`.month-btn[data-month="${monthToShow}"]`);
        if (monthBtn) {
            filterLogsByMonth(parseInt(monthToShow));
        }
    }
    
    // If entry ID is specified, scroll to and highlight it
    if (highlightEntryId) {
        highlightLogEntry(highlightEntryId);
    }
});

function updateDateHeaders() {
    const dateHeaders = document.querySelectorAll('.date-header');
    dateHeaders.forEach(header => {
        const nextEntry = header.nextElementSibling;
        header.style.display = nextEntry && nextEntry.style.display !== 'none' ? 'block' : 'none';
    });
}

function confirmDelete(entryId) {
    if (confirm('Are you sure you want to delete this entry?')) {
        fetch('delete_log.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'entry_id=' + entryId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error deleting entry: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting entry');
        });
    }
}

function viewLog(entryId) {
    // Instead of redirecting to view_log.php, show the entry in logbook
    const entryElement = document.querySelector(`.log-entry[data-entry-id="${entryId}"]`);
    if (entryElement) {
        // Get the month of this entry
        const entryDate = new Date(entryElement.dataset.date);
        const month = entryDate.getMonth();
        
        // Redirect to logbook with highlight and month parameters
        window.location.href = `logbook.php?highlight=${entryId}&month=${month}`;
    } else {
        // Fallback to traditional view if entry not found in current page
        window.location.href = `logbook.php`;
    }
}

// New function to highlight a specific log entry
function highlightLogEntry(entryId) {
    const entry = document.querySelector(`.log-entry[data-entry-id="${entryId}"]`);
    if (entry) {
        // Scroll to entry
        entry.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Add highlight animation class
        entry.classList.add('highlight-entry');
        
        // Remove class after animation completes
        setTimeout(() => {
            entry.classList.remove('highlight-entry');
        }, 3000); // 3 seconds
    }
}