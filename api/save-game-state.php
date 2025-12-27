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
$gameState = $input['game_state'] ?? null;

if (!$sessionId || !$gameState) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Verify session belongs to user
    $stmt = $conn->prepare("
        SELECT id FROM game_sessions
        WHERE id = ? AND (player1_id = ? OR player2_id = ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Invalid session']);
        exit;
    }

    // Update game state
    $stmt = $conn->prepare("
        UPDATE game_sessions
        SET board_state = ?,
            current_phase = ?,
            current_turn = ?,
            last_move_at = NOW()
        WHERE id = ?
    ");

    $boardStateJson = json_encode($gameState);
    $stmt->execute([
        $boardStateJson,
        $gameState['phase'] ?? 'placement',
        $gameState['turn'] ?? 'X',
        $sessionId
    ]);

    $redisManager = RedisManager::getInstance();
    if ($redisManager->isEnabled()) {
        $cachedState = $redisManager->getGameState($sessionId);
        $cachePayload = is_array($cachedState) ? $cachedState : [];
        $cachePayload['board_state'] = $boardStateJson;
        $cachePayload['current_phase'] = $gameState['phase'] ?? 'placement';
        $cachePayload['current_turn'] = $gameState['turn'] ?? 'X';
        $cachePayload['status'] = $cachePayload['status'] ?? 'active';
        $cachePayload['winner_id'] = $cachePayload['winner_id'] ?? null;
        $redisManager->cacheGameState($sessionId, $cachePayload);
    }

    echo json_encode(['success' => true, 'message' => 'Game state saved']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to save: ' . $e->getMessage()]);
}
