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
$gameMode = $input['game_mode'] ?? null;

if (!$sessionId || !$gameMode) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Validate game mode
if (!in_array($gameMode, ['pvc-easy', 'pvc-medium', 'pvc-hard'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid game mode']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $redisManager = RedisManager::getInstance();

    // Get the previous game session to check who started
    $stmt = $conn->prepare("
        SELECT board_state, player1_id
        FROM game_sessions
        WHERE id = ? AND player1_id = ?
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id']]);
    $previousGame = $stmt->fetch();

    if (!$previousGame) {
        echo json_encode(['success' => false, 'message' => 'Previous game not found']);
        exit;
    }

    // Parse previous board state to determine who went first
    $previousState = json_decode($previousGame['board_state'], true);
    $previousFirstTurn = $previousState['turn'] ?? 'X';

    // Alternate the starting player
    // If player was X (went first) last time, make them O (go second) this time
    $newFirstTurn = $previousFirstTurn === 'X' ? 'O' : 'X';

    // Create new game session with alternating first turn
    $initialBoard = json_encode([
        'board' => array_fill(0, 9, null),
        'placedCount' => ['X' => 0, 'O' => 0],
        'phase' => 'placement',
        'turn' => $newFirstTurn
    ]);

    $stmt = $conn->prepare("
        INSERT INTO game_sessions (player1_id, game_mode, board_state)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $gameMode, $initialBoard]);
    $newSessionId = $conn->lastInsertId();

    // Cache in Redis
    if ($redisManager->isEnabled()) {
        $redisManager->cacheGameState($newSessionId, [
            'board_state' => $initialBoard,
            'status' => 'active',
            'winner_id' => null,
            'current_phase' => 'placement',
            'current_turn' => $newFirstTurn
        ]);
        $redisManager->trackActiveGame($newSessionId, [
            'player1_id' => $_SESSION['user_id'],
            'player2_id' => null,
            'mode' => $gameMode
        ]);
    }

    echo json_encode([
        'success' => true,
        'new_session_id' => $newSessionId,
        'first_turn' => $newFirstTurn,
        'message' => $newFirstTurn === 'X' ? 'You go first this time!' : 'AI goes first this time!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create rematch: ' . $e->getMessage()
    ]);
}
