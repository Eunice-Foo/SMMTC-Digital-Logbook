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
                // Include and initialize toast notification component
                $login_error = true;
            }
        } else {
            $login_error = true;
        }
    } catch(PDOException $e) {
        $db_error = $e->getMessage();
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
    <link rel="stylesheet" href="css/form_indicators.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Open Sans', sans-serif;
            overflow: hidden;
        }
        
        .split-container {
            display: flex;
            height: 100vh;
        }
        
        .image-side {
            flex: 1;
            background-color: var(--primary-color);
            position: relative;
            overflow: hidden;
        }
        
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.2);
            z-index: 1;
        }
        
        .image-side img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .form-side {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            background-color: #fff;
        }
        
        .auth-container {
            width: 100%;
            max-width: 400px;
        }
        
        .auth-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .auth-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
        }
        
        .auth-header p {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(var(--primary-color-rgb), 0.2);
        }
        
        .submit-button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .submit-button:hover {
            background-color: var(--primary-hover);
        }
        
        .auth-footer {
            margin-top: 24px;
            text-align: center;
            color: var(--text-secondary);
        }
        
        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #dc3545;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .split-container {
                flex-direction: column;
            }
            
            .image-side {
                height: 30vh;
                min-height: 200px;
            }
            
            .form-side {
                height: 70vh;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="split-container">
        <div class="image-side">
            <div class="image-overlay"></div>
            <img src="images/login-cover.png" alt="Login Cover">
        </div>
        
        <div class="form-side">
            <div class="auth-container">
                <div class="auth-header">
                    <h1>Welcome Back</h1>
                    <p>Please enter your credentials to login</p>
                </div>
                
                <?php if (isset($login_error) && $login_error): ?>
                <div class="error-message">
                    <i class="fi fi-rr-exclamation"></i> Invalid username or password. Please try again.
                </div>
                <?php endif; ?>
                
                <?php if (isset($db_error)): ?>
                <div class="error-message">
                    <i class="fi fi-rr-exclamation"></i> A system error occurred. Please try again later.
                </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label for="username">Username<span class="required-indicator">*</span></label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email<span class="required-indicator">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password<span class="required-indicator">*</span></label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password<span class="required-indicator">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="submit-button">Login</button>
                </form>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="index.php">Sign up here</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
