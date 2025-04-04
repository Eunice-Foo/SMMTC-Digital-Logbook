<!-- Floating control bar -->
<div class="floating-controls screen-only">
    <div class="profile-toggle">
        <label class="profile-checkbox">
            <input type="checkbox" id="includeProfile" checked onchange="toggleProfilePage()">
            Include Profile Page
        </label>
    </div>
    <div class="font-controls">
        <button onclick="adjustFontSize('decrease')" class="font-button">A-</button>
        <span id="currentFontSize">11px</span>
        <button onclick="adjustFontSize('increase')" class="font-button">A+</button>
    </div>
    <button onclick="window.print()" class="print-button">Print Document</button>
</div>
</body>