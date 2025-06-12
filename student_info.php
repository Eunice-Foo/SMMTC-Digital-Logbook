<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

// Get user email from the database
$email = '';
try {
    $stmt = $conn->prepare("SELECT email FROM user WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $email = $user['email'];
    }
} catch(PDOException $e) {
    // Silently handle any errors
}

// Only allow access if user is a student
if ($_SESSION['role'] != ROLE_STUDENT) {
    header("Location: main_menu.php");
    exit();
}

// Check if the student info already exists
$stmt = $conn->prepare("SELECT * FROM student WHERE student_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
if ($stmt->rowCount() > 0 && !isset($_SESSION['needs_profile_completion'])) {
    // Profile already complete, redirect to main menu
    header("Location: main_menu.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $stmt = $conn->prepare("
            INSERT INTO student 
            (student_id, matric_no, full_name, phone_number, institution, school, 
            programme, home_address, practicum_start_date, practicum_end_date, practicum_duration)
            VALUES 
            (:student_id, :matric_no, :full_name, :phone_number, :institution, :school, 
            :programme, :home_address, :practicum_start_date, :practicum_end_date, :practicum_duration)
        ");
        
        // Calculate duration in months
        $start_date = new DateTime($_POST['practicum_start_date']);
        $end_date = new DateTime($_POST['practicum_end_date']);
        $interval = $start_date->diff($end_date);
        $duration_months = ($interval->y * 12) + $interval->m;

        $stmt->execute([
            ':student_id' => $_SESSION['user_id'],
            ':matric_no' => $_POST['matric_no'],
            ':full_name' => $_POST['full_name'],
            ':phone_number' => $_POST['phone_number'],
            ':institution' => $_POST['institution'],
            ':school' => $_POST['school'],
            ':programme' => $_POST['programme'],
            ':home_address' => $_POST['home_address'],
            ':practicum_start_date' => $_POST['practicum_start_date'],
            ':practicum_end_date' => $_POST['practicum_end_date'],
            ':practicum_duration' => $duration_months
        ]);
        
        // Mark profile as completed
        unset($_SESSION['needs_profile_completion']);
        
        // Redirect to main menu
        header("Location: main_menu.php");
        exit();
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/auth_form.css">
    <link rel="stylesheet" href="css/form_indicators.css">
</head>
<body>
    <?php include 'components/topnav.php'; ?>
    
    <div class="main-content student-info-container">
        <h1>Complete Your Profile</h1>
        <p>Please provide the following information to complete your registration.</p>
        
        <form action="student_info.php" method="post">
            <!-- Matric Number and Full Name (1:5 ratio) -->
            <div class="form-row name-row">
                <div class="form-group">
                    <label for="matric_no">Matric Number<span class="required-indicator">*</span></label>
                    <input type="text" id="matric_no" name="matric_no" required>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name<span class="required-indicator">*</span></label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
            </div>

            <!-- Email and Phone Number -->
            <div class="form-row equal-cols">
                <div class="form-group">
                    <label for="email">Email Address<span class="required-indicator">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number<span class="required-indicator">*</span></label>
                    <input type="tel" id="phone_number" name="phone_number" required>
                </div>
            </div>
            
            <!-- Home Address -->
            <div class="form-group">
                <label for="home_address">Home Address<span class="required-indicator">*</span></label>
                <textarea id="home_address" name="home_address" required></textarea>
            </div>

            <!-- Institution Info -->
            <div class="form-group">
                <label for="institution">Institution<span class="required-indicator">*</span></label>
                <input type="text" id="institution" name="institution" list="institution-list" value="Universiti Utara Malaysia" required>
                <datalist id="institution-list">
                    <option value="Universiti Utara Malaysia">
                </datalist>
            </div>

            <div class="form-group">
                <label for="school">School<span class="required-indicator">*</span></label>
                <input type="text" id="school" name="school" list="school-list" required>
                <datalist id="school-list">
                    <option value="School of Multimedia Technology & Communication (SMMTC)">
                </datalist>
            </div>

            <div class="form-group">
                <label for="programme">Programme<span class="required-indicator">*</span></label>
                <input type="text" id="programme" name="programme" list="programme-list" required>
                <datalist id="programme-list">
                    <option value="Bachelor of Science (Multimedia) with Honours">
                </datalist>
            </div>

            <!-- Practicum Dates -->
            <div class="form-row equal-cols">
                <div class="form-group">
                    <label for="practicum_start_date">Practicum Start Date<span class="required-indicator">*</span></label>
                    <input type="date" id="practicum_start_date" name="practicum_start_date" required>
                </div>

                <div class="form-group">
                    <label for="practicum_end_date">Practicum End Date<span class="required-indicator">*</span></label>
                    <input type="date" id="practicum_end_date" name="practicum_end_date" required>
                </div>
            </div>

            <button type="submit" class="submit-button">
                <i class="fi fi-br-check"></i> Submit
            </button>
        </form>
    </div>

    <!-- Add this script at the bottom of student_info.php before </body> -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Create suggestion containers
        const fields = ['institution', 'school', 'programme'];
        
        fields.forEach(field => {
            const input = document.getElementById(field);
            const datalist = document.getElementById(field + '-list');
            const wrapper = document.createElement('div');
            wrapper.className = 'suggestions-container';
            
            // Create suggestion dropdown
            const suggestionsDiv = document.createElement('div');
            suggestionsDiv.className = 'suggestion-dropdown';
            suggestionsDiv.id = field + '-suggestions';
            
            // Get options from datalist
            const options = Array.from(datalist.options).map(opt => opt.value);
            
            // Replace input with wrapper + input + suggestions
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            wrapper.appendChild(suggestionsDiv);
            
            // Add input event
            input.addEventListener('input', function() {
                const value = this.value.toLowerCase();
                
                if (value.length > 0) {
                    // Filter options
                    const filteredOptions = options.filter(opt => 
                        opt.toLowerCase().includes(value)
                    );
                    
                    // Show suggestions
                    if (filteredOptions.length > 0) {
                        suggestionsDiv.innerHTML = filteredOptions
                            .map(opt => `<div class="suggestion-item">${opt}</div>`)
                            .join('');
                        suggestionsDiv.style.display = 'block';
                    } else {
                        suggestionsDiv.style.display = 'none';
                    }
                } else {
                    suggestionsDiv.style.display = 'none';
                }
            });
            
            // Add click event for suggestions
            suggestionsDiv.addEventListener('click', function(e) {
                if (e.target.classList.contains('suggestion-item')) {
                    input.value = e.target.textContent;
                    suggestionsDiv.style.display = 'none';
                }
            });
            
            // Close suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!wrapper.contains(e.target)) {
                    suggestionsDiv.style.display = 'none';
                }
            });
        });
    });
    </script>
</body>
</html>