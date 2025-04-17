document.addEventListener('DOMContentLoaded', function() {
    initializeMonthFilter();
    generateVideoThumbnails();
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
    window.location.href = `view_log.php?id=${entryId}`;
}