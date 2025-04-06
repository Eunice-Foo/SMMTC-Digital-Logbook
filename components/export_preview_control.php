<!-- Floating control bar -->
<div class="floating-controls screen-only">
    <div class="profile-toggle">
        <label class="profile-checkbox">
            <input type="checkbox" id="includeProfile" checked onchange="toggleProfilePage()">
            Include Profile Page
        </label>
    </div>
    <div class="font-controls">
        <div class="control-group">
            <span class="font-label">Text:</span>
            <button onclick="adjustFontSize('decrease')" class="font-button">A-</button>
            <span id="currentFontSize">11pt</span>
            <button onclick="adjustFontSize('increase')" class="font-button">A+</button>
        </div>
        <div class="control-group">
            <span class="font-label">Heading:</span>
            <button onclick="adjustHeadingSize('decrease')" class="font-button">A-</button>
            <span id="currentHeadingSize">14pt</span>
            <button onclick="adjustHeadingSize('increase')" class="font-button">A+</button>
        </div>
        <div class="control-group">
            <span class="font-label">Media:</span>
            <button onclick="adjustMediaSize('decrease')" class="font-button">-</button>
            <span id="currentMediaSize">150px</span>
            <button onclick="adjustMediaSize('increase')" class="font-button">+</button>
        </div>
    </div>
    <button onclick="window.print()" class="print-button">Print Document</button>
</div>
</body>