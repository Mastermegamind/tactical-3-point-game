<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    require_once __DIR__ . '/../config/database.php';

    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Log logout activity
        $stmt = $conn->prepare("
            INSERT INTO admin_activity_log (admin_id, action, description, ip_address)
            VALUES (?, 'logout', 'Admin logged out', ?)
        ");
        $stmt->execute([$_SESSION['admin_id'], $_SERVER['REMOTE_ADDR'] ?? null]);

        // Mark session as inactive
        $stmt = $conn->prepare("
            UPDATE admin_sessions
            SET logout_time = NOW(), is_active = 0
            WHERE admin_id = ? AND is_active = 1
        ");
        $stmt->execute([$_SESSION['admin_id']]);
    } catch (Exception $e) {
        error_log('Admin logout error: ' . $e->getMessage());
    }
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
