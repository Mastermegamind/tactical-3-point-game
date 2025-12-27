<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ErrorLogger.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['challenge_id']) || !isset($data['response'])) {
    echo json_encode(['success' => false, 'message' => 'Missing challenge ID or response']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $challengeId = $data['challenge_id'];
    $response = $data['response']; // 'accepted' or 'rejected'
    $userId = $_SESSION['user_id'];

    // Get challenge details
    $stmt = $conn->prepare("
        SELECT gc.*, u.username as challenger_name
        FROM game_challenges gc
        JOIN users u ON gc.challenger_id = u.id
        WHERE gc.id = ? AND gc.challenged_id = ?
    ");
    $stmt->execute([$challengeId, $userId]);
    $challenge = $stmt->fetch();

    if (!$challenge) {
        echo json_encode(['success' => false, 'message' => 'Challenge not found']);
        exit;
    }

    if ($challenge['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Challenge already responded to']);
        exit;
    }

    // Check if challenge has expired
    if (strtotime($challenge['expires_at']) < time()) {
        $stmt = $conn->prepare("UPDATE game_challenges SET status = 'expired' WHERE id = ?");
        $stmt->execute([$challengeId]);
        echo json_encode(['success' => false, 'message' => 'Challenge has expired']);
        exit;
    }

    if ($response === 'accepted') {
        // Create game session
        $initialBoard = json_encode([
            'board' => array_fill(0, 9, null),
            'placedCount' => ['X' => 0, 'O' => 0],
            'phase' => 'placement',
            'turn' => 'X'
        ]);

        $stmt = $conn->prepare("
            INSERT INTO game_sessions (player1_id, player2_id, game_mode, board_state)
            VALUES (?, ?, 'pvp', ?)
        ");
        $stmt->execute([$challenge['challenger_id'], $userId, $initialBoard]);
        $sessionId = $conn->lastInsertId();

        // Update challenge with session ID
        $stmt = $conn->prepare("
            UPDATE game_challenges
            SET status = 'accepted', session_id = ?, responded_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$sessionId, $challengeId]);

        // Remove both users from matchmaking queue if they're in it
        $stmt = $conn->prepare("DELETE FROM matchmaking_queue WHERE user_id IN (?, ?)");
        $stmt->execute([$challenge['challenger_id'], $userId]);

        // Get challenged user's username
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $challengedUser = $stmt->fetch();

        // Notify challenger that challenge was accepted
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, title, message, data)
            VALUES (?, 'game_start', ?, ?, ?)
        ");

        $title = 'Challenge Accepted!';
        $message = $challengedUser['username'] . ' accepted your challenge!';
        $notificationData = json_encode([
            'session_id' => $sessionId,
            'opponent_name' => $challengedUser['username']
        ]);

        $stmt->execute([$challenge['challenger_id'], $title, $message, $notificationData]);

        echo json_encode([
            'success' => true,
            'message' => 'Challenge accepted',
            'session_id' => $sessionId
        ]);

    } else if ($response === 'rejected') {
        // Update challenge status
        $stmt = $conn->prepare("
            UPDATE game_challenges
            SET status = 'rejected', responded_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$challengeId]);

        // Get challenged user's username
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $challengedUser = $stmt->fetch();

        // Notify challenger that challenge was rejected
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, title, message, data)
            VALUES (?, 'challenge', ?, ?, ?)
        ");

        $title = 'Challenge Declined';
        $message = $challengedUser['username'] . ' declined your challenge.';
        $notificationData = json_encode([
            'challenge_id' => $challengeId
        ]);

        $stmt->execute([$challenge['challenger_id'], $title, $message, $notificationData]);

        echo json_encode([
            'success' => true,
            'message' => 'Challenge rejected'
        ]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid response']);
    }

} catch (Exception $e) {
    $logger = ErrorLogger::getInstance();
    $logger->logException($e, 'api');

    echo json_encode([
        'success' => false,
        'message' => 'Failed to respond to challenge: ' . $e->getMessage()
    ]);
}
