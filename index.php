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
        
        // Start the session and log the user in
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['role'] = $_POST['role'];
        $_SESSION['needs_profile_completion'] = true;
        
        // Redirect based on role
        if ($_POST['role'] == ROLE_STUDENT) {
            header("Location: student_info.php");
        } else {
            header("Location: supervisor_info.php");
        }
        exit();
        
    } catch(PDOException $e) {
        $signup_error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
        
        .form-side {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            background-color: #fff;
            order: 1; /* Left side */
        }
        
        .image-side {
            flex: 1;
            background-color: var(--primary-color);
            position: relative;
            overflow: hidden;
            order: 2; /* Right side */
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
        
        .auth-container {
            width: 100%;
            max-width: 400px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus {
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
                order: 1; /* Top on mobile */
            }
            
            .form-side {
                height: 70vh;
                padding: 20px;
                order: 2; /* Bottom on mobile */
            }
        }
    </style>
</head>
<body>
    <div class="split-container">
        <div class="form-side">
            <div class="auth-container">
                <div class="auth-header">
                    <h1>Create Account</h1>
                    <p>Please fill in your information to get started</p>
                </div>
                
                <?php if (isset($signup_error)): ?>
                <div class="error-message">
                    <i class="fi fi-rr-exclamation"></i> Error: <?php echo htmlspecialchars($signup_error); ?>
                </div>
                <?php endif; ?>
                
                <form action="index.php" method="post">
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

                    <div class="form-group">
                        <label for="role">I am a...</label>
                        <select id="role" name="role" required>
                            <option value="1">Intern / Student</option>
                            <option value="2">Report / Company Supervisor</option>
                        </select>
                    </div>

                    <button type="submit" class="submit-button">Sign Up</button>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Log in here</a></p>
                </div>
            </div>
        </div>
        
        <div class="image-side">
            <div class="image-overlay"></div>
            <img src="images/signup-cover.png" alt="Signup Cover">
        </div>
    </div>
</body>
</html>