/**
 * Initialize category filtering functionality
 * @param {string} itemSelector - CSS selector for portfolio items
 * @param {string} categoryAttr - Data attribute name containing category information
 */
function initCategoryFilter(itemSelector = '.portfolio-card', categoryAttr = 'data-category') {
    const categoryTabs = document.querySelectorAll('.category-tab');
    
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            // Only prevent default if it's a button (not a link)
            if (tab.tagName === 'BUTTON') {
                e.preventDefault();
            }
            
            const selectedCategory = tab.getAttribute('data-category');
            
            // For buttons (client-side filtering)
            if (tab.tagName === 'BUTTON') {
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
            }
            // Links will navigate normally
        });
    });
}