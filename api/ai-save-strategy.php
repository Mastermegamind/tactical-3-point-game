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
$strategyName = $input['strategy_name'] ?? '';
$difficulty = $input['difficulty'] ?? 'medium';
$boardState = $input['board_state'] ?? [];
$aiPieces = $input['ai_pieces'] ?? [];
$opponentPieces = $input['opponent_pieces'] ?? [];
$moveFrom = $input['move_from'] ?? null;
$moveTo = $input['move_to'] ?? null;
$moveType = $input['move_type'] ?? 'placement';
$gamePhase = $input['game_phase'] ?? 'placement';
$opponentPattern = $input['opponent_pattern'] ?? 'unknown';
$strategyType = $input['strategy_type'] ?? 'balanced';
$boardScore = $input['board_score'] ?? 0;
$threatLevel = $input['threat_level'] ?? 0;

if (!$sessionId || $moveTo === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if similar strategy already exists
    $stmt = $conn->prepare("
        SELECT id, success_count, failure_count, total_uses, avg_moves_to_win
        FROM ai_strategies
        WHERE difficulty_level = ?
        AND move_to = ?
        AND move_type = ?
        AND game_phase = ?
        AND strategy_type = ?
        AND JSON_CONTAINS(ai_pieces_positions, ?)
        LIMIT 1
    ");

    $stmt->execute([
        $difficulty,
        $moveTo,
        $moveType,
        $gamePhase,
        $strategyType,
        json_encode($aiPieces)
    ]);

    $existing = $stmt->fetch();

    $redisManager = RedisManager::getInstance();

    if ($existing) {
        // Update existing strategy
        $newTotalUses = $existing['total_uses'] + 1;
        $successRate = ($existing['success_count'] / $newTotalUses) * 100;

        // Update priority based on success rate
        $priority = min(100, max(10, round($successRate * 0.8 + $boardScore * 0.2)));

        $stmt = $conn->prepare("
            UPDATE ai_strategies
            SET total_uses = ?,
                success_rate = ?,
                priority_score = ?,
                board_evaluation_score = ?,
                threat_level = ?,
                last_used_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $newTotalUses,
            round($successRate, 2),
            $priority,
            round($boardScore, 4),
            $threatLevel,
            $existing['id']
        ]);

        $redisManager->invalidateAIStrategies($difficulty);

        echo json_encode([
            'success' => true,
            'message' => 'Strategy updated',
            'strategy_id' => $existing['id'],
            'updated' => true
        ]);
    } else {
        // Insert new strategy
        $priority = min(100, max(30, round($boardScore)));

        $stmt = $conn->prepare("
            INSERT INTO ai_strategies
            (strategy_name, difficulty_level, board_state, ai_pieces_positions,
             opponent_pieces_positions, move_from, move_to, move_type, game_phase,
             opponent_pattern, strategy_type, board_evaluation_score, threat_level,
             priority_score, created_at, last_used_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $strategyName,
            $difficulty,
            json_encode($boardState),
            json_encode($aiPieces),
            json_encode($opponentPieces),
            $moveFrom,
            $moveTo,
            $moveType,
            $gamePhase,
            $opponentPattern,
            $strategyType,
            round($boardScore, 4),
            $threatLevel,
            $priority
        ]);

        $strategyId = $conn->lastInsertId();

        $redisManager->invalidateAIStrategies($difficulty);

        echo json_encode([
            'success' => true,
            'message' => 'New strategy saved',
            'strategy_id' => $strategyId,
            'updated' => false
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save strategy: ' . $e->getMessage()
    ]);
}
