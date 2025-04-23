<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

// Only allow access if user is a supervisor
if ($_SESSION['role'] != ROLE_SUPERVISOR) {
    header("Location: main_menu.php");
    exit();
}

// Check if the supervisor info already exists
$stmt = $conn->prepare("SELECT * FROM supervisor WHERE supervisor_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
if ($stmt->rowCount() > 0 && !isset($_SESSION['needs_profile_completion'])) {
    // Profile already complete, redirect
    header("Location: sv_main.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $stmt = $conn->prepare("
            INSERT INTO supervisor 
            (supervisor_id, company_name, company_address, supervisor_name, 
             designation, contact_number, supervisor_email)
            VALUES 
            (:supervisor_id, :company_name, :company_address, :supervisor_name,
             :designation, :contact_number, :supervisor_email)
        ");
        
        $stmt->execute([
            ':supervisor_id' => $_SESSION['user_id'],
            ':company_name' => $_POST['company_name'],
            ':company_address' => $_POST['company_address'],
            ':supervisor_name' => $_POST['supervisor_name'],
            ':designation' => $_POST['designation'],
            ':contact_number' => $_POST['contact_number'],
            ':supervisor_email' => $_POST['supervisor_email']
        ]);
        
        // Mark profile as completed
        unset($_SESSION['needs_profile_completion']);
        
        // Redirect to supervisor main page
        header("Location: sv_main.php");
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
</head>
<body>
    <?php include 'components/topnav.php'; ?>
    
    <div class="container">
        <h2>Complete Your Company Profile</h2>
        <p>Please provide the following information to complete your registration.</p>
        <form action="supervisor_info.php" method="post">
            <div class="form-group">
                <label for="company_name">Company Name:</label>
                <input type="text" id="company_name" name="company_name" required>
            </div>

            <div class="form-group">
                <label for="company_address">Company Address:</label>
                <textarea id="company_address" name="company_address" required></textarea>
            </div>

            <div class="form-group">
                <label for="supervisor_name">Supervisor Name:</label>
                <input type="text" id="supervisor_name" name="supervisor_name" required>
            </div>

            <div class="form-group">
                <label for="designation">Designation:</label>
                <input type="text" id="designation" name="designation" required>
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number:</label>
                <input type="tel" id="contact_number" name="contact_number" required>
            </div>

            <div class="form-group">
                <label for="supervisor_email">Email Address:</label>
                <input type="email" id="supervisor_email" name="supervisor_email" required>
            </div>

            <button type="submit">Submit</button>
        </form>
    </div>
</body>
</html>