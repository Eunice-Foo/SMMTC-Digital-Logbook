<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['new_user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != ROLE_SUPERVISOR) {
    header("Location: signup.php");
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
            ':supervisor_id' => $_SESSION['new_user_id'],
            ':company_name' => $_POST['company_name'],
            ':company_address' => $_POST['company_address'],
            ':supervisor_name' => $_POST['supervisor_name'],
            ':designation' => $_POST['designation'],
            ':contact_number' => $_POST['contact_number'],
            ':supervisor_email' => $_POST['supervisor_email']
        ]);
        
        header("Location: login.php");
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
    <title>Company Supervisor Information</title>
    <link rel="stylesheet" href="css/theme.css">
</head>
<body>
    <div class="container">
        <h2>Company Supervisor Information</h2>
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