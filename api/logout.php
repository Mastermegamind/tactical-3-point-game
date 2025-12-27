<?php
require_once __DIR__ . '/../config/session.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/RedisManager.php';

// Set user as offline
if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("UPDATE users SET is_online = FALSE WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    $redisManager = RedisManager::getInstance();
    if ($redisManager->isEnabled()) {
        $redisManager->decrementOnlineUsers();
        $redisManager->deletePattern("leaderboard:online_users:*");
    }
}

// Clear session
session_destroy();

header('Location: ../login.php');
exit;
