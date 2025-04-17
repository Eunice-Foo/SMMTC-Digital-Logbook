<?php
require_once 'includes/session_check.php';
?>
<!-- Add the Flaticon CSS link -->
<link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.1.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
<link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.1.0/uicons-regular-straight/css/uicons-regular-straight.css'>

<div class="sidenav">
    <div class="user-info">
        <a href="view_profile.php" class="profile-link">
            <div class="profile-picture">
                <i class="fi fi-rr-user"></i>
            </div>
        </a>
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <?php if (isset($_SESSION['needs_profile_completion'])): ?>
            <div class="profile-alert">Please complete your profile</div>
        <?php endif; ?>
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
                    <a href="sv_add_student.php">
                        <i class="fi fi-rr-user-add"></i>
                        Add Interns
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
        <a href="logout.php" class="logout-btn">
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
    background-color: #f8f9fa;
    overflow-x: hidden;
    padding-top: 20px;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.sidenav .user-info {
    padding: 15px;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.profile-link {
    display: inline-block;
    text-decoration: none;
    margin-bottom: 10px;
}

.profile-picture {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: 3px solid transparent;
}

.profile-picture:hover {
    background-color: #dee2e6;
    border-color: var(--primary-color);
}

.profile-picture i {
    font-size: 32px;
    color: #6c757d;
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

.sidenav nav ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.sidenav nav ul li {
    padding: 0;
}

.sidenav nav ul li a {
    padding: 15px 25px;
    text-decoration: none;
    font-size: 16px;
    color: #333;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.sidenav nav ul li a i {
    margin-right: 12px;
    font-size: 20px;
    width: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sidenav nav ul li a:hover {
    background-color: #e9ecef;
    color: #007bff;
}

/* Active link style */
.sidenav nav ul li a.active {
    background-color: #007bff;
    color: white;
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

.profile-alert {
    background-color: #ffeeba;
    color: #856404;
    padding: 5px 10px;
    border-radius: 4px;
    margin: 10px 0;
    font-size: 12px;
    text-align: center;
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
</script>