<!-- Floating control bar -->
<div class="floating-controls screen-only">
    <!-- Wrap profile toggle and controls in a container -->
    <div class="export-left-container">
        <!-- Controls container now comes first -->
        <div class="controls-container">
            <div class="control-section">
                <span class="control-label">Text Size:</span>
                <div class="control-group">
                    <button onclick="adjustFontSize('decrease')" class="font-button">−</button>
                    <span id="currentFontSize">11pt</span>
                    <button onclick="adjustFontSize('increase')" class="font-button">+</button>
                </div>
            </div>
            
            <div class="control-section">
                <span class="control-label">Heading Size:</span>
                <div class="control-group">
                    <button onclick="adjustHeadingSize('decrease')" class="font-button">−</button>
                    <span id="currentHeadingSize">14pt</span>
                    <button onclick="adjustHeadingSize('increase')" class="font-button">+</button>
                </div>
            </div>
            
            <div class="control-section">
                <span class="control-label">Media Size:</span>
                <div class="control-group">
                    <button onclick="adjustMediaSize('decrease')" class="font-button">−</button>
                    <span id="currentMediaSize">150px</span>
                    <button onclick="adjustMediaSize('increase')" class="font-button">+</button>
                </div>
            </div>
        </div>
        
        <!-- Profile toggle moved after controls -->
        <div class="profile-toggle">
            <label class="profile-checkbox">
                <input type="checkbox" id="includeProfile" checked onchange="toggleProfilePage()">
                Include profile page
            </label>
        </div>
    </div>
    
    <button onclick="window.print()" class="print-button btn">
        <i class="fi fi-rr-print"></i> Print
    </button>
</div>
</body>