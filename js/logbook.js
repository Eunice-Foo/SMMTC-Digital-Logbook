document.addEventListener('DOMContentLoaded', function() {
    initializeMonthFilter();
    generateVideoThumbnails();
    
    // Set initial view
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view') || 'timeline';
    switchView(view);
});

function updateCalendar(month, year) {
    const calendarContainer = document.getElementById('calendar-container');
    
    // Only proceed if calendar container exists
    if (!calendarContainer) {
        console.log('Calendar container not found - likely in timeline view');
        return;
    }

    // Show loading state
    calendarContainer.innerHTML = '<div class="loading">Loading calendar...</div>';
    
    fetch(`get_calendar.php?month=${month}&year=${year}`)
        .then(response => response.text())
        .then(html => {
            calendarContainer.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading calendar:', error);
            calendarContainer.innerHTML = '<div class="error">Error loading calendar</div>';
        });
}

// Helper function to get user ID from a hidden input or data attribute
function getUserId() {
    return document.body.dataset.userId;
}

function updateDateHeaders() {
    const dateHeaders = document.querySelectorAll('.date-header');
    dateHeaders.forEach(header => {
        const nextEntry = header.nextElementSibling;
        header.style.display = nextEntry && nextEntry.style.display !== 'none' ? 'block' : 'none';
    });
}

/*
function generateVideoThumbnails() {
    const videoElements = document.querySelectorAll('.video-thumbnail video');
    
    videoElements.forEach(video => {
        const canvas = video.nextElementSibling;
        const thumbnailContainer = video.closest('.thumbnail-container');
        
        video.addEventListener('loadedmetadata', function() {
            video.currentTime = 1;
        });

        video.addEventListener('seeked', function() {
            // Set canvas dimensions
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw the video frame on the canvas
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Create and append an image element using the canvas data
            const img = document.createElement('img');
            img.src = canvas.toDataURL();
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            thumbnailContainer.insertBefore(img, video);

            // Remove video and canvas elements
            video.remove();
            canvas.remove();
        });
    });
}
*/

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

function switchView(view) {
    const timelineView = document.querySelector('.timeline-view');
    const calendarView = document.querySelector('.calendar-view');
    const buttons = document.querySelectorAll('.view-btn');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    
    if (view === 'calendar') {
        timelineView.style.display = 'none';
        calendarView.style.display = 'block';
        document.querySelector('.view-btn:last-child').classList.add('active');
        
        // Get active month from month bar or current month
        const activeMonthBtn = document.querySelector('.month-btn.active');
        const monthIndex = activeMonthBtn ? 
            parseInt(activeMonthBtn.dataset.month) : 
            new Date().getMonth();
            
        // Update calendar for the selected month
        updateCalendar(monthIndex + 1, new Date().getFullYear());
    } else {
        timelineView.style.display = 'block';
        calendarView.style.display = 'none';
        document.querySelector('.view-btn:first-child').classList.add('active');
    }
}

function viewLog(entryId) {
    window.location.href = `view_log.php?id=${entryId}`;
}