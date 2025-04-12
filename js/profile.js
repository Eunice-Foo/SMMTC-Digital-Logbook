document.addEventListener('DOMContentLoaded', function() {
    // Add any profile page animations or interactions here
    
    // Example: Add a visual effect when hovering over profile sections
    const profileSections = document.querySelectorAll('.profile-section');
    profileSections.forEach(section => {
        section.addEventListener('mouseenter', () => {
            section.style.backgroundColor = '#f9f9f9';
        });
        section.addEventListener('mouseleave', () => {
            section.style.backgroundColor = '';
        });
    });
});