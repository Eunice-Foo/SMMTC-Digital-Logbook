<?php
/**
 * Renders a three-dot menu for actions
 *
 * @param int $portfolio_id The ID of the portfolio
 */
function renderThreeDotMenu($portfolio_id) {
    ?>
    <div class="three-dot-menu">
        <button class="three-dot-btn" onclick="toggleMenu(event)">
            <i class="fi fi-rr-menu-dots-vertical"></i>
        </button>
        <div class="menu-dropdown" id="portfolioMenu">
            <a href="edit_portfolio.php?id=<?php echo $portfolio_id; ?>" class="menu-item">
                <i class="fi fi-rr-edit"></i> Edit
            </a>
            <div class="menu-divider"></div>
            <button class="menu-item delete-item" onclick="confirmDelete(<?php echo $portfolio_id; ?>, 'portfolio')">
                <i class="fi fi-rr-trash"></i> Delete Portfolio
            </button>
        </div>
    </div>
    
    <script>
    function toggleMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('portfolioMenu');
        const isVisible = menu.style.display === 'block';
        
        // Close any open menus first
        closeAllMenus();
        
        // Toggle this menu
        if (!isVisible) {
            menu.style.display = 'block';
        }
    }

    function closeAllMenus() {
        const menus = document.querySelectorAll('.menu-dropdown');
        menus.forEach(menu => {
            menu.style.display = 'none';
        });
    }

    // Close menu when clicking outside
    document.addEventListener('click', function() {
        closeAllMenus();
    });
    </script>
    <?php
}
?>

<style>
.three-dot-menu {
    position: relative;
    display: inline-block;
    margin-left: auto; /* Pushes menu to the right */
}

.three-dot-btn {
    background-color: var(--bg-tertiary); /* Light gray background instead of transparent */
    border: 1px solid var(--border-color); /* Add a subtle border */
    cursor: pointer;
    padding: 8px; /* Increased from 6px to 8px */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    width: 36px; /* Increased from 28px to 36px */
    height: 36px; /* Increased from 28px to 36px */
}

.three-dot-btn i {
    color: var(--primary-color); /* Changed from var(--text-secondary) to var(--primary-color) */
    font-size: 18px; /* Increased from 16px to 18px */
}

.three-dot-btn:hover {
    background-color: var(--primary-light); /* Keep using primary light color on hover */
}

.menu-dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 5px);
    min-width: 200px; /* Increased from 180px to 200px */
    background-color: white;
    box-shadow: 0 3px 12px rgba(0,0,0,0.15);
    border-radius: 8px;
    display: none;
    z-index: 100;
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 10px; /* Increased from 8px to 10px */
    padding: 14px 18px; /* Increased from 12px 16px to 14px 18px */
    text-decoration: none;
    color: var(--text-primary);
    transition: background-color 0.2s ease;
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    font-size: 15px; /* Increased from 14px to 15px */
}

.menu-item i {
    font-size: 18px; /* Increased from 16px to 18px */
    width: 18px; /* Increased from 16px to 18px */
    text-align: center;
}

.menu-item:hover {
    background-color: var(--bg-secondary);
}

.delete-item {
    color: var(--danger-color);
}

.delete-item:hover {
    background-color: rgba(187, 45, 59, 0.1); /* Changed to darker red with transparency */
}

.menu-divider {
    height: 1px;
    background-color: var(--border-color);
    margin: 4px 0;
}
</style>

<script>
function confirmDeletePortfolio(portfolioId) {
    // Check if toast functions exist for better UX
    if (typeof showWarningToast === 'function') {
        showWarningToast(
            'Are you sure you want to delete this portfolio item? This action cannot be undone.',
            'Confirm Delete',
            'Delete',
            function() { deletePortfolio(portfolioId); }
        );
    } else {
        // Fallback to standard confirm
        if (confirm('Are you sure you want to delete this portfolio item? This action cannot be undone.')) {
            deletePortfolio(portfolioId);
        }
    }
}

function deletePortfolio(portfolioId) {
    // Send AJAX request to delete the portfolio
    fetch('delete_portfolio.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'portfolio_id=' + portfolioId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message and redirect
            if (typeof showSuccessToast === 'function') {
                showSuccessToast('Portfolio deleted successfully', 'Deleted');
                setTimeout(() => {
                    window.location.href = 'portfolio.php';
                }, 1500);
            } else {
                alert('Portfolio deleted successfully');
                window.location.href = 'portfolio.php';
            }
        } else {
            // Show error message
            if (typeof showErrorToast === 'function') {
                showErrorToast(data.message || 'Unknown error occurred', 'Error');
            } else {
                alert('Error: ' + (data.message || 'Unknown error occurred'));
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showErrorToast === 'function') {
            showErrorToast('Failed to connect to the server', 'Connection Error');
        } else {
            alert('Connection Error: Failed to delete portfolio');
        }
    });
}
</script>