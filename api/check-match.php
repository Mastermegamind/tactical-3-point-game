<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['matched' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if user has a session waiting
    $stmt = $conn->prepare("
        SELECT id FROM game_sessions
        WHERE (player1_id = ? OR player2_id = ?)
        AND status = 'active'
        AND player2_id IS NOT NULL
        ORDER BY started_at DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $session = $stmt->fetch();

    if ($session) {
        // Remove from queue
        $stmt = $conn->prepare("DELETE FROM matchmaking_queue WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        echo json_encode([
            'matched' => true,
            'session_id' => $session['id']
        ]);
    } else {
        echo json_encode(['matched' => false]);
    }

} catch (Exception $e) {
    echo json_encode(['matched' => false, 'error' => $e->getMessage()]);
}
