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

if (!isset($data['challenged_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing challenged user ID']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $challengerId = $_SESSION['user_id'];
    $challengedId = $data['challenged_id'];

    // Prevent challenging yourself
    if ($challengerId == $challengedId) {
        echo json_encode(['success' => false, 'message' => 'Cannot challenge yourself']);
        exit;
    }

    // Check if challenged user is online
    $stmt = $conn->prepare("SELECT is_online, username FROM users WHERE id = ?");
    $stmt->execute([$challengedId]);
    $challengedUser = $stmt->fetch();

    if (!$challengedUser) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    if (!$challengedUser['is_online']) {
        echo json_encode(['success' => false, 'message' => 'User is not online']);
        exit;
    }

    // Check for existing pending challenge
    $stmt = $conn->prepare("
        SELECT id FROM game_challenges
        WHERE ((challenger_id = ? AND challenged_id = ?) OR (challenger_id = ? AND challenged_id = ?))
        AND status = 'pending'
    ");
    $stmt->execute([$challengerId, $challengedId, $challengedId, $challengerId]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'A pending challenge already exists']);
        exit;
    }

    // Get challenger username
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$challengerId]);
    $challenger = $stmt->fetch();

    // Create challenge (expires in 2 minutes)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+2 minutes'));

    $stmt = $conn->prepare("
        INSERT INTO game_challenges (challenger_id, challenged_id, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$challengerId, $challengedId, $expiresAt]);
    $challengeId = $conn->lastInsertId();

    // Create notification for challenged user
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, title, message, data)
        VALUES (?, 'challenge', ?, ?, ?)
    ");

    $title = 'Game Challenge!';
    $message = $challenger['username'] . ' has challenged you to a game!';
    $notificationData = json_encode([
        'challenge_id' => $challengeId,
        'challenger_id' => $challengerId,
        'challenger_name' => $challenger['username']
    ]);

    $stmt->execute([$challengedId, $title, $message, $notificationData]);

    echo json_encode([
        'success' => true,
        'message' => 'Challenge sent to ' . $challengedUser['username'],
        'challenge_id' => $challengeId
    ]);

} catch (Exception $e) {
    $logger = ErrorLogger::getInstance();
    $logger->logException($e, 'api');

    echo json_encode([
        'success' => false,
        'message' => 'Failed to send challenge: ' . $e->getMessage()
    ]);
}
