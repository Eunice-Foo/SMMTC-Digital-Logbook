document.addEventListener('DOMContentLoaded', function() {
    // Initialize font size controls
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

        // Recalculate page breaks
        reorganizePages();

        console.log(`Font size adjusted to: ${currentFontSize}pt`);
    };

    function reorganizePages() {
        const mainContainer = document.querySelector('.preview-section');
        const logEntries = Array.from(document.querySelectorAll('.log-entry'));
        const header = document.querySelector('.document-header');

        // Clear existing pages
        mainContainer.innerHTML = '';

        // Create first page
        let currentPage = createNewPage();
        mainContainer.appendChild(currentPage);

        // Add header to first page
        if (header) {
            currentPage.appendChild(header.cloneNode(true));
        }

        // Calculate available height
        const pageHeight = 297 * 3.78; // A4 height in pixels
        const marginHeight = 96 * 2; // 2 inches margins
        const headerHeight = header ? header.offsetHeight : 0;
        const availableHeight = pageHeight - marginHeight - headerHeight;

        logEntries.forEach((entry, index) => {
            const entryHeight = entry.offsetHeight;
            const currentContentHeight = Array.from(currentPage.children)
                .reduce((total, child) => total + child.offsetHeight, 0);

            // Check if entry needs new page
            if (currentContentHeight + entryHeight > availableHeight) {
                currentPage = createNewPage();
                mainContainer.appendChild(currentPage);
            }

            currentPage.appendChild(entry.cloneNode(true));
        });
    }

    function createNewPage() {
        const page = document.createElement('div');
        page.className = 'preview-container';
        return page;
    }
});