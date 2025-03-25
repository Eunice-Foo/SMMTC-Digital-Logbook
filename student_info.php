<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['new_user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != ROLE_STUDENT) {
    header("Location: signup.php");
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
            ':student_id' => $_SESSION['new_user_id'],
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
        
        // Clear session variables
        unset($_SESSION['new_user_id']);
        unset($_SESSION['role']);
        
        // Redirect to login
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
    <title>Student Information</title>
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Student Information</h2>
        <form action="student_info.php" method="post">
            <div class="form-group">
                <label for="matric_no">Matric Number:</label>
                <input type="text" id="matric_no" name="matric_no" required>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number:</label>
                <input type="tel" id="phone_number" name="phone_number" required>
            </div>

            <div class="form-group">
                <label for="institution">Institution:</label>
                <input type="text" id="institution" name="institution" required>
            </div>

            <div class="form-group">
                <label for="school">School/Faculty:</label>
                <input type="text" id="school" name="school" required>
            </div>

            <div class="form-group">
                <label for="programme">Programme:</label>
                <input type="text" id="programme" name="programme" required>
            </div>

            <div class="form-group">
                <label for="home_address">Home Address:</label>
                <textarea id="home_address" name="home_address" required></textarea>
            </div>

            <div class="form-group">
                <label for="practicum_start_date">Practicum Start Date:</label>
                <input type="date" id="practicum_start_date" name="practicum_start_date" required>
            </div>

            <div class="form-group">
                <label for="practicum_end_date">Practicum End Date:</label>
                <input type="date" id="practicum_end_date" name="practicum_end_date" required>
            </div>

            <button type="submit">Submit</button>
        </form>
    </div>
</body>
</html>