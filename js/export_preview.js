document.addEventListener('DOMContentLoaded', function() {
    initializePages();
    setTimeout(checkContentOverflow, 0);
});

// Make checkAllPagesOverflow globally available
window.checkAllPagesOverflow = function() {
    // Skip profile page when checking overflow
    const pages = Array.from(document.querySelectorAll('.preview-container:not(.profile-page)'));
    const signatureHeight = 120;
    const topMargin = 96;
    const bottomMargin = 96 + signatureHeight;
    
    handleUnderflow(pages, topMargin, bottomMargin);
    handleOverflow(pages, topMargin, bottomMargin);
    cleanupEmptyPages(pages);
};

function handleUnderflow(pages, topMargin, bottomMargin) {
    for (let i = pages.length - 1; i > 0; i--) {
        const currentPage = pages[i];
        const previousPage = pages[i - 1];
        const entries = currentPage.querySelectorAll('.log-entry');
        
        Array.from(entries).forEach(entry => {
            if (canFitInPreviousPage(entry, previousPage, topMargin, bottomMargin)) {
                previousPage.appendChild(entry);
            }
        });
    }
}

function handleOverflow(pages, topMargin, bottomMargin) {
    pages.forEach((page, index) => {
        const entries = page.querySelectorAll('.log-entry');
        
        entries.forEach(entry => {
            if (isOverflowing(entry, page, topMargin, bottomMargin)) {
                moveToNextPage(entry, page, pages, index);
            }
        });
    });
}

function canFitInPreviousPage(entry, previousPage, topMargin, bottomMargin) {
    const safeAreaHeight = previousPage.offsetHeight - (topMargin + bottomMargin);
    const currentContentHeight = Array.from(previousPage.children)
        .reduce((total, child) => total + child.offsetHeight, 0);
    return currentContentHeight + entry.offsetHeight <= safeAreaHeight;
}

function isOverflowing(entry, page, topMargin, bottomMargin) {
    const safeAreaHeight = page.offsetHeight - (topMargin + bottomMargin);
    const entryBottom = entry.offsetTop + entry.offsetHeight;
    return entryBottom > safeAreaHeight;
}

function moveToNextPage(entry, currentPage, pages, currentIndex) {
    let nextPage = pages[currentIndex + 1];
    
    if (!nextPage) {
        nextPage = createNewPage();
        currentPage.parentNode.insertBefore(nextPage, currentPage.nextSibling);
        pages.push(nextPage);
    }
    
    nextPage.insertBefore(entry, nextPage.firstChild);
}

function cleanupEmptyPages(pages) {
    pages.forEach(page => {
        if (!page.querySelector('.log-entry')) {
            page.remove();
        }
    });
}

function initializePages() {
    // Get all containers except profile page
    const mainContainer = document.querySelector('.preview-container:not(.profile-page)');
    const logEntries = Array.from(mainContainer.querySelectorAll('.log-entry'));
    const header = document.querySelector('.document-header');
    const signature = document.querySelector('.signature-section');
    
    // Create preview section if it doesn't exist
    let previewSection = document.querySelector('.preview-section');
    if (!previewSection) {
        previewSection = document.createElement('div');
        previewSection.className = 'preview-section';
        mainContainer.parentNode.insertBefore(previewSection, mainContainer);
    }
    
    // Move profile page to preview section first
    const profilePage = document.querySelector('.preview-container.profile-page');
    if (profilePage) {
        previewSection.appendChild(profilePage);
    }
    
    // Clear remaining content
    previewSection.querySelectorAll('.preview-container:not(.profile-page)').forEach(container => container.remove());
    
    // Create first page for log entries
    let currentPage = createNewPage();
    previewSection.appendChild(currentPage);
    
    // Add header to first log entry page
    if (header) {
        currentPage.appendChild(header.cloneNode(true));
    }

    // Calculate available height
    const headerHeight = header ? header.offsetHeight : 0;
    const pageHeight = 297 * 3.78; // A4 height in pixels
    const signatureHeight = 120; // Height for signature block
    const marginHeight = 96 * 2; // 2 inches margins
    const availableHeight = pageHeight - marginHeight - headerHeight - signatureHeight;

    // Process log entries
    logEntries.forEach((entry, index) => {
        const entryHeight = entry.offsetHeight;
        const currentContentHeight = Array.from(currentPage.children)
            .reduce((total, child) => total + child.offsetHeight, 0);

        if (currentContentHeight + entryHeight > availableHeight) {
            if (signature) {
                currentPage.appendChild(signature.cloneNode(true));
            }
            currentPage = createNewPage();
            previewSection.appendChild(currentPage);
        }

        currentPage.appendChild(entry.cloneNode(true));
    });

    // Add signature to last page
    if (signature) {
        currentPage.appendChild(signature.cloneNode(true));
    }

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
    const signatureHeight = 120; // Increased height for signature block
    
    containers.forEach(container => {
        const contentHeight = Array.from(container.children)
            .reduce((total, child) => total + child.offsetHeight, 0);
        const availableHeight = container.offsetHeight - (96 * 3) - signatureHeight; // Adjusted margin
        
        console.log('Content height:', contentHeight);
        console.log('Available height:', availableHeight);
        
        if (contentHeight > availableHeight) {
            const lastEntry = container.querySelector('.log-entry:last-child');
            if (lastEntry) {
                const newContainer = createNewPage();
                container.parentNode.insertBefore(newContainer, container.nextSibling);
                newContainer.appendChild(lastEntry);
                
                // Move signature to new page
                const signature = container.querySelector('.signature-section');
                if (signature) {
                    newContainer.appendChild(signature.cloneNode(true));
                    signature.remove();
                }
            }
        }
    });
}