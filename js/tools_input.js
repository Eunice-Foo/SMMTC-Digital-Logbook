// Common software/tools suggestions
const commonTools = [
    // Adobe Creative Suite
    'Adobe Photoshop', 'Adobe Illustrator', 'Adobe Premiere Pro', 
    'Adobe After Effects', 'Adobe XD', 'Adobe InDesign', 'Adobe Animate',
    'Adobe Lightroom', 'Adobe Audition', 'Adobe Dimension',

    // 3D Software
    'Blender', 'Maya', '3ds Max', 'Cinema 4D', 'ZBrush', 'SketchUp', 
    'Houdini', 'Substance Painter', 'Substance Designer', 'Marvelous Designer',
    'Daz Studio', 'Modo', 'Rhino 3D', 'V-Ray', 'KeyShot',

    // Game Development
    'Unity', 'Unreal Engine', 'Godot', 'RPG Maker MV', 'RPG Maker MZ', 
    'GameMaker Studio', 'CryEngine', 'Construct', 'Phaser', 'Cocos2d',
    'Twine', 'Ren\'Py', 'PlayCanvas', 'AppGameKit', 'Amazon Lumberyard',
    'RPG Maker VX Ace', 'Game Builder Garage', 'Dreams',

    // UI/UX Design & No-Code
    'Figma', 'Sketch', 'InVision', 'FlutterFlow', 'Adalo', 'Bubble',
    'Webflow', 'Wix', 'WordPress', 'Elementor', 'Framer', 'Proto.io',
    'Balsamiq', 'Marvel', 'Principle', 'Axure RP', 'Adobe XD',
    'Thunkable', 'Glide', 'AppSheet', 'Softr', 'Stacker', 'Zapier',

    // Programming Languages
    'Python', 'JavaScript', 'HTML', 'CSS', 'PHP', 'Java', 'C++', 'C#',
    'Swift', 'Kotlin', 'Ruby', 'TypeScript', 'SQL', 'MySQL', 'PostgreSQL',
    'R', 'Go', 'Rust', 'Dart', 'Lua', 'MATLAB', 'Scala', 'Shell Script',
    'C', 'Objective-C', 'Perl', 'Haskell', 'Assembly', 'Solidity', 'COBOL',
    
    // Frameworks & Libraries
    'React', 'Angular', 'Vue.js', 'Django', 'Flask', 'Laravel',
    'Node.js', 'Express.js', 'Spring Boot', 'Flutter', 'Bootstrap',
    'TailwindCSS', 'jQuery', 'Next.js', 'Nuxt.js', 'Svelte',
    'React Native', 'Electron', 'PyTorch', 'TensorFlow', 'Pandas',
    'Ruby on Rails', 'ASP.NET', 'Symfony', 'CodeIgniter', 'FastAPI',

    // Database & Storage
    'MySQL', 'MongoDB', 'PostgreSQL', 'SQLite', 'Redis', 'Oracle',
    'Microsoft SQL Server', 'Firebase', 'Supabase', 'Amazon DynamoDB',
    'Cassandra', 'Neo4j', 'MariaDB', 'CouchDB', 'Elasticsearch',

    // Version Control & DevOps
    'Git', 'GitHub', 'GitLab', 'Bitbucket', 'Docker', 'Kubernetes',
    'Jenkins', 'AWS', 'Azure', 'Google Cloud', 'Heroku', 'Netlify',
    'Vercel', 'CircleCI', 'Travis CI', 'Terraform', 'Ansible', 'Jira',

    // Design, Photo & Image Editing
    'GIMP', 'Inkscape', 'Affinity Designer', 'Affinity Photo',
    'Krita', 'CorelDRAW', 'Paint Tool SAI', 'Clip Studio Paint',
    'Aseprite', 'Pixelmator', 'Procreate', 'PaintShop Pro',

    // Video & Animation
    'DaVinci Resolve', 'Final Cut Pro', 'Vegas Pro', 'Moho',
    'Toon Boom Harmony', 'OpenToonz', 'Pencil2D', 'Synfig Studio',
    'iMovie', 'Lightworks', 'Shotcut', 'Kdenlive', 'Camtasia',
    'Filmora', 'Hitfilm Express', 'Blender Animation',

    // Audio & Music
    'Audacity', 'FL Studio', 'Ableton Live', 'Logic Pro X',
    'Pro Tools', 'GarageBand', 'Reaper', 'Studio One',
    'Cubase', 'Reason', 'Bitwig Studio', 'Ardour',

    // Project & Task Management
    'Jira', 'Trello', 'Asana', 'Monday.com', 'Notion', 'ClickUp',
    'Linear', 'GitHub Projects', 'Basecamp', 'Airtable', 'Todoist',

    // AI & Machine Learning
    'TensorFlow', 'PyTorch', 'Keras', 'Scikit-learn', 'OpenAI API',
    'Hugging Face', 'NLTK', 'Jupyter Notebook', 'Google Colab',
    'Midjourney', 'DALL-E', 'Stable Diffusion', 'ChatGPT',

    // Office & Productivity
    'Microsoft Office', 'Google Workspace', 'LibreOffice', 'Notion',
    'Confluence', 'Evernote', 'OneNote', 'Obsidian', 'Miro', 'Figma',

    // CAD & Architecture
    'AutoCAD', 'Fusion 360', 'SolidWorks', 'Revit', 'Rhino 3D',
    'ArchiCAD', 'SketchUp', 'CATIA', 'Inventor', 'Onshape',

    // Communication & Collaboration
    'Slack', 'Discord', 'Microsoft Teams', 'Zoom', 'Google Meet',
    'Miro', 'Figma', 'Notion', 'Confluence', 'Trello'
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