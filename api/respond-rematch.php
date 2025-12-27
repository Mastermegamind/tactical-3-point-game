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
$requestId = $input['request_id'] ?? null;
$accept = $input['accept'] ?? false;

if (!$requestId) {
    echo json_encode(['success' => false, 'message' => 'Missing request_id']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $redisManager = RedisManager::getInstance();

    // Get rematch request
    $stmt = $conn->prepare("
        SELECT rr.*, gs.game_mode
        FROM rematch_requests rr
        JOIN game_sessions gs ON rr.original_session_id = gs.id
        WHERE rr.id = ? AND rr.recipient_id = ? AND rr.status = 'pending'
    ");
    $stmt->execute([$requestId, $_SESSION['user_id']]);
    $request = $stmt->fetch();

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found or expired']);
        exit;
    }

    if ($accept) {
        // Create new game session
        $initialBoard = json_encode([
            'board' => array_fill(0, 9, null),
            'placedCount' => ['X' => 0, 'O' => 0],
            'phase' => 'placement',
            'turn' => 'X'
        ]);

        // Player 1 is the requester, Player 2 is the recipient (you)
        $stmt = $conn->prepare("
            INSERT INTO game_sessions (player1_id, player2_id, game_mode, board_state)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$request['requester_id'], $_SESSION['user_id'], $request['game_mode'], $initialBoard]);
        $newSessionId = $conn->lastInsertId();

        // Update rematch request
        $stmt = $conn->prepare("
            UPDATE rematch_requests
            SET status = 'accepted', new_session_id = ?, responded_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newSessionId, $requestId]);

        // Cache game state in Redis
        if ($redisManager->isEnabled()) {
            $redisManager->cacheGameState($newSessionId, [
                'board_state' => $initialBoard,
                'status' => 'active',
                'winner_id' => null,
                'current_phase' => 'placement',
                'current_turn' => 'X'
            ]);
            $redisManager->trackActiveGame($newSessionId, [
                'player1_id' => $request['requester_id'],
                'player2_id' => $_SESSION['user_id'],
                'mode' => $request['game_mode']
            ]);
        }

        echo json_encode([
            'success' => true,
            'new_session_id' => $newSessionId
        ]);
    } else {
        // Reject rematch
        $stmt = $conn->prepare("
            UPDATE rematch_requests
            SET status = 'rejected', responded_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$requestId]);

        echo json_encode([
            'success' => true,
            'rejected' => true
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to respond: ' . $e->getMessage()
    ]);
}
