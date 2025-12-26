<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Set user as offline
if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("UPDATE users SET is_online = FALSE WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Clear session
session_destroy();

header('Location: ../login.php');
exit;
