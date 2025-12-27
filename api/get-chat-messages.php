<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$sessionId = $_GET['session_id'] ?? null;
$afterId = isset($_GET['after']) ? (int)$_GET['after'] : 0;

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Missing session_id']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Verify user is part of this game
    $stmt = $conn->prepare("
        SELECT id FROM game_sessions
        WHERE id = ? AND (player1_id = ? OR player2_id = ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    // Get messages after the specified ID
    $stmt = $conn->prepare("
        SELECT gcm.id, gcm.user_id, gcm.message, gcm.created_at, u.username
        FROM game_chat_messages gcm
        JOIN users u ON gcm.user_id = u.id
        WHERE gcm.session_id = ? AND gcm.id > ?
        ORDER BY gcm.created_at ASC
    ");
    $stmt->execute([$sessionId, $afterId]);
    $messages = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load messages: ' . $e->getMessage()
    ]);
}
