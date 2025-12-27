<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ErrorLogger.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get all online users except current user
    $stmt = $conn->prepare("
        SELECT id, username, avatar, rating, wins, losses, draws
        FROM users
        WHERE is_online = 1 AND id != ?
        ORDER BY rating DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll();

    // Check if each user has a pending challenge with current user
    foreach ($users as &$user) {
        $stmt = $conn->prepare("
            SELECT id, status
            FROM game_challenges
            WHERE ((challenger_id = ? AND challenged_id = ?) OR (challenger_id = ? AND challenged_id = ?))
            AND status = 'pending'
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([
            $_SESSION['user_id'], $user['id'],
            $user['id'], $_SESSION['user_id']
        ]);
        $challenge = $stmt->fetch();

        $user['has_pending_challenge'] = $challenge !== false;
        $user['pending_challenge_id'] = $challenge ? $challenge['id'] : null;
    }

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);

} catch (Exception $e) {
    $logger = ErrorLogger::getInstance();
    $logger->logException($e, 'api');

    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch online users: ' . $e->getMessage()
    ]);
}
