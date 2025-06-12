// Common software/tools suggestions
const commonTools = [
    // Adobe Creative Suite
    'Adobe Photoshop', 'Adobe Illustrator', 'Adobe Premiere Pro', 
    'Adobe After Effects', 'Adobe XD', 'Adobe InDesign', 'Adobe Animate',
    'Adobe Lightroom', 'Adobe Audition',

    // 3D Software
    'Blender', 'Maya', '3ds Max', 'Cinema 4D', 'ZBrush', 'SketchUp',

    // Game Development
    'Unity', 'Unreal Engine', 'RPG Maker MV', 'RPG Maker MZ', 'Roblox Studio',

    // UI/UX Design & No-Code
    'Canva', 'Figma', 'Sketch', 'FlutterFlow', 'Adalo', 'Wix', 'WordPress',
    'Adobe XD',

    // Programming Languages
    'Python', 'JavaScript', 'HTML', 'CSS', 'PHP', 'Java', 'C++', 'C#',
    'Kotlin', 'TypeScript', 'MySQL', 'Dart', 'Lua', 'C',
    
    // Frameworks & Libraries
    'Flutter',

    // Version Control & DevOps
    'GitHub',

    // Design, Photo & Image Editing
    'CorelDRAW', 'Procreate',

    // Video & Animation
    'DaVinci Resolve', 'Final Cut Pro',

    // Audio & Music
    'Audacity',

    // AI & Machine Learning
    'ChatGPT', 'Deepseak',

    // Office & Productivity
    'Microsoft Word', 'Microsoft Excel', 'Microsoft PowerPoint',
    'Google Docs', 'Google Sheets', 'Google Slides',

    // Data Visualization
    'Power BI', 'Tableau', 'Google Looker Studio'
];

// Global variables
let selectedTools = new Set();

document.addEventListener('DOMContentLoaded', function() {
    initializeToolsInput();
});

function initializeToolsInput() {
    const toolInput = document.getElementById('toolInput');
    const toolSuggestions = document.getElementById('toolSuggestions');
    const selectedToolsContainer = document.getElementById('selectedTools');
    const toolsHidden = document.getElementById('toolsHidden');
    
    // Check if elements exist
    if (!toolInput || !toolSuggestions || !selectedToolsContainer || !toolsHidden) {
        console.warn('Tools input elements not found');
        return;
    }
    
    console.log('Initializing tools input');
    
    // Load existing tools
    const existingTools = toolsHidden.value;
    console.log('Existing tools:', existingTools);
    
    if (existingTools) {
        const tools = existingTools.split(',');
        tools.forEach(tool => {
            const trimmedTool = tool.trim();
            if (trimmedTool) {
                addToolToUI(trimmedTool);
            }
        });
    }
    
    // Rest of the function - event handlers, etc.
    function updateHiddenInput() {
        toolsHidden.value = Array.from(selectedTools).join(',');
        console.log('Updated tools:', toolsHidden.value);
    }
    
    function addToolToUI(tool) {
        if (tool && !selectedTools.has(tool)) {
            console.log('Adding tool:', tool);
            selectedTools.add(tool);
            updateHiddenInput();
            
            const toolTag = document.createElement('span');
            toolTag.className = 'tool-tag';
            toolTag.innerHTML = `
                ${tool}
                <span class="remove" data-tool="${tool}">&times;</span>
            `;
            selectedToolsContainer.appendChild(toolTag);
            return true;
        }
        return false;
    }
    
    function showSuggestions(input) {
        const value = input.toLowerCase();
        const filtered = commonTools.filter(tool => 
            tool.toLowerCase().includes(value)
        );

        if (filtered.length > 0 && value) {
            toolSuggestions.innerHTML = filtered
                .map(tool => `<div class="suggestion-item">${tool}</div>`)
                .join('');
            toolSuggestions.style.display = 'block';
        } else {
            toolSuggestions.style.display = 'none';
        }
    }

    // Event listeners
    toolInput.addEventListener('input', (e) => {
        showSuggestions(e.target.value);
    });

    toolInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const success = addToolToUI(toolInput.value.trim());
            if (success) {
                toolInput.value = '';
                toolSuggestions.style.display = 'none';
            }
        }
    });

    toolSuggestions.addEventListener('click', (e) => {
        if (e.target.classList.contains('suggestion-item')) {
            addToolToUI(e.target.textContent);
            toolInput.value = '';
            toolSuggestions.style.display = 'none';
        }
    });

    selectedToolsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove')) {
            const tool = e.target.dataset.tool;
            console.log('Removing tool:', tool);
            selectedTools.delete(tool);
            updateHiddenInput();
            e.target.parentElement.remove();
        }
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.tools-input-container')) {
            toolSuggestions.style.display = 'none';
        }
    });
}

// Make addTool globally accessible for inline scripts
window.addTool = function(tool) {
    const trimmedTool = tool.trim();
    if (trimmedTool) {
        selectedTools.add(trimmedTool);
        
        const toolsHidden = document.getElementById('toolsHidden');
        if (toolsHidden) {
            toolsHidden.value = Array.from(selectedTools).join(',');
        }
        
        const selectedToolsContainer = document.getElementById('selectedTools');
        if (selectedToolsContainer) {
            const toolTag = document.createElement('span');
            toolTag.className = 'tool-tag';
            toolTag.innerHTML = `
                ${trimmedTool}
                <span class="remove" data-tool="${trimmedTool}">&times;</span>
            `;
            selectedToolsContainer.appendChild(toolTag);
            return true;
        }
    }
    return false;
};