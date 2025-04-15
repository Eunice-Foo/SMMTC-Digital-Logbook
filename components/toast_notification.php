<?php
/**
 * Toast notification component
 * Displays success or error messages with a stylish toast notification
 */

function initializeToast() {
    ?>
    <!-- Flaticon CSS for icons -->
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-solid-rounded/css/uicons-solid-rounded.css'>
    
    <!-- Toast notification container -->
    <div id="toastContainer" style="display:block;">
        <!-- Toast notifications will be added here dynamically -->
    </div>

    <style>
    :root {
        /* Color variables as requested */
        --toast-success-color: rgb(1, 153, 6);
        --toast-success-bg: rgb(234, 251, 234);
        --toast-success-hover: rgb(200, 230, 200);
        --toast-error-color: #f44336;
        --toast-error-bg: #ffebee;
        --toast-error-hover: #ffcdd2;
        --toast-warning-color:rgb(255, 123, 0);
        --toast-warning-bg:rgb(255, 248, 236);
        --toast-warning-hover: #ffe0b2;
    }

    #toastContainer {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        width: 30%; /* Fixed width of 30% of the screen */
        min-width: 300px; /* Minimum width for small screens */
        display: flex;
        flex-direction: column;
        pointer-events: none; /* Allow clicks to pass through container */
    }

    .toast {
        display: flex;
        align-items: center;
        justify-content: space-between; /* Space content from button */
        padding: 20px;
        border-radius: 0 0 8px 8px; /* Changed to only round bottom corners */
        border-top: 4px solid; /* Keep top border */
        border-left: none; /* Remove left border */
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        margin-bottom: 16px;
        animation: slideIn 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards; /* Changed duration and added easing */
        width: 100%;
        box-sizing: border-box;
        pointer-events: auto; /* Re-enable clicks on toast */
    }
    
    .toast-left {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 0; /* Prevent overflow */
    }

    .toast.success {
        background-color: var(--toast-success-bg);
        border-top: none; /* Remove top border for success toast */
        padding-right: 20px;
        position: relative; /* Required for progress bar positioning */
    }

    /* Progress bar for success toast */
    .toast.success .toast-progress {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background-color: var(--toast-success-color);
        animation: toast-progress 3s linear forwards;
        border-radius: 0;
    }

    .toast.error {
        background-color: var(--toast-error-bg);
        border-top-color: var(--toast-error-color);
    }

    .toast.warning {
        background-color: var(--toast-warning-bg);
        border-top-color: var(--toast-warning-color);
    }

    .toast-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 16px; 
        flex-shrink: 0;
    }

    .toast-icon i {
        font-size: 24px;
    }

    .toast.success .toast-icon {color: var(--toast-success-color);}

    .toast.error .toast-icon {color: var(--toast-error-color);}

    .toast.warning .toast-icon {color: var(--toast-warning-color);}

    /* Control icon visibility */
    .toast-icon i {
        display: none; /* Hide all icons by default */
    }

    .toast.success .icon-success {
        display: inline-block;
    }

    .toast.error .icon-error {
        display: inline-block;
    }

    .toast.warning .icon-warning {
        display: inline-block;
    }

    .toast-content {
        flex-grow: 1;
        padding-right: 12px;
        min-width: 0; /* Prevent text overflow */
        overflow: hidden; /* Hide overflow text */
    }

    .toast-title {
        font-weight: 600;
        font-size: 16px; /* Changed to 16px as requested */
        margin-bottom: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .toast.success .toast-title {
        color: var(--toast-success-color);
    }

    .toast.error .toast-title {
        color: var(--toast-error-color);
    }

    .toast.warning .toast-title {
        color: var(--toast-warning-color);
    }

    .toast-message {
        font-size: 16px; /* Changed to 16px as requested */
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .toast.success .toast-message {
        color: var(--toast-success-color); /* Same color as icon */
    }

    .toast.error .toast-message {
        color: var(--toast-error-color); /* Same color as icon */
    }

    .toast.warning .toast-message {
        color: var(--toast-warning-color); /* Same color as icon */
    }

    .toast-button {
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 500;
        font-size: 14px;
        border: none;
        cursor: pointer;
        flex-shrink: 0;
        margin-left: 16px; /* Space from content */
        align-self: center;
        transition: background-color 0.2s;
    }

    /* Hide button for success toasts */
    .toast.success .toast-button {
        display: none;
    }

    .toast.error .toast-button {
        background-color: var(--toast-error-bg);
        color: var(--toast-error-color);
        border: 1px solid var(--toast-error-color);
    }

    .toast.error .toast-button:hover {
        background-color: var(--toast-error-hover);
    }

    .toast.warning .toast-button {
        background-color: var(--toast-warning-bg);
        color: var(--toast-warning-color);
        border: 1px solid var(--toast-warning-color);
    }

    .toast.warning .toast-button:hover {
        background-color: var(--toast-warning-hover);
    }

    @keyframes slideIn {
        0% {
            transform: translateX(120%);
            opacity: 0;
        }
        70% {
            transform: translateX(-10px); /* slight overshoot */
            opacity: 1;
        }
        85% {
            transform: translateX(5px); /* bounce back */
        }
        100% {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        0% {
            transform: translateX(0);
            opacity: 1;
        }
        100% {
            transform: translateX(120%);
            opacity: 0;
        }
    }

    .toast-hide {
        animation: slideOut 0.3s cubic-bezier(0.55, 0.085, 0.68, 0.53) forwards; /* Added easing */
    }

    @keyframes toast-progress {
        from { 
            width: 0; 
            left: 0;
        }
        to { 
            width: 100%; 
            left: 0;
        }
    }
    </style>

    <script>
    // Toast notification JavaScript functions
    let toastIdCounter = 0; // Counter to create unique IDs for each toast

    function showToast(type, title, message, buttonText = 'Close', autoHide = true) {
        const toastContainer = document.getElementById('toastContainer');
        
        // Create unique ID for this toast
        const toastId = 'toast-' + toastIdCounter++;
        
        // Create toast element
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = 'toast ' + type;
        
        // For success toasts, don't include the button
        if (type === 'success') {
            // Add progress bar for success toasts
            toast.style.position = 'relative'; // Required for absolute positioning of progress bar
            
            toast.innerHTML = `
                <div class="toast-left">
                    <div class="toast-icon">
                        <i class="fi fi-sr-badge-check icon-success"></i>
                        <i class="fi fi-sr-times-hexagon icon-error"></i>
                        <i class="fi fi-sr-triangle-warning icon-warning"></i>
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                </div>
                <div class="toast-progress"></div>
            `;
        } else {
            // For error and warning toasts, include the button
            toast.innerHTML = `
                <div class="toast-left">
                    <div class="toast-icon">
                        <i class="fi fi-sr-badge-check icon-success"></i>
                        <i class="fi fi-sr-times-hexagon icon-error"></i>
                        <i class="fi fi-sr-triangle-warning icon-warning"></i>
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                </div>
                <button class="toast-button">${buttonText}</button>
            `;
        }
        
        // Add to container
        toastContainer.appendChild(toast);
        
        // Setup close button for non-success toasts
        if (type !== 'success') {
            const closeButton = toast.querySelector('.toast-button');
            closeButton.onclick = function() {
                hideToast(toastId);
                if (type === 'error' && buttonText === 'Retry') {
                    // Optional: add retry logic here
                }
            };
        }
        
        // Auto hide after 3 seconds for success toasts
        if (type === 'success') {
            setTimeout(() => hideToast(toastId), 3000);
        }
        
        // Return toast ID in case it's needed
        return toastId;
    }

    function hideToast(toastId) {
        const toast = document.getElementById(toastId);
        if (!toast) return;
        
        toast.classList.add('toast-hide');
        
        setTimeout(() => {
            if (toast && toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300); // Match the animation duration
    }

    // Clear all toasts
    function clearAllToasts() {
        const toastContainer = document.getElementById('toastContainer');
        const toasts = toastContainer.querySelectorAll('.toast');
        
        toasts.forEach(toast => {
            if (toast && toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        });
    }

    // Global function to show success toast
    function showSuccessToast(message, title = 'Success!') {
        return showToast('success', title, message, null, true);
    }

    // Global function to show error toast
    function showErrorToast(message, title = 'Error!', buttonText = 'Retry') {
        return showToast('error', title, message, buttonText, false);
    }

    // Global function to show warning toast
    function showWarningToast(message, title = 'Warning!', buttonText = 'Dismiss') {
        return showToast('warning', title, message, buttonText, false);
    }
    </script>
    <?php
}
?>