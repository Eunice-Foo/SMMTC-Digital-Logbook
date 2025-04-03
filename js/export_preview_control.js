document.addEventListener('DOMContentLoaded', function() {
    let currentFontSize = 11;
    const minFontSize = 9;
    const maxFontSize = 16;

    window.adjustFontSize = function(action) {
        if (action === 'increase' && currentFontSize < maxFontSize) {
            currentFontSize++;
        } else if (action === 'decrease' && currentFontSize > minFontSize) {
            currentFontSize--;
        }

        // Update display
        document.getElementById('currentFontSize').textContent = `${currentFontSize}pt`;

        // Update font sizes
        document.querySelectorAll('.preview-container').forEach(container => {
            const textElements = container.querySelectorAll(`
                .log-entry p, 
                .log-description p,
                .document-header h1,
                .document-header h3,
                .log-datetime h3,
                .supervisor-feedback h4,
                .supervisor-feedback p
            `);

            textElements.forEach(element => {
                element.style.fontSize = `${currentFontSize}pt`;
            });
        });

        // Trigger page reorganization after font change
        window.checkAllPagesOverflow();
    };

    function checkAllPagesOverflow() {
        console.log('Checking all pages for overflow and underflow');
        const pages = Array.from(document.querySelectorAll('.preview-container'));
        const signatureHeight = 120; // Height for signature block
        const topMargin = 96; // 1 inch top margin
        const bottomMargin = 96 + signatureHeight; // 1 inch bottom margin + signature space
        
        // First check for underflow (moving entries back to previous pages)
        for (let i = pages.length - 1; i > 0; i--) {
            const currentPage = pages[i];
            const previousPage = pages[i - 1];
            const entries = currentPage.querySelectorAll('.log-entry');
            
            // Try to move entries back to previous page
            Array.from(entries).forEach(entry => {
                const safeAreaHeight = previousPage.offsetHeight - (topMargin + bottomMargin);
                const previousPageContent = Array.from(previousPage.children);
                const currentContentHeight = previousPageContent.reduce((total, child) => 
                    total + child.offsetHeight, 0);
                
                // Check if entry can fit in previous page
                const entryHeight = entry.offsetHeight;
                console.log(`Safe area check - Height: ${safeAreaHeight}, Content: ${currentContentHeight}, Entry: ${entryHeight}`);
                
                if (currentContentHeight + entryHeight <= safeAreaHeight) {
                    console.log('Moving entry back to previous page');
                    previousPage.appendChild(entry);
                }
            });
        }

        // Then check for overflow (moving entries to next pages)
        pages.forEach((page, index) => {
            const safeAreaHeight = page.offsetHeight - (topMargin + bottomMargin);
            const entries = page.querySelectorAll('.log-entry');
            
            entries.forEach(entry => {
                const entryBottom = entry.offsetTop + entry.offsetHeight;
                console.log(`Page ${index + 1} - Entry bottom: ${entryBottom}, Safe area: ${safeAreaHeight}`);
                
                if (entryBottom > safeAreaHeight) {
                    console.log(`Entry overflows on page ${index + 1}, creating new page`);
                    let nextPage = pages[index + 1];
                    
                    if (!nextPage) {
                        nextPage = createNewPage();
                        page.parentNode.insertBefore(nextPage, page.nextSibling);
                        pages.push(nextPage);
                    }
                    
                    nextPage.insertBefore(entry, nextPage.firstChild);
                }
            });
        });

        // Clean up empty pages
        pages.forEach(page => {
            if (!page.querySelector('.log-entry')) {
                page.remove();
            }
        });

        console.log('Page reorganization complete');
    }

    function createNewPage() {
        const page = document.createElement('div');
        page.className = 'preview-container';
        return page;
    }
});