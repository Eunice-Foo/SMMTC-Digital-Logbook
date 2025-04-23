<?php
// filepath: c:\xampp\htdocs\log\view_profile.php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];

try {
    // Get user profile data
    if ($_SESSION['role'] == ROLE_STUDENT) {
        $stmt = $conn->prepare("
            SELECT 
                s.*,
                u.user_name,
                u.email
            FROM student s
            INNER JOIN user u ON s.student_id = u.user_id
            WHERE s.student_id = :user_id
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT 
                sv.*,
                u.user_name,
                u.email
            FROM supervisor sv
            INNER JOIN user u ON sv.supervisor_id = u.user_id
            WHERE sv.supervisor_id = :user_id
        ");
    }
    
    $stmt->execute([':user_id' => $user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If profile doesn't exist, redirect to profile creation
    if (!$profile) {
        if ($_SESSION['role'] == ROLE_STUDENT) {
            header('Location: student_info.php');
        } else {
            header('Location: supervisor_info.php');
        }
        exit();
    }

} catch(PDOException $e) {
    $error_message = "Error fetching profile: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/profile.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'components/topnav.php'; ?>
    
    <div class="main-content">
        <div class="profile-header">
            <h2>My Profile</h2>
            <a href="edit_profile.php" class="edit-profile-btn">
                <i class="fi fi-rr-edit"></i>
                Edit Profile
            </a>
        </div>
        
        <div class="profile-card">
            <div class="profile-section basic-info">
                <div class="profile-picture large">
                    <i class="fi fi-rr-user"></i>
                </div>
                <div class="user-details">
                    <h1><?php echo $_SESSION['role'] == ROLE_STUDENT ? 
                        htmlspecialchars($profile['full_name']) : 
                        htmlspecialchars($profile['supervisor_name']); ?></h1>
                    <p class="username">@<?php echo htmlspecialchars($profile['user_name']); ?></p>
                    <p class="email"><?php echo htmlspecialchars($profile['email']); ?></p>
                </div>
            </div>
            
            <?php if ($_SESSION['role'] == ROLE_STUDENT): ?>
            <div class="profile-section">
                <h3>Personal Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Matric Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['matric_no']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['phone_number']); ?></span>
                    </div>
                    <div class="info-item full-width">
                        <span class="info-label">Home Address</span>
                        <span class="info-value"><?php echo nl2br(htmlspecialchars($profile['home_address'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="profile-section">
                <h3>Academic Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Institution</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['institution']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">School/Faculty</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['school']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Programme</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['programme']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="profile-section">
                <h3>Practicum Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Start Date</span>
                        <span class="info-value"><?php echo date('d M Y', strtotime($profile['practicum_start_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">End Date</span>
                        <span class="info-value"><?php echo date('d M Y', strtotime($profile['practicum_end_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Duration</span>
                        <span class="info-value"><?php echo $profile['practicum_duration']; ?> months</span>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="profile-section">
                <h3>Professional Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Designation</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['designation']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Contact Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['contact_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Work Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['supervisor_email']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="profile-section">
                <h3>Company Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Company Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['company_name']); ?></span>
                    </div>
                    <div class="info-item full-width">
                        <span class="info-label">Company Address</span>
                        <span class="info-value"><?php echo nl2br(htmlspecialchars($profile['company_address'])); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/profile.js"></script>
</body>
</html>