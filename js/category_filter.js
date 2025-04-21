/**
 * Initialize category filtering functionality
 * @param {string} itemSelector - CSS selector for portfolio items
 * @param {string} categoryAttr - Data attribute name containing category information
 */
function initCategoryFilter(itemSelector = '.portfolio-card', categoryAttr = 'data-category') {
    const categoryTabs = document.querySelectorAll('.category-tab');
    
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            
            const selectedCategory = tab.getAttribute('data-category');
            
            // If we're in main_menu.php, navigate to the filtered URL
            if (window.location.pathname.includes('main_menu.php')) {
                window.location.href = 'main_menu.php?category=' + selectedCategory;
                return;
            }
            
            // For other pages (like portfolio.php), do client-side filtering
            const portfolioItems = document.querySelectorAll(itemSelector);
            
            // Update active state for tabs
            categoryTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            // Filter items
            portfolioItems.forEach(item => {
                if (selectedCategory === 'all' || item.getAttribute(categoryAttr) === selectedCategory) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}