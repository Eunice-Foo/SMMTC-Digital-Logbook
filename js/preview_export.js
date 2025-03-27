document.addEventListener('DOMContentLoaded', function() {
    initializePages();
    setTimeout(checkContentOverflow, 0);
});

function initializePages() {
    const mainContainer = document.querySelector('.preview-container');
    const logEntries = Array.from(document.querySelectorAll('.log-entry'));
    const header = document.querySelector('.document-header');
    
    // Create preview section if it doesn't exist
    let previewSection = document.querySelector('.preview-section');
    if (!previewSection) {
        previewSection = document.createElement('div');
        previewSection.className = 'preview-section';
        mainContainer.parentNode.appendChild(previewSection);
    }
    
    // Clear existing content
    previewSection.innerHTML = '';
    
    // Create first page
    let currentPage = createNewPage();
    previewSection.appendChild(currentPage);
    
    // Add header to first page only
    if (header) {
        currentPage.appendChild(header.cloneNode(true));
    }

    // Calculate available height for content (A4 height minus margins and header)
    const headerHeight = header ? header.offsetHeight : 0;
    const pageHeight = 297 * 3.78; // A4 height in pixels
    const marginHeight = 96 * 2; // 2 inches (top and bottom margins)
    const availableHeight = pageHeight - marginHeight - headerHeight;

    logEntries.forEach((entry, index) => {
        console.log(`Processing entry ${index + 1}`);
        const entryRect = entry.getBoundingClientRect();
        const currentContentHeight = Array.from(currentPage.children)
            .reduce((total, child) => total + child.offsetHeight, 0);

        // Check if entry needs to go to next page
        if (currentContentHeight + entryRect.height > availableHeight) {
            console.log('Creating new page for entry');
            currentPage = createNewPage();
            previewSection.appendChild(currentPage);
        }

        currentPage.appendChild(entry.cloneNode(true));
    });

    // Remove original container
    mainContainer.remove();
}

function createNewPage() {
    console.log('Creating new A4 page');
    const page = document.createElement('div');
    page.className = 'preview-container';
    return page;
}

function checkContentOverflow() {
    const containers = document.querySelectorAll('.preview-container');
    
    containers.forEach(container => {
        const contentHeight = Array.from(container.children)
            .reduce((total, child) => total + child.offsetHeight, 0);
        const availableHeight = container.offsetHeight - (96 * 2); // Subtract 2 inches (96px each)
        
        console.log('Content height:', contentHeight);
        console.log('Available height:', availableHeight);
        
        if (contentHeight > availableHeight) {
            const lastEntry = container.querySelector('.log-entry:last-child');
            if (lastEntry) {
                const newContainer = createNewPage();
                container.parentNode.insertBefore(newContainer, container.nextSibling);
                newContainer.appendChild(lastEntry);
            }
        }
    });
}