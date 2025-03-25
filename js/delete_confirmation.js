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
        .then(handleDeleteResponse)
        .catch(handleDeleteError);
    }
}

function handleDeleteResponse(data) {
    if (data.success) {
        window.location.href = 'logbook.php';
    } else {
        alert('Error deleting entry: ' + data.message);
    }
}

function handleDeleteError(error) {
    console.error('Error:', error);
    alert('Error deleting entry');
}