<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$sessionId = $input['session_id'] ?? null;
$moveNumber = $input['move_number'] ?? null;
$player = $input['player'] ?? null;
$moveType = $input['move_type'] ?? null;
$fromPosition = $input['from_position'] ?? null;
$toPosition = $input['to_position'] ?? null;
$boardBefore = $input['board_before'] ?? null;
$boardAfter = $input['board_after'] ?? null;
$thinkTime = $input['think_time_ms'] ?? 0;

if (!$sessionId || !$player || !$moveType || $toPosition === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Verify session
    $stmt = $conn->prepare("
        SELECT id FROM game_sessions
        WHERE id = ? AND (player1_id = ? OR player2_id = ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Invalid session']);
        exit;
    }

    // Save move
    $stmt = $conn->prepare("
        INSERT INTO game_moves
        (session_id, move_number, player, move_type, from_position, to_position,
         board_state_before, board_state_after, think_time_ms)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $sessionId,
        $moveNumber,
        $player,
        $moveType,
        $fromPosition,
        $toPosition,
        json_encode($boardBefore),
        json_encode($boardAfter),
        $thinkTime
    ]);

    echo json_encode(['success' => true, 'message' => 'Move recorded']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to save move: ' . $e->getMessage()]);
}
