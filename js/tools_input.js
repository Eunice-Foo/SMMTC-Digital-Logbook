// Common software/tools suggestions
const commonTools = [
    // Adobe Creative Suite
    'Adobe Photoshop', 'Adobe Illustrator', 'Adobe Premiere Pro', 
    'Adobe After Effects', 'Adobe XD', 'Adobe InDesign', 'Adobe Animate',
    'Adobe Lightroom', 'Adobe Audition', 'Adobe Dimension',

    // 3D Software
    'Blender', 'Maya', '3ds Max', 'Cinema 4D', 'ZBrush', 'SketchUp',
    'Houdini', 'Substance Painter', 'Substance Designer', 'Marvelous Designer',

    // Game Development
    'Unity', 'Unreal Engine', 'Roblox Studio', 'Godot', 'RPG Maker MV', 'RPG Maker MZ',
    'GameMaker Studio', 'CryEngine', 'Construct', 'Phaser', 'Cocos2d',

    // UI/UX & No-Code
    'Figma', 'Sketch', 'InVision', 'FlutterFlow', 'Adalo', 'Bubble',
    'Webflow', 'Wix', 'WordPress', 'Elementor', 'Framer', 'Proto.io',
    'Adobe XD', 'Balsamiq', 'Marvel', 'Principle',

    // Programming Languages
    'Python', 'JavaScript', 'HTML', 'CSS', 'PHP', 'Java', 'C++', 'C#',
    'Swift', 'Kotlin', 'Ruby', 'TypeScript', 'SQL', 'R', 'Go', 'Rust',
    'Dart', 'Lua', 'MATLAB',

    // Frameworks & Libraries
    'React', 'Angular', 'Vue.js', 'Django', 'Flask', 'Laravel',
    'Node.js', 'Express.js', 'Spring Boot', 'Flutter', 'Bootstrap',
    'TailwindCSS', 'jQuery', 'Next.js', 'Nuxt.js', 'Svelte',

    // Database Technologies
    'MySQL', 'PostgreSQL', 'MongoDB', 'SQLite', 'Redis', 'Oracle',
    'Microsoft SQL Server', 'Firebase', 'Supabase',

    // Version Control & DevOps
    'Git', 'GitHub', 'GitLab', 'Bitbucket', 'Docker', 'Kubernetes',
    'Jenkins', 'AWS', 'Azure', 'Google Cloud',

    // Design & Image Editing
    'GIMP', 'Inkscape', 'Affinity Designer', 'Affinity Photo',
    'Krita', 'CorelDRAW', 'Paint Tool SAI', 'Clip Studio Paint',

    // Video & Animation
    'DaVinci Resolve', 'Final Cut Pro', 'Vegas Pro', 'Moho',
    'Toon Boom Harmony', 'OpenToonz', 'Pencil2D', 'Synfig Studio',

    // Audio
    'Audacity', 'FL Studio', 'Ableton Live', 'Logic Pro X',
    'Pro Tools', 'GarageBand', 'Reaper',

    // Project Management
    'Jira', 'Trello', 'Asana', 'Monday.com', 'Notion', 'ClickUp',
    'Linear', 'GitHub Projects',

    // Other
    'AutoCAD', 'Fusion 360', 'Rhinoceros 3D', 'Revit',
    'Microsoft Office', 'Google Workspace', 'Slack', 'Discord'
];

let selectedTools = new Set();

document.addEventListener('DOMContentLoaded', function() {
    const toolInput = document.getElementById('toolInput');
    const toolSuggestions = document.getElementById('toolSuggestions');
    const selectedToolsContainer = document.getElementById('selectedTools');
    const toolsHidden = document.getElementById('toolsHidden');

    function updateHiddenInput() {
        toolsHidden.value = Array.from(selectedTools).join(',');
    }

    function addTool(tool) {
        if (tool && !selectedTools.has(tool)) {
            selectedTools.add(tool);
            updateHiddenInput();
            
            const toolTag = document.createElement('span');
            toolTag.className = 'tool-tag';
            toolTag.innerHTML = `
                ${tool}
                <span class="remove" data-tool="${tool}">&times;</span>
            `;
            selectedToolsContainer.appendChild(toolTag);
        }
        toolInput.value = '';
        toolSuggestions.style.display = 'none';
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

    toolInput.addEventListener('input', (e) => {
        showSuggestions(e.target.value);
    });

    toolInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addTool(toolInput.value.trim());
        }
    });

    toolSuggestions.addEventListener('click', (e) => {
        if (e.target.classList.contains('suggestion-item')) {
            addTool(e.target.textContent);
        }
    });

    selectedToolsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove')) {
            const tool = e.target.dataset.tool;
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
});