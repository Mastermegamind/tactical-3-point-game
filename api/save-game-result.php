<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/RedisManager.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$sessionId = $input['session_id'] ?? null;
$winner = $input['winner'] ?? null;  // 'X', 'O', or 'draw'
$totalMoves = $input['total_moves'] ?? 0;
$difficulty = $input['difficulty'] ?? 'medium';
$gameDuration = $input['game_duration_seconds'] ?? 0;
$playerRating = $input['player_rating'] ?? 1000;

if (!$sessionId || !$winner) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Verify this is a vs AI game and user owns it
    $stmt = $conn->prepare("
        SELECT id, game_mode FROM game_sessions
        WHERE id = ? AND (player1_id = ? OR player2_id = ?)
        AND game_mode LIKE '%pvc%'
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Invalid session or not an AI game']);
        exit;
    }

    // Determine game outcome from AI's perspective
    $gameOutcome = $winner === 'draw' ? 'draw' : ($winner === 'O' ? 'ai_win' : 'player_win');

    // Save to ai_training_data for real-time learning
    $stmt = $conn->prepare("
        INSERT INTO ai_training_data
        (session_id, game_outcome, difficulty_level, total_moves,
         player_rating, game_duration_seconds, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $sessionId,
        $gameOutcome,
        $difficulty,
        $totalMoves,
        $playerRating,
        $gameDuration
    ]);

    $redisManager = RedisManager::getInstance();
    if ($redisManager->isEnabled()) {
        $redisManager->invalidateTrainingData($difficulty);
        $redisManager->delete("ai:training:all");
        $redisManager->deletePattern("ai:stats:*");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Game result saved for AI learning',
        'outcome' => $gameOutcome
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save game result: ' . $e->getMessage()
    ]);
}
