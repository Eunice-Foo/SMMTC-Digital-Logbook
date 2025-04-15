<?php
require_once 'components/toast_notification.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toast Test</title>
    <!-- Include Flaticon CSS -->
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-solid-rounded/css/uicons-solid-rounded.css'>
    <style>
        .test-buttons {
            margin: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-success {
            background-color: #4caf50;
            color: white;
        }
        .btn-error {
            background-color: #f44336;
            color: white;
        }
        .btn-warning {
            background-color: #ff9800;
            color: white;
        }
    </style>
</head>
<body>
    <?php initializeToast(); ?>

    <div class="test-buttons">
        <button class="btn btn-success" onclick="showToast('success', 'Success Test', 'This is a success message!', 'Close', false)">
            Test Success Toast
        </button>
        <button class="btn btn-error" onclick="showToast('error', 'Error Test', 'This is an error message!', 'Close', false)">
            Test Error Toast
        </button>
        <button class="btn btn-warning" onclick="showToast('warning', 'Warning Test', 'This is a warning message!', 'Dismiss', false)">
            Test Warning Toast
        </button>
    </div>
</body>
</html>