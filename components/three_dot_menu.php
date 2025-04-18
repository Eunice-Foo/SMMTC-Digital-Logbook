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
            <button class="menu-item delete-item" onclick="confirmDeletePortfolio(<?php echo $portfolio_id; ?>)">
                <i class="fi fi-rr-trash"></i> Delete Portfolio
            </button>
        </div>
    </div>
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
    background: none;
    border: 2px solid var(--border-color);
    width: 36px;
    height: 36px;
    color: var(--text-secondary);
    cursor: pointer;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    padding: 0; /* Removed padding */
}

.three-dot-btn i {
    font-size: 18px;
    color: var(--text-secondary); /* Set icon color to text-secondary */
}

.three-dot-btn:hover {
    background-color: rgba(0, 0, 0, 0.05);
    border-color: var(--primary-color);
}

.three-dot-btn:hover i {
    color: var(--primary-color); /* Icon color changes on hover */
}

.menu-dropdown {
    position: absolute;
    right: 0;
    top: 100%;
    background-color: white;
    min-width: 180px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
    display: none;
    z-index: 100;
    overflow: hidden;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    text-decoration: none;
    color: var(--text-primary);
    transition: background-color 0.2s ease;
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    font-size: 14px;
}

.menu-item:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.delete-item {
    color: #dc3545;
}

.delete-item:hover {
    background-color: rgba(220, 53, 69, 0.1);
}

.menu-divider {
    height: 1px;
    background-color: var(--border-color);
    margin: 4px 0;
}
</style>

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