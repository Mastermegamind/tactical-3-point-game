<?php
require_once __DIR__ . '/config/session.php';

// Redirect based on login status
if (isset($_SESSION['user_id'])) {
    // User is logged in - go to dashboard
    header('Location: dashboard.php');
} else {
    // User not logged in - go to login page
    header('Location: login.php');
}
exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
</html>