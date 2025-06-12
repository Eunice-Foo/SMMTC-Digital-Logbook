<?php
// filepath: c:\xampp\htdocs\log\edit_profile.php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'includes/file_naming.php';
require_once 'includes/image_converter.php';
require_once 'components/toast_notification.php';

$success_message = '';
$error_message = '';

// Get current user profile data
try {
    if ($_SESSION['role'] == ROLE_STUDENT) {
        $stmt = $conn->prepare("
            SELECT 
                s.*,
                u.user_name,
                u.email,
                u.profile_picture
            FROM student s
            INNER JOIN user u ON s.student_id = u.user_id
            WHERE s.student_id = :user_id
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT 
                sv.*,
                u.user_name,
                u.email,
                u.profile_picture
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
        
        // Handle profile picture upload
        if (!empty($_FILES['profile_picture']['name'])) {
            $tmp_name = $_FILES['profile_picture']['tmp_name'];
            $original_filename = $_FILES['profile_picture']['name'];
            $file_type = $_FILES['profile_picture']['type'];
            $file_error = $_FILES['profile_picture']['error'];
            
            // Check for upload errors
            if ($file_error !== UPLOAD_ERR_OK) {
                $error_message = "Upload error: " . 
                    ($file_error === UPLOAD_ERR_INI_SIZE ? "File too large (exceeds server limit)" : "Unknown error");
            } 
            // Validate file is an image
            elseif (!in_array($file_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $error_message = "Profile picture must be an image (JPG, PNG, GIF, or WebP)";
            } else {
                // Create directories if they don't exist
                $profile_dir = "uploads/profile/";
                $thumbs_dir = "uploads/profile/thumbnails/";
                
                if (!file_exists($profile_dir)) {
                    mkdir($profile_dir, 0755, true);
                }
                if (!file_exists($thumbs_dir)) {
                    mkdir($thumbs_dir, 0755, true);
                }
                
                // Generate unique filename
                $unique_filename = generateUniqueFilename($original_filename, $_SESSION['user_id'] . '_profile');
                $upload_path = $profile_dir . $unique_filename;
                
                // Move uploaded file
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    // Create WebP version and thumbnails
                    $webp_path = null;
                    
                    try {
                        if ($file_type !== 'image/webp') {
                            $webp_path = convertToWebP($upload_path);
                        }
                        
                        // Create profile thumbnails
                        createProfileThumbnail($upload_path, $unique_filename);
                        
                        // Update database with new profile picture
                        $profile_pic_filename = $webp_path ? basename($webp_path) : $unique_filename;
                        
                        $stmt = $conn->prepare("
                            UPDATE user
                            SET profile_picture = :profile_picture
                            WHERE user_id = :user_id
                        ");
                        
                        $stmt->execute([
                            ':profile_picture' => $profile_pic_filename,
                            ':user_id' => $_SESSION['user_id']
                        ]);
                        
                        // Update the session variable to reflect change immediately
                        $user_profile_picture = $profile_pic_filename;
                    } catch (Exception $e) {
                        $error_message = "Image processing error: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Failed to upload profile picture. Please check file permissions.";
                }
            }
        }
        
        // Handle signature image upload (for supervisors only)
        if ($_SESSION['role'] == ROLE_SUPERVISOR && !empty($_FILES['signature_image']['name'])) {
            $tmp_name = $_FILES['signature_image']['tmp_name'];
            $original_filename = $_FILES['signature_image']['name'];
            $file_type = $_FILES['signature_image']['type'];
            $file_error = $_FILES['signature_image']['error'];
            
            // Check for upload errors
            if ($file_error !== UPLOAD_ERR_OK) {
                $error_message = "Signature upload error: " . 
                    ($file_error === UPLOAD_ERR_INI_SIZE ? "File too large (exceeds server limit)" : "Unknown error");
            } 
            // Validate file is an image
            elseif (!in_array($file_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $error_message = "Signature must be an image (JPG, PNG, GIF, or WebP)";
            } else {
                // Create directory if it doesn't exist
                $signature_dir = "uploads/signatures/";
                
                if (!file_exists($signature_dir)) {
                    mkdir($signature_dir, 0755, true);
                }
                
                // Generate unique filename
                $unique_filename = generateUniqueFilename($original_filename, $_SESSION['user_id'] . '_signature');
                $upload_path = $signature_dir . $unique_filename;
                
                // Move uploaded file
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    // Update database with new signature image
                    $stmt = $conn->prepare("
                        UPDATE supervisor
                        SET signature_image = :signature_image
                        WHERE supervisor_id = :user_id
                    ");
                    
                    $stmt->execute([
                        ':signature_image' => $unique_filename,
                        ':user_id' => $_SESSION['user_id']
                    ]);
                } else {
                    $error_message = "Failed to upload signature. Please check file permissions.";
                }
            }
        }
        
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
                    u.email,
                    u.profile_picture
                FROM student s
                INNER JOIN user u ON s.student_id = u.user_id
                WHERE s.student_id = :user_id
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT 
                    sv.*,
                    u.user_name,
                    u.email,
                    u.profile_picture
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

// Initialize toast notifications
initializeToast();

// Show notifications if needed
if ($success_message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showSuccessToast('<?php echo $success_message; ?>', 'Profile Updated');
        });
    </script>
<?php endif; ?>

<?php if ($error_message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showErrorToast('<?php echo $error_message; ?>', 'Error');
        });
    </script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" width="device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/auth_form.css">
    <link rel="stylesheet" href="css/edit_profile.css">
    <link rel="stylesheet" href="css/cancel_modal.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <link rel="stylesheet" href="css/form_indicators.css">
    <style>
        /* Add this to your CSS in edit_profile.css */
        .signature-upload-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .current-signature {
            width: 250px;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f9f9f9;
        }

        .current-signature img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .signature-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: #999;
            font-size: 14px;
            font-style: italic;
        }

        .signature-upload {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
    </style>
</head>
<body>
    <?php include 'components/topnav.php'; ?>
    
    <div class="main-content">
        <div class="profile-header">
            <h1>Edit Profile</h1>
            <a href="view_profile.php" class="back-btn">
                <i class="fi fi-rr-arrow-left"></i>
                Back to Profile
            </a>
        </div>
        
        <form action="edit_profile.php" method="post" class="form-container" enctype="multipart/form-data">
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
            
            <!-- Profile Picture Upload -->
            <div class="form-section">
                <h3 class="section-title">Profile Picture</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <div class="profile-upload-container">
                            <div class="current-profile-picture">
                                <?php if (!empty($profile['profile_picture'])): ?>
                                    <img src="<?php echo getProfileImagePath($profile['profile_picture'], 'md'); ?>" alt="Current profile picture">
                                <?php else: ?>
                                    <div class="profile-placeholder">
                                        <i class="fi fi-rr-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="profile-upload">
                                <label for="profile_picture" class="profile-upload-label">
                                    <i class="fi fi-rr-camera"></i>
                                    <span>Upload new picture</span>
                                </label>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="profile-upload-input">
                                <small class="upload-hint">Recommended size: Square image at least 150x150px</small>
                            </div>
                        </div>
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
                        <label for="full_name">Full Name<span class="required-indicator">*</span></label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo $user_data['full_name']; ?>" required>
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

            <!-- Signature Upload -->
            <div class="form-section">
                <h3 class="section-title">Signature</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <div class="signature-upload-container">
                            <div class="current-signature">
                                <?php if (!empty($profile['signature_image'])): ?>
                                    <img src="uploads/signatures/<?php echo htmlspecialchars($profile['signature_image']); ?>" alt="Current signature">
                                <?php else: ?>
                                    <div class="signature-placeholder">
                                        <span>No signature uploaded</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="signature-upload">
                                <label for="signature_image" class="profile-upload-label">
                                    <i class="fi fi-rr-edit-alt"></i>
                                    <span>Upload signature</span>
                                </label>
                                <input type="file" id="signature_image" name="signature_image" accept="image/*" class="profile-upload-input">
                                <small class="upload-hint">Upload a clear image of your signature (PNG or JPEG)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="button-container">
                <a href="javascript:void(0)" class="btn cancel-btn">Cancel</a>
                <button type="submit" class="update-btn">Update Profile</button>
            </div>
        </form>
    </div>

    <!-- Include JavaScript file -->
    <script src="js/edit_profile.js"></script>
    <script src="js/cancel_confirmation.js" defer></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add cancel confirmation for the Cancel button
        const cancelBtn = document.querySelector('.cancel-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(event) {
                event.preventDefault();
                confirmCancel('view_profile.php');
            });
        }
    });
    </script>
</body>
</html>