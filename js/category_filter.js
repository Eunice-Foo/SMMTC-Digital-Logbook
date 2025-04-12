/**
 * Initialize category filtering functionality
 * @param {string} itemSelector - CSS selector for portfolio items
 * @param {string} categoryAttr - Data attribute name containing category information
 */
function initCategoryFilter(itemSelector = '.portfolio-item', categoryAttr = 'data-category') {
    document.addEventListener('DOMContentLoaded', function() {
        const categoryTabs = document.querySelectorAll('.category-tab');
        const portfolioItems = document.querySelectorAll(itemSelector);
        
        categoryTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Update active state for tabs
                categoryTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                const selectedCategory = tab.dataset.category;
                
                // Filter items
                portfolioItems.forEach(item => {
                    if (selectedCategory === 'all' || item.getAttribute(categoryAttr) === selectedCategory) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    });
}