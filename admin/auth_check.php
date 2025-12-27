<?php
// Admin Authentication Check
// Include this file at the top of every admin page

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Verify admin still exists and is active
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT is_active, role FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if (!$admin || !$admin['is_active']) {
    session_destroy();
    header('Location: login.php?error=inactive');
    exit;
}

// Update session role if changed
$_SESSION['admin_role'] = $admin['role'];

// Helper function to check if admin has permission
function hasPermission($requiredRole = 'admin') {
    $roles = ['moderator' => 1, 'admin' => 2, 'super_admin' => 3];
    $userLevel = $roles[$_SESSION['admin_role']] ?? 0;
    $requiredLevel = $roles[$requiredRole] ?? 0;
    return $userLevel >= $requiredLevel;
}

// Helper function to log admin activity
function logAdminActivity($action, $description = null, $targetType = null, $targetId = null) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            INSERT INTO admin_activity_log (admin_id, action, description, target_type, target_id, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['admin_id'],
            $action,
            $description,
            $targetType,
            $targetId,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {
        error_log('Failed to log admin activity: ' . $e->getMessage());
    }
}
