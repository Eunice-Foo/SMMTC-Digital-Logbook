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
    ensureSignaturesOnAllPages();
};

function handleUnderflow(pages, topMargin, bottomMargin) {
    for (let i = pages.length - 1; i > 0; i--) {
        const currentPage = pages[i];
        const previousPage = pages[i - 1];
        const currentEntries = Array.from(currentPage.querySelectorAll('.log-entry'));
        
        if (currentEntries.length === 0) continue;
        
        // Only try to move the first entry of current page to previous page
        const firstEntryOfCurrentPage = currentEntries[0];
        
        // Check if entry can fit while maintaining chronological order
        if (canFitInPreviousPage(firstEntryOfCurrentPage, previousPage, topMargin, bottomMargin) && 
            isChronologicallyValid(firstEntryOfCurrentPage, previousPage)) {
            console.log('Moving entry to previous page');
            previousPage.appendChild(firstEntryOfCurrentPage);
        }
    }
}

function isChronologicallyValid(entry, previousPage) {
    const entryDate = new Date(entry.querySelector('.log-datetime h3').textContent);
    const lastEntryInPrevPage = previousPage.querySelector('.log-entry:last-child');
    
    // If no entries in previous page, it's valid
    if (!lastEntryInPrevPage) return true;
    
    const lastEntryDate = new Date(lastEntryInPrevPage.querySelector('.log-datetime h3').textContent);
    console.log('Date comparison:', {
        moving: entryDate,
        existing: lastEntryDate
    });
    
    // Entry date should be after or equal to the last entry in previous page
    return entryDate >= lastEntryDate;
}

function canFitInPreviousPage(entry, previousPage, topMargin, bottomMargin) {
    const safeAreaHeight = previousPage.offsetHeight - (topMargin + bottomMargin);
    const currentContentHeight = Array.from(previousPage.children)
        .reduce((total, child) => {
            // Don't count signature section in content height
            if (!child.classList.contains('signature-section')) {
                return total + child.offsetHeight;
            }
            return total;
        }, 0);
    
    console.log('Space check:', {
        available: safeAreaHeight,
        current: currentContentHeight,
        needed: entry.offsetHeight
    });
    
    return currentContentHeight + entry.offsetHeight <= safeAreaHeight;
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

function isOverflowing(entry, page, topMargin, bottomMargin) {
    const safeAreaHeight = page.offsetHeight - (topMargin + bottomMargin);
    const entryBottom = entry.offsetTop + entry.offsetHeight;
    return entryBottom > safeAreaHeight;
}

// Modify moveToNextPage function to handle signatures
function moveToNextPage(entry, currentPage, pages, currentIndex) {
    let nextPage = pages[currentIndex + 1];
    
    if (!nextPage) {
        nextPage = createNewPage();
        currentPage.parentNode.insertBefore(nextPage, currentPage.nextSibling);
        pages.push(nextPage);
    }
    
    // Move entry to next page
    nextPage.insertBefore(entry, nextPage.firstChild);
    
    // Ensure both pages have signatures
    ensureSignaturesOnAllPages();
}

function cleanupEmptyPages(pages) {
    pages.forEach(page => {
        if (!page.querySelector('.log-entry')) {
            page.remove();
        }
    });
}

// Add new function to ensure signatures
function ensureSignaturesOnAllPages() {
    const pages = Array.from(document.querySelectorAll('.preview-container:not(.profile-page)'));
    const originalSignature = document.querySelector('.signature-section');
    
    if (!originalSignature) return;
    
    pages.forEach(page => {
        // Remove existing signature if present (to prevent duplicates)
        const existingSignature = page.querySelector('.signature-section');
        if (existingSignature) {
            existingSignature.remove();
        }
        
        // Add new signature to page
        const signatureCopy = originalSignature.cloneNode(true);
        page.appendChild(signatureCopy);
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

// Update checkContentOverflow function
function checkContentOverflow() {
    const containers = document.querySelectorAll('.preview-container:not(.profile-page)');
    const signatureHeight = 120;
    
    containers.forEach(container => {
        const contentHeight = Array.from(container.children)
            .reduce((total, child) => {
                if (!child.classList.contains('signature-section')) {
                    return total + child.offsetHeight;
                }
                return total;
            }, 0);
        const availableHeight = container.offsetHeight - (96 * 2) - signatureHeight;
        
        if (contentHeight > availableHeight) {
            const lastEntry = container.querySelector('.log-entry:last-child');
            if (lastEntry) {
                const newContainer = createNewPage();
                container.parentNode.insertBefore(newContainer, container.nextSibling);
                newContainer.appendChild(lastEntry);
            }
        }
    });
    
    // Ensure all pages have signatures after reorganization
    ensureSignaturesOnAllPages();
}