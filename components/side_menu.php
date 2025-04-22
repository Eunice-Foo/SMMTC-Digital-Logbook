<?php
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

<div class="sidenav">
    <div class="user-section">
        <div class="user-profile">
            <a href="view_profile.php" class="profile-avatar">
                <?php if (!empty($user_profile_picture)): ?>
                    <img src="<?php echo getProfileImagePath($user_profile_picture); ?>" alt="Profile Picture">
                <?php else: ?>
                    <div class="profile-placeholder">
                        <i class="fi fi-rr-user"></i>
                    </div>
                <?php endif; ?>
            </a>
            <div class="user-info">
                <div class="full-name"><?php echo htmlspecialchars($user_full_name); ?></div>
                <div class="role"><?php echo $_SESSION['role'] == ROLE_STUDENT ? 'Student' : 'Supervisor'; ?></div>
            </div>
        </div>
    </div>
    
    <nav>
        <ul>
            <?php if ($_SESSION['role'] == ROLE_STUDENT): ?>
                <li>
                    <a href="main_menu.php">
                        <i class="fi fi-rr-layers"></i>
                        Explore
                    </a>
                </li>
                <li>
                    <a href="logbook.php">
                        <i class="fi fi-rr-audit-alt"></i>
                        My Logbook
                    </a>
                </li>
                <li>
                    <a href="portfolio.php">
                        <i class="fi fi-rr-objects-column"></i>
                        My Portfolio
                    </a>
                </li>
                <li>
                    <a href="view_practicum_info.php">
                        <i class="fi fi-rr-circle-user"></i>
                        Practicum Info
                    </a>
                </li>
            <?php elseif ($_SESSION['role'] == ROLE_SUPERVISOR): ?>
                <li>
                    <a href="sv_main.php">
                        <i class="fi fi-rr-users"></i>
                        Interns
                    </a>
                </li>
                <li>
                    <a href="view_practicum_info.php">
                        <i class="fi fi-rr-building"></i>
                        Company Info
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="logout-container">
        <a href="javascript:void(0)" class="logout-btn" onclick="handleLogout()">
            <i class="fi fi-rr-sign-out-alt"></i>
            Logout
        </a>
    </div>
</div>

<style>
.sidenav {
    height: 100%;
    width: 250px;
    position: fixed;
    z-index: 1;
    top: 0;
    left: 0;
    background-color: var(--bg-primary); /* Make sure this is using the primary background color */
    overflow-x: hidden;
    padding-top: 20px;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.sidenav nav ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.sidenav nav ul li {
    padding: 0;
}

.sidenav nav ul li a {
    padding: 12px 24px;  /* Updated padding */
    margin: 6px 12px;    /* Added margin */
    text-decoration: none;
    font-size: 16px;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    border-radius: 8px;  /* Added border radius */
}

.sidenav nav ul li a i {
    margin-right: 16px;
    font-size: 20px;
    width: 20px;
    color: var(--text-secondary);
}

.sidenav nav ul li a:hover {
    background-color: #e9ecef;
    color: var(--primary-color);
}

.sidenav nav ul li a:hover i {
    color: var(--primary-color);
}

/* Active link style */
.sidenav nav ul li a.active {
    background-color: rgba(76, 175, 80, 0.1);
    color: var(--primary-color);
}

.sidenav nav ul li a.active i {
    color: var(--primary-color);
}

.user-section {
    padding: 20px 15px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 15px;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 15px;
}

.profile-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    display: block;
    transition: all 0.3s ease;
    border: 3px solid var(--border-color); /* Changed from rgba(255,255,255,0.2) to border-color */
    flex-shrink: 0;
}

.profile-avatar:hover {
    transform: scale(1.05);
    border-color: var(--primary-color);
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f0f0;
}

.profile-placeholder i {
    font-size: 28px;
    color: #999;
}

.user-info {
    flex-grow: 1;
    overflow: hidden;
}

.full-name {
    font-weight: 500;
    color: var(--text-primary); /* Changed from white to text-primary */
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
    font-size: 16px;
}

.role {
    color: var(--text-secondary); /* Changed from rgba(255,255,255,0.7) to text-secondary */
    font-size: 14px;
}

.sidenav nav {
    flex: 1;
    overflow-y: auto;
}

.logout-container {
    padding: 20px;
    border-top: 1px solid #dee2e6;
    text-align: center;
}

.sidenav .logout-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #dc3545;
    text-decoration: none;
    font-size: 14px;
    padding: 10px 20px;
    border-radius: 20px;
    transition: all 0.3s ease;
}

.sidenav .logout-btn:hover {
    background-color: #dc3545;
    color: white;
}

.sidenav .logout-btn i {
    font-size: 16px;
}

/* Responsive design */
@media screen and (max-width: 768px) {
    .sidenav {
        width: 200px;
    }
    
    .main-content {
        margin-left: 200px;
        width: calc(100% - 200px);
    }
}

@media screen and (max-width: 576px) {
    .sidenav {
        width: 100%;
        height: auto;
        position: relative;
    }
    
    .logout-container {
        position: relative;
        padding: 15px;
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
}
</style>

<script>
// Add active class to current page link
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.sidenav nav ul li a');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
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