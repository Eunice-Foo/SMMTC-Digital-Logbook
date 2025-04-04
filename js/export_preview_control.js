document.addEventListener('DOMContentLoaded', function() {
    let currentTextSize = 11;
    let currentHeadingSize = 14;
    const minTextSize = 9;
    const maxTextSize = 16;
    const minHeadingSize = 12;
    const maxHeadingSize = 18;

    window.adjustFontSize = function(action) {
        if (action === 'increase' && currentTextSize < maxTextSize) {
            currentTextSize++;
        } else if (action === 'decrease' && currentTextSize > minTextSize) {
            currentTextSize--;
        }

        // Update display
        document.getElementById('currentFontSize').textContent = `${currentTextSize}pt`;

        // Update paragraph font sizes
        document.querySelectorAll('.preview-container').forEach(container => {
            const textElements = container.querySelectorAll('p');
            textElements.forEach(element => {
                element.style.fontSize = `${currentTextSize}pt`;
            });
        });

        // Trigger page reorganization
        window.checkAllPagesOverflow();
    };

    window.adjustHeadingSize = function(action) {
        if (action === 'increase' && currentHeadingSize < maxHeadingSize) {
            currentHeadingSize++;
        } else if (action === 'decrease' && currentHeadingSize > minHeadingSize) {
            currentHeadingSize--;
        }

        // Update display
        document.getElementById('currentHeadingSize').textContent = `${currentHeadingSize}pt`;

        // Update heading font sizes
        document.querySelectorAll('.preview-container').forEach(container => {
            const headingElements = container.querySelectorAll('h1, h2, h3, h4, h5, h6');
            headingElements.forEach(element => {
                element.style.fontSize = `${currentHeadingSize}pt`;
            });
        });

        // Trigger page reorganization
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