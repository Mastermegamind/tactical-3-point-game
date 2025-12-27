<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/RedisManager.php';

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

    // Get game session to find opponent
    $stmt = $conn->prepare("
        SELECT player1_id, player2_id
        FROM game_sessions
        WHERE id = ? AND (player1_id = ? OR player2_id = ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }

    // Determine opponent ID
    $opponentId = ($session['player1_id'] == $_SESSION['user_id'])
        ? $session['player2_id']
        : $session['player1_id'];

    if (!$opponentId) {
        // No opponent (vs AI)
        echo json_encode([
            'success' => true,
            'is_online' => false,
            'is_ai' => true
        ]);
        exit;
    }

    // Get opponent's online status
    $stmt = $conn->prepare("
        SELECT is_online, last_activity
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$opponentId]);
    $opponent = $stmt->fetch();

    if (!$opponent) {
        echo json_encode(['success' => false, 'message' => 'Opponent not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'is_online' => (bool)$opponent['is_online'],
        'last_seen' => $opponent['last_activity'],
        'is_ai' => false
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to check status: ' . $e->getMessage()
    ]);
}
