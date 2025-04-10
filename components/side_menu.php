<?php
require_once 'includes/session_check.php';
?>
<div class="sidenav">
    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <?php if (isset($_SESSION['needs_profile_completion'])): ?>
            <div class="profile-alert">Please complete your profile</div>
        <?php endif; ?>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    <nav>
        <ul>
            <?php if ($_SESSION['role'] == ROLE_STUDENT): ?>
                <li><a href="main_menu.php">Explore</a></li>
                <li><a href="logbook.php">My Logbook</a></li>
                <li><a href="portfolio.php">My Portfolio</a></li>
                <li><a href="view_practicum_info.php">Practicum Info</a></li>
            <?php elseif ($_SESSION['role'] == ROLE_SUPERVISOR): ?>
                <li><a href="sv_main.php">Interns</a></li>
                <li><a href="sv_add_student.php">Add Interns</a></li>
                <li><a href="view_practicum_info.php">Company Info</a></li>
            <?php endif; ?>
        </ul>
    </nav>
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
}

.sidenav .user-info {
    padding: 15px;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 20px;
}

.sidenav .user-info span {
    display: block;
    margin-bottom: 10px;
    color: #333;
    font-weight: 500;
}

.sidenav .logout-btn {
    color: #dc3545;
    text-decoration: none;
    font-size: 14px;
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
    display: block;
    transition: all 0.3s ease;
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
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
}

/* Add this to the existing styles */
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