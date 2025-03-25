document.addEventListener('DOMContentLoaded', function() {
    initializeMonthFilter();
});

function initializeMonthFilter() {
    const monthBar = document.querySelector('.month-bar');
    if (!monthBar) return;

    const currentMonth = new Date().getMonth();
    let activeMonth = currentMonth;
    
    // Find if current month is in practicum months
    const monthBtns = document.querySelectorAll('.month-btn');
    const monthExists = Array.from(monthBtns).some(btn => 
        parseInt(btn.dataset.month) === currentMonth
    );
    
    // If current month is not in practicum period, select first month
    if (!monthExists && monthBtns.length > 0) {
        activeMonth = parseInt(monthBtns[0].dataset.month);
    }
    
    filterLogsByMonth(activeMonth);
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

    // Reset select all checkbox if in export mode
    if (typeof resetSelectAllCheckbox === 'function' && 
        document.getElementById('exportControls').style.display === 'flex') {
        resetSelectAllCheckbox();
    }

    // Handle calendar view if present
    if (document.querySelector('.calendar-view').style.display !== 'none') {
        updateCalendar(monthIndex + 1, new Date().getFullYear());
    }
    
    // Update date headers if function exists
    if (typeof updateDateHeaders === 'function') {
        updateDateHeaders();
    }
}