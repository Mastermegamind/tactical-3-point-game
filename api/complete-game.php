<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$sessionId = $input['session_id'] ?? null;
$winnerId = $input['winner_id'] ?? null; // null for draw

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get session info
    $stmt = $conn->prepare("
        SELECT player1_id, player2_id, game_mode, started_at
        FROM game_sessions
        WHERE id = ? AND (player1_id = ? OR player2_id = ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Invalid session']);
        exit;
    }

    // Update game session
    $stmt = $conn->prepare("
        UPDATE game_sessions
        SET status = 'completed',
            winner_id = ?,
            completed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$winnerId, $sessionId]);

    // Update player stats
    if ($winnerId) {
        // Update winner
        $stmt = $conn->prepare("UPDATE users SET wins = wins + 1, rating = rating + 25 WHERE id = ?");
        $stmt->execute([$winnerId]);

        // Update loser
        $loserId = ($winnerId == $session['player1_id']) ? $session['player2_id'] : $session['player1_id'];
        if ($loserId) {
            $stmt = $conn->prepare("UPDATE users SET losses = losses + 1, rating = rating - 10 WHERE id = ?");
            $stmt->execute([$loserId]);
        }
    } else {
        // Draw - update both players
        $stmt = $conn->prepare("UPDATE users SET draws = draws + 1 WHERE id IN (?, ?)");
        $stmt->execute([$session['player1_id'], $session['player2_id']]);
    }

    // Save AI training data if vs computer
    if (strpos($session['game_mode'], 'pvc') !== false) {
        $gameDuration = strtotime('now') - strtotime($session['started_at']);
        $difficulty = explode('-', $session['game_mode'])[1];

        $outcome = 'draw';
        if ($winnerId) {
            $outcome = ($winnerId == $session['player1_id']) ? 'player_win' : 'ai_win';
        }

        // Count total moves
        $stmt = $conn->prepare("SELECT COUNT(*) as move_count FROM game_moves WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $moveCount = $stmt->fetchColumn();

        // Get user rating
        $stmt = $conn->prepare("SELECT rating FROM users WHERE id = ?");
        $stmt->execute([$session['player1_id']]);
        $playerRating = $stmt->fetchColumn();

        $stmt = $conn->prepare("
            INSERT INTO ai_training_data
            (session_id, game_outcome, difficulty_level, total_moves, game_duration_seconds, player_rating)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$sessionId, $outcome, $difficulty, $moveCount, $gameDuration, $playerRating]);
    }

    echo json_encode(['success' => true, 'message' => 'Game completed']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to complete game: ' . $e->getMessage()]);
}
