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

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Missing session_id']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get game session and determine opponent
    $stmt = $conn->prepare("
        SELECT player1_id, player2_id, game_mode, status
        FROM game_sessions
        WHERE id = ? AND (player1_id = ? OR player2_id = ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Game not found']);
        exit;
    }

    if ($session['status'] !== 'completed') {
        echo json_encode(['success' => false, 'message' => 'Game not completed yet']);
        exit;
    }

    // Determine opponent
    $opponentId = ($session['player1_id'] == $_SESSION['user_id'])
        ? $session['player2_id']
        : $session['player1_id'];

    if (!$opponentId) {
        echo json_encode(['success' => false, 'message' => 'Cannot rematch AI games']);
        exit;
    }

    // Check for existing pending request
    $stmt = $conn->prepare("
        SELECT id FROM rematch_requests
        WHERE original_session_id = ?
        AND status = 'pending'
        AND (requester_id = ? OR recipient_id = ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Rematch request already exists']);
        exit;
    }

    // Create rematch request (expires in 2 minutes)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+2 minutes'));

    $stmt = $conn->prepare("
        INSERT INTO rematch_requests (original_session_id, requester_id, recipient_id, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $opponentId, $expiresAt]);
    $requestId = $conn->lastInsertId();

    // Create notification for opponent
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $requester = $stmt->fetch();

    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, title, message, data)
        VALUES (?, 'game_start', ?, ?, ?)
    ");

    $title = 'Rematch Request';
    $message = $requester['username'] . ' wants a rematch!';
    $notificationData = json_encode([
        'request_id' => $requestId,
        'session_id' => $sessionId,
        'requester_id' => $_SESSION['user_id']
    ]);

    $stmt->execute([$opponentId, $title, $message, $notificationData]);

    echo json_encode([
        'success' => true,
        'request_id' => $requestId
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send request: ' . $e->getMessage()
    ]);
}
