<?php
// filepath: c:\xampp\htdocs\log\logout.php

// Start the session
session_start();

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy the session
session_destroy();

// Set no-cache headers to prevent browser back button from showing logged-in pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page with a random parameter to prevent caching
header("Location: login.php?logout=" . time());
exit();
?>
