document.addEventListener('DOMContentLoaded', function() {
    // Initialize profile page visibility
    const profileToggle = document.getElementById('includeProfile');
    if (profileToggle) {
        toggleProfilePage(); // Set initial state
    }
});

window.toggleProfilePage = function() {
    const profileToggle = document.getElementById('includeProfile');
    const profilePage = document.querySelector('.preview-container.profile-page');
    
    if (profilePage) {
        // Toggle visibility
        profilePage.style.display = profileToggle.checked ? 'block' : 'none';
        profilePage.classList.toggle('show', profileToggle.checked);
        
        console.log('Profile page visibility:', profileToggle.checked);
        
        // Trigger page reorganization after toggling profile
        if (typeof window.checkAllPagesOverflow === 'function') {
            setTimeout(window.checkAllPagesOverflow, 0);
        }
    }
};