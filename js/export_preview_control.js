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

        // Wait for DOM update then check overflow
        setTimeout(checkAllPagesOverflow, 0);
    };

    function checkAllPagesOverflow() {
        console.log('Checking all pages for overflow');
        const pages = document.querySelectorAll('.preview-container');
        let currentPage = pages[0];
        
        pages.forEach((page, index) => {
            const safeAreaHeight = page.offsetHeight - (96 * 2); // Height minus 2-inch margins
            const entries = page.querySelectorAll('.log-entry');
            
            entries.forEach(entry => {
                const entryBottom = entry.offsetTop + entry.offsetHeight;
                console.log(`Page ${index + 1}, Entry bottom: ${entryBottom}, Safe area: ${safeAreaHeight}`);
                
                if (entryBottom > safeAreaHeight) {
                    // If entry overflows, move it to next page
                    console.log(`Entry overflows on page ${index + 1}`);
                    let nextPage = pages[index + 1];
                    
                    // Create new page if needed
                    if (!nextPage) {
                        nextPage = createNewPage();
                        page.parentNode.insertBefore(nextPage, page.nextSibling);
                    }
                    
                    nextPage.insertBefore(entry, nextPage.firstChild);
                }
            });
        });

        // Remove empty pages
        pages.forEach(page => {
            if (!page.querySelector('.log-entry')) {
                page.remove();
            }
        });

        console.log('Overflow check complete');
    }

    function createNewPage() {
        const page = document.createElement('div');
        page.className = 'preview-container';
        return page;
    }
});