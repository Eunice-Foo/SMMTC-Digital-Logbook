<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkUserLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    // Set cache control headers to prevent access after logout
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}
?>