<?php
require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Insert into user table
        $stmt = $conn->prepare("
            INSERT INTO user (user_name, email, password, role) 
            VALUES (:username, :email, :password, :role)
        ");
        
        $stmt->execute([
            ':username' => $_POST['username'],
            ':email' => $_POST['email'],
            ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            ':role' => $_POST['role']
        ]);
        
        $user_id = $conn->lastInsertId();
        
        // If role is student, redirect to student info page
        if ($_POST['role'] == ROLE_STUDENT) {
            session_start();
            $_SESSION['new_user_id'] = $user_id;
            $_SESSION['role'] = ROLE_STUDENT;
            header("Location: student_info.php");
            exit();
        } else {
            session_start();
            $_SESSION['new_user_id'] = $user_id;
            $_SESSION['role'] = ROLE_SUPERVISOR;
            header("Location: supervisor_info.php");
            exit();
        }
        
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
    <title>Signup</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/auth_form.css">
</head>
<body>
    <div class="container">
        <h2>Signup Form</h2>
        <form action="signup.php" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="1">Student</option>
                    <option value="2">Supervisor</option>
                </select>
            </div>

            <div class="form-group">
                <button type="submit">Signup</button>
            </div>
        </form>
        
        <div class="auth-link">
            <p>Already have an account? <a href="login.php">Log in here</a></p>
        </div>
    </div>
</body>
</html>