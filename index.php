<?php
session_start();

// Redirect based on login status
if (isset($_SESSION['user_id'])) {
    // User is logged in - go to dashboard
    header('Location: dashboard.php');
} else {
    // User not logged in - go to login page
    header('Location: login.php');
}
exit;
