<?php
/**
 * Toast notification component
 * Displays success or error messages with a stylish toast notification
 */

function initializeToast() {
    ?>
    <!-- Toast notification container -->
    <div id="toastContainer" style="display:none;">
        <div id="toast" class="toast">
            <div class="toast-icon">
                <svg id="successIcon" class="icon-success" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" fill="currentColor"/>
                </svg>
                <svg id="errorIcon" class="icon-error" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" fill="currentColor"/>
                </svg>
            </div>
            <div class="toast-content">
                <div id="toastTitle" class="toast-title">Success!</div>
                <div id="toastMessage" class="toast-message">Your changes have been saved successfully!</div>
            </div>
            <button id="toastButton" class="toast-button">Close</button>
        </div>
    </div>

    <style>
    #toastContainer {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        width: 100%;
        max-width: 500px;
        padding: 0 15px;
    }

    .toast {
        display: flex;
        align-items: flex-start;
        padding: 16px;
        border-radius: 8px;
        border-left: 4px solid;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        margin-bottom: 16px;
        animation: slideIn 0.3s ease-out forwards;
    }

    .toast.success {
        background-color: #e8f5e9;
        border-left-color: #4caf50;
    }

    .toast.error {
        background-color: #ffebee;
        border-left-color: #f44336;
    }

    .toast-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        margin-right: 12px;
        flex-shrink: 0;
    }

    .toast.success .toast-icon {
        color: #4caf50;
    }

    .toast.error .toast-icon {
        color: #f44336;
    }

    .icon-success, .icon-error {
        width: 24px;
        height: 24px;
    }

    .toast.success .icon-error,
    .toast.error .icon-success {
        display: none;
    }

    .toast-content {
        flex-grow: 1;
        padding-right: 12px;
    }

    .toast-title {
        font-weight: 600;
        font-size: 18px;
        margin-bottom: 4px;
    }

    .toast.success .toast-title {
        color: #2e7d32;
    }

    .toast.error .toast-title {
        color: #c62828;
    }

    .toast-message {
        color: #555;
        font-size: 14px;
    }

    .toast-button {
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 500;
        font-size: 14px;
        border: none;
        cursor: pointer;
        flex-shrink: 0;
        transition: background-color 0.2s;
    }

    .toast.success .toast-button {
        background-color: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #4caf50;
    }

    .toast.success .toast-button:hover {
        background-color: #c8e6c9;
    }

    .toast.error .toast-button {
        background-color: #ffebee;
        color: #c62828;
        border: 1px solid #f44336;
    }

    .toast.error .toast-button:hover {
        background-color: #ffcdd2;
    }

    @keyframes slideIn {
        from {
            transform: translateY(-30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(-30px);
            opacity: 0;
        }
    }

    .toast-hide {
        animation: slideOut 0.3s ease-in forwards;
    }
    </style>

    <script>
    // Toast notification JavaScript functions
    function showToast(type, title, message, buttonText = 'Close', autoHide = true) {
        const toastContainer = document.getElementById('toastContainer');
        const toast = document.getElementById('toast');
        const toastTitle = document.getElementById('toastTitle');
        const toastMessage = document.getElementById('toastMessage');
        const toastButton = document.getElementById('toastButton');
        
        // Set content
        toastTitle.innerText = title;
        toastMessage.innerText = message;
        toastButton.innerText = buttonText;
        
        // Set type styling
        toast.className = 'toast ' + type;
        
        // Button action based on type
        if (type === 'error') {
            toastButton.onclick = function() {
                hideToast();
                if (buttonText === 'Retry') {
                    // Optional: add retry logic here
                }
            };
        } else {
            toastButton.onclick = hideToast;
        }
        
        // Show toast
        toastContainer.style.display = 'block';
        
        // Auto hide after 5 seconds for success toasts
        if (autoHide && type === 'success') {
            setTimeout(hideToast, 5000);
        }
    }

    function hideToast() {
        const toastContainer = document.getElementById('toastContainer');
        const toast = document.getElementById('toast');
        
        toast.classList.add('toast-hide');
        
        setTimeout(() => {
            toast.classList.remove('toast-hide');
            toastContainer.style.display = 'none';
        }, 300);
    }

    // Global function to show success toast
    function showSuccessToast(message, title = 'Success!') {
        showToast('success', title, message, 'Close', true);
    }

    // Global function to show error toast
    function showErrorToast(message, title = 'Error!', buttonText = 'Retry') {
        showToast('error', title, message, buttonText, false);
    }
    </script>
    <?php
}
?>