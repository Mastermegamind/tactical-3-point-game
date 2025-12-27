<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/RedisManager.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$sessionId = $input['session_id'] ?? null;

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $redisManager = RedisManager::getInstance();

    // Verify user is part of this game
    $stmt = $conn->prepare("
        SELECT player1_id, player2_id, status, game_mode
        FROM game_sessions
        WHERE id = ? AND (player1_id = ? OR player2_id = ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Game not found or access denied']);
        exit;
    }

    if ($session['status'] === 'completed') {
        echo json_encode(['success' => false, 'message' => 'Game already completed']);
        exit;
    }

    // Determine winner (opponent if abandoning)
    $winnerId = null;
    if ($session['player2_id']) {
        // PvP game - opponent wins
        $winnerId = ($session['player1_id'] == $_SESSION['user_id']) ? $session['player2_id'] : $session['player1_id'];
    }
    // If vs AI (player2_id is null), winnerId stays null (AI doesn't get wins)

    // Mark game as completed with opponent as winner
    $stmt = $conn->prepare("
        UPDATE game_sessions
        SET status = 'completed',
            winner_id = ?,
            completed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$winnerId, $sessionId]);

    // Update user stats
    $stmt = $conn->prepare("
        UPDATE users
        SET losses = losses + 1,
            rating = GREATEST(rating - 15, 0)
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);

    // If PvP, update opponent stats
    if ($winnerId) {
        $stmt = $conn->prepare("
            UPDATE users
            SET wins = wins + 1,
                rating = rating + 25
            WHERE id = ?
        ");
        $stmt->execute([$winnerId]);
    }

    // Clear Redis cache
    if ($redisManager->isEnabled()) {
        $redisManager->deleteGameState($sessionId);
        $redisManager->removeActiveGame($sessionId);
        $redisManager->invalidateUserStats($_SESSION['user_id']);
        if ($winnerId) {
            $redisManager->invalidateUserStats($winnerId);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Game abandoned successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to abandon game: ' . $e->getMessage()
    ]);
}
