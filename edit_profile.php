<?php
// filepath: c:\xampp\htdocs\log\edit_profile.php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

$success_message = '';
$error_message = '';

// Get current user profile data
try {
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
    
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If profile doesn't exist, redirect to appropriate info page
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Update user table
        $stmt = $conn->prepare("
            UPDATE user
            SET user_name = :user_name, email = :email
            WHERE user_id = :user_id
        ");
        
        $stmt->execute([
            ':user_name' => $_POST['user_name'],
            ':email' => $_POST['email'],
            ':user_id' => $_SESSION['user_id']
        ]);
        
        // Update role-specific table
        if ($_SESSION['role'] == ROLE_STUDENT) {
            $stmt = $conn->prepare("
                UPDATE student
                SET 
                    full_name = :full_name,
                    matric_no = :matric_no,
                    phone_number = :phone_number,
                    institution = :institution,
                    school = :school,
                    programme = :programme,
                    home_address = :home_address,
                    practicum_start_date = :practicum_start_date,
                    practicum_end_date = :practicum_end_date
                WHERE student_id = :user_id
            ");
            
            // Calculate practicum duration in months
            $start_date = new DateTime($_POST['practicum_start_date']);
            $end_date = new DateTime($_POST['practicum_end_date']);
            $interval = $start_date->diff($end_date);
            $duration_months = ($interval->y * 12) + $interval->m + ($interval->d > 15 ? 1 : 0);
            
            $stmt->execute([
                ':full_name' => $_POST['full_name'],
                ':matric_no' => $_POST['matric_no'],
                ':phone_number' => $_POST['phone_number'],
                ':institution' => $_POST['institution'],
                ':school' => $_POST['school'],
                ':programme' => $_POST['programme'],
                ':home_address' => $_POST['home_address'],
                ':practicum_start_date' => $_POST['practicum_start_date'],
                ':practicum_end_date' => $_POST['practicum_end_date'],
                ':user_id' => $_SESSION['user_id']
            ]);
            
            // Update practicum duration separately
            $stmt = $conn->prepare("
                UPDATE student
                SET practicum_duration = :practicum_duration
                WHERE student_id = :user_id
            ");
            
            $stmt->execute([
                ':practicum_duration' => $duration_months,
                ':user_id' => $_SESSION['user_id']
            ]);
            
        } else {
            $stmt = $conn->prepare("
                UPDATE supervisor
                SET 
                    company_name = :company_name,
                    company_address = :company_address,
                    supervisor_name = :supervisor_name,
                    designation = :designation,
                    contact_number = :contact_number,
                    supervisor_email = :supervisor_email
                WHERE supervisor_id = :user_id
            ");
            
            $stmt->execute([
                ':company_name' => $_POST['company_name'],
                ':company_address' => $_POST['company_address'],
                ':supervisor_name' => $_POST['supervisor_name'],
                ':designation' => $_POST['designation'],
                ':contact_number' => $_POST['contact_number'],
                ':supervisor_email' => $_POST['supervisor_email'],
                ':user_id' => $_SESSION['user_id']
            ]);
        }
        
        // Check if password change is requested
        if (!empty($_POST['new_password'])) {
            $stmt = $conn->prepare("
                UPDATE user
                SET password = :password
                WHERE user_id = :user_id
            ");
            
            $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt->execute([
                ':password' => $hashed_password,
                ':user_id' => $_SESSION['user_id']
            ]);
        }
        
        $conn->commit();
        $_SESSION['username'] = $_POST['user_name'];
        $success_message = "Profile updated successfully!";
        
        // Refresh profile data
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
        
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/auth_form.css">
    <style>
        .main-content {
            max-width: 800px;
            padding: 20px;
            margin: 0 auto;
        }
        
        .form-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: var(--box-shadow);
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 100px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .button-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }
        
        .update-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            font-size: 16px;
        }
        
        .update-btn:hover {
            background-color: var(--primary-hover);
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .back-btn {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .back-btn i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <div class="profile-header">
            <h2>Edit Profile</h2>
            <a href="view_profile.php" class="back-btn">
                <i class="fi fi-rr-arrow-left"></i>
                Back to Profile
            </a>
        </div>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form action="edit_profile.php" method="post" class="form-container">
            <!-- Account Information -->
            <div class="form-section">
                <h3 class="section-title">Account Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="user_name">Username</label>
                        <input type="text" id="user_name" name="user_name" value="<?php echo htmlspecialchars($profile['user_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                    </div>
                </div>
            </div>
            
            <!-- Password Change (Optional) -->
            <div class="form-section">
                <h3 class="section-title">Change Password (Optional)</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                    </div>
                </div>
            </div>
            
            <?php if ($_SESSION['role'] == ROLE_STUDENT): ?>
            <!-- Student Profile Information -->
            <div class="form-section">
                <h3 class="section-title">Personal Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($profile['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="matric_no">Matric Number</label>
                        <input type="text" id="matric_no" name="matric_no" value="<?php echo htmlspecialchars($profile['matric_no']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number']); ?>" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="home_address">Home Address</label>
                        <textarea id="home_address" name="home_address" required><?php echo htmlspecialchars($profile['home_address']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">Academic Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="institution">Institution</label>
                        <input type="text" id="institution" name="institution" value="<?php echo htmlspecialchars($profile['institution']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="school">School/Faculty</label>
                        <input type="text" id="school" name="school" value="<?php echo htmlspecialchars($profile['school']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="programme">Programme</label>
                        <input type="text" id="programme" name="programme" value="<?php echo htmlspecialchars($profile['programme']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">Practicum Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="practicum_start_date">Start Date</label>
                        <input type="date" id="practicum_start_date" name="practicum_start_date" value="<?php echo htmlspecialchars($profile['practicum_start_date']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="practicum_end_date">End Date</label>
                        <input type="date" id="practicum_end_date" name="practicum_end_date" value="<?php echo htmlspecialchars($profile['practicum_end_date']); ?>" required>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Supervisor Profile Information -->
            <div class="form-section">
                <h3 class="section-title">Personal Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="supervisor_name">Full Name</label>
                        <input type="text" id="supervisor_name" name="supervisor_name" value="<?php echo htmlspecialchars($profile['supervisor_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="designation">Designation</label>
                        <input type="text" id="designation" name="designation" value="<?php echo htmlspecialchars($profile['designation']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($profile['contact_number']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="supervisor_email">Work Email</label>
                        <input type="email" id="supervisor_email" name="supervisor_email" value="<?php echo htmlspecialchars($profile['supervisor_email']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">Company Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="company_name">Company Name</label>
                        <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($profile['company_name']); ?>" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="company_address">Company Address</label>
                        <textarea id="company_address" name="company_address" required><?php echo htmlspecialchars($profile['company_address']); ?></textarea>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="button-container">
                <a href="<?php echo $_SESSION['role'] == ROLE_STUDENT ? 'main_menu.php' : 'sv_main.php'; ?>" class="btn">Cancel</a>
                <button type="submit" class="update-btn">Update Profile</button>
            </div>
        </form>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Validate practicum dates
        document.getElementById('practicum_end_date')?.addEventListener('input', function() {
            const startDate = document.getElementById('practicum_start_date').value;
            const endDate = this.value;
            
            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                this.setCustomValidity('End date must be after start date');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>