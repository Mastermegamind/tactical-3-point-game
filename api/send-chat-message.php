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
$message = trim($input['message'] ?? '');

if (!$sessionId || !$message) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if (strlen($message) > 200) {
    echo json_encode(['success' => false, 'message' => 'Message too long (max 200 characters)']);
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

    // Insert message
    $stmt = $conn->prepare("
        INSERT INTO game_chat_messages (session_id, user_id, message)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $message]);

    echo json_encode([
        'success' => true,
        'message_id' => $conn->lastInsertId()
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message: ' . $e->getMessage()
    ]);
}
