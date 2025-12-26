<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get user rating
    $stmt = $conn->prepare("SELECT rating FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Check if already in queue
    $stmt = $conn->prepare("SELECT id FROM matchmaking_queue WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Already in queue']);
        exit;
    }

    // Join matchmaking queue
    $stmt = $conn->prepare("INSERT INTO matchmaking_queue (user_id, rating) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $user['rating']]);

    // Try to find immediate match (within ±200 rating)
    $stmt = $conn->prepare("
        SELECT mq.*, u.username
        FROM matchmaking_queue mq
        JOIN users u ON mq.user_id = u.id
        WHERE mq.user_id != ?
        AND ABS(mq.rating - ?) <= 200
        ORDER BY mq.joined_at ASC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id'], $user['rating']]);
    $opponent = $stmt->fetch();

    if ($opponent) {
        // Create game session
        $stmt = $conn->prepare("
            INSERT INTO game_sessions (player1_id, player2_id, game_mode, board_state)
            VALUES (?, ?, 'pvp', ?)
        ");

        $initialBoard = json_encode([
            'board' => array_fill(0, 9, null),
            'placedCount' => ['X' => 0, 'O' => 0],
            'phase' => 'placement',
            'turn' => 'X'
        ]);

        $stmt->execute([$_SESSION['user_id'], $opponent['user_id'], $initialBoard]);
        $sessionId = $conn->lastInsertId();

        // Remove both players from queue
        $stmt = $conn->prepare("DELETE FROM matchmaking_queue WHERE user_id IN (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $opponent['user_id']]);

        echo json_encode([
            'success' => true,
            'matched' => true,
            'session_id' => $sessionId
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'matched' => false,
            'message' => 'Waiting for opponent'
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Matchmaking failed: ' . $e->getMessage()]);
}
