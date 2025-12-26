<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT board_state, status, winner_id, current_phase, current_turn
        FROM game_sessions
        WHERE id = ? AND (player1_id = ? OR player2_id = ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'board_state' => $session['board_state'],
        'status' => $session['status'],
        'winner_id' => $session['winner_id'],
        'current_phase' => $session['current_phase'],
        'current_turn' => $session['current_turn']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load: ' . $e->getMessage()]);
}
