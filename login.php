<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    try {
        $stmt = $conn->prepare("SELECT user_id, password, role FROM user WHERE user_name = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $hashed_password = $row['password'];
            $role = $row['role'];

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $row['user_id'];  // Store user_id in session
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                
                if ($role == 1) { // intern
                    header("Location: main_menu.php");
                } elseif ($role == 2) { // Supervisor
                    header("Location: sv_main.php"); // Supervisor's main page
                }
                exit();
            } else {
                echo "<script>alert('Invalid username or password.');</script>";
            }
        } else {
            echo "<script>alert('Invalid username or password.');</script>";
        }
    } catch(PDOException $e) {
        echo "<script>console.log('Error: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/auth_form.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <input type="submit" value="Login">
            </div>
        </form>
        <div class="auth-link">
            <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
        </div>
    </div>
</body>
</html>
