<?php
// filepath: c:\xampp\htdocs\log\components\topnav.php
require_once 'includes/session_check.php';
require_once 'includes/profile_functions.php';

// Fetch user profile picture and full name if needed
if (!isset($user_profile_picture) && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("
        SELECT 
            u.profile_picture, 
            CASE 
                WHEN u.role = 1 THEN s.full_name
                WHEN u.role = 2 THEN sv.supervisor_name
                ELSE u.user_name
            END as full_name
        FROM user u
        LEFT JOIN student s ON u.user_id = s.student_id AND u.role = 1
        LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id AND u.role = 2
        WHERE u.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_profile_picture = $result['profile_picture'] ?? null;
    $user_full_name = $result['full_name'] ?? $_SESSION['username'];
}
?>

<!-- Add the Flaticon CSS link -->
<link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
<link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.1.0/uicons-regular-straight/css/uicons-regular-straight.css'>
<link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>


<div class="topnav">
    <div class="topnav-brand">
        <a href="<?php echo $_SESSION['role'] == ROLE_STUDENT ? 'main_menu.php' : 'sv_main.php'; ?>">
            <!-- Replace text with logo image -->
            <img src="images/logo.png" alt="Logbook Logo" class="logo-image">
        </a>
    </div>

    <nav class="topnav-menu">
        <ul>
            <?php if ($_SESSION['role'] == ROLE_STUDENT): ?>
                <li>
                    <a href="main_menu.php">
                        <i class="fi fi-rr-layers"></i>
                        <span>Explore</span>
                    </a>
                </li>
                <li>
                    <a href="logbook.php">
                        <i class="fi fi-rr-audit-alt"></i>
                        <span>My Logbook</span>
                    </a>
                </li>
                <li>
                    <a href="portfolio.php">
                        <i class="fi fi-rr-objects-column"></i>
                        <span>My Portfolio</span>
                    </a>
                </li>
                <!-- Removed Practicum Info tab -->
            <?php elseif ($_SESSION['role'] == ROLE_SUPERVISOR): ?>
                <li>
                    <a href="sv_main.php">
                        <i class="fi fi-rr-users"></i>
                        <span>Interns</span>
                    </a>
                </li>
                <li>
                    <a href="view_practicum_info.php">
                        <i class="fi fi-rr-building"></i>
                        <span>Company Info</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="topnav-profile">
        <div class="profile-dropdown">
            <button class="profile-button" onclick="toggleProfileMenu(event)">
                <div class="profile-avatar">
                    <?php if (!empty($user_profile_picture)): ?>
                        <img src="<?php echo getProfileImagePath($user_profile_picture, 'sm'); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <div class="profile-placeholder">
                            <i class="fi fi-rr-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <span class="profile-name"><?php echo htmlspecialchars($user_full_name); ?></span>
                <i class="fi fi-rr-angle-down"></i>
            </button>

            <div class="profile-menu" id="profileMenu">
                <a href="view_profile.php">
                    <i class="fi fi-rr-user"></i> My Profile
                </a>
                <a href="edit_profile.php">
                    <i class="fi fi-rr-edit"></i> Edit Profile
                </a>
                <div class="menu-divider"></div>
                <a href="javascript:void(0)" onclick="handleLogout()" class="logout-item">
                    <i class="fi fi-rr-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Top Navigation Bar */
.topnav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 64px;
    background-color: var(--bg-primary);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    z-index: 1000;
}

/* Brand/Logo */
.topnav-brand {
    display: flex;
    align-items: center;
}

.topnav-brand a {
    text-decoration: none;
    display: flex;
    align-items: center;
}

/* Style for the logo image */
.logo-image {
    height: 40px; /* Adjust height as needed */
    width: auto;
    object-fit: contain;
}

/* Navigation Menu */
.topnav-menu {
    flex: 1;
    display: flex;
    justify-content: center;
}

.topnav-menu ul {
    display: flex;
    list-style-type: none;
    margin: 0;
    padding: 0;
    gap: 8px;
}

.topnav-menu ul li a {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--text-secondary);
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.topnav-menu ul li a i {
    margin-right: 8px;
    font-size: 16px;
}

.topnav-menu ul li a:hover {
    background-color: #f3ebf9;
    color: var(--primary-color);
}

.topnav-menu ul li a.active {
    background-color: var(--primary-light);
    color: var(--primary-color);
}

/* Profile Section */
.topnav-profile {
    position: relative;
}

.profile-button {
    display: flex;
    align-items: center;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    gap: 8px;
    border-radius: 8px;
    transition: background-color 0.3s;
}

.profile-button:hover {
    background-color: #f3ebf9;
}

.profile-button .profile-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    overflow: hidden;
}

.profile-button .profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-button .profile-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f0f0;
}

.profile-button .profile-placeholder i {
    font-size: 16px;
    color: #999;
}

.profile-name {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-primary);
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Profile Dropdown Menu */
.profile-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 10px);
    width: 200px;
    background-color: var(--bg-primary);
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: none;
    z-index: 100;
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.profile-menu a {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    text-decoration: none;
    color: var(--text-primary);
    transition: background-color 0.2s;
    gap: 8px;
}

.profile-menu a:hover {
    background-color: #f3ebf9;
}

.profile-menu a i {
    width: 16px;
    text-align: center;
}

.menu-divider {
    height: 1px;
    background-color: var(--border-color);
    margin: 4px 0;
}

.logout-item {
    color: #dc3545 !important;
}

.logout-item:hover {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

/* Responsive Design */
@media screen and (max-width: 768px) {
    .topnav-menu span {
        display: none;
    }
    
    .topnav-menu ul li a i {
        margin-right: 0;
        font-size: 18px;
    }
    
    .profile-name {
        display: none;
    }
}

@media screen and (max-width: 576px) {
    .topnav {
        padding: 0 10px;
    }
    
    .logo-image {
        height: 32px; /* Smaller logo on mobile */
    }
    
    .topnav-menu ul {
        gap: 0;
    }
    
    .topnav-menu ul li a {
        padding: 8px;
    }
}
</style>

<script>
// Add active class to current page link
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.topnav-menu ul li a');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
    
    // Add class to body for proper content positioning
    document.body.classList.add('has-topnav');
});

// Toggle profile menu
function toggleProfileMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('profileMenu');
    const isVisible = menu.style.display === 'block';
    
    // Close any open menus first
    closeAllMenus();
    
    // Toggle this menu
    if (!isVisible) {
        menu.style.display = 'block';
    }
}

// Close all menus
function closeAllMenus() {
    const menus = document.querySelectorAll('.profile-menu');
    menus.forEach(menu => {
        menu.style.display = 'none';
    });
}

// Close menu when clicking outside
document.addEventListener('click', function() {
    closeAllMenus();
});

// Secure logout function
function handleLogout() {
    // Clear any local/session storage data
    localStorage.clear();
    sessionStorage.clear();
    
    // Redirect to logout script
    window.location.href = 'logout.php';
}

// Prevent back button after logout
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        // Page was restored from the back-forward cache
        window.location.reload();
    }
});
</script>