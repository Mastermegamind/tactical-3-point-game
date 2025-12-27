<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/RedisManager.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $difficulty = $_GET['difficulty'] ?? 'medium';
    $phase = $_GET['phase'] ?? null;
    $strategyType = $_GET['strategy_type'] ?? null;
    $limit = isset($_GET['limit']) ? min(100, (int)$_GET['limit']) : 50;

    $redisManager = RedisManager::getInstance();
    $phaseKey = $phase ?: 'all';
    $typeKey = $strategyType ?: 'all';
    $cacheKey = "ai:strategies:{$difficulty}:{$phaseKey}:{$typeKey}:{$limit}";

    if ($redisManager->isEnabled()) {
        $cachedResponse = $redisManager->get($cacheKey);
        if ($cachedResponse !== false) {
            echo json_encode($cachedResponse);
            exit;
        }
    }

    // Build query
    $query = "
        SELECT
            id, strategy_name, difficulty_level, board_state,
            ai_pieces_positions, opponent_pieces_positions,
            move_from, move_to, move_type, game_phase,
            opponent_pattern, strategy_type, success_count,
            failure_count, success_rate, total_uses,
            avg_moves_to_win, board_evaluation_score,
            threat_level, priority_score, notes,
            created_at, last_used_at
        FROM ai_strategies
        WHERE difficulty_level = :difficulty
    ";

    $params = ['difficulty' => $difficulty];

    if ($phase) {
        $query .= " AND game_phase = :phase";
        $params['phase'] = $phase;
    }

    if ($strategyType) {
        $query .= " AND strategy_type = :strategy_type";
        $params['strategy_type'] = $strategyType;
    }

    // Order by priority and success rate
    $query .= " ORDER BY priority_score DESC, success_rate DESC, total_uses DESC";
    $query .= " LIMIT :limit";

    $stmt = $conn->prepare($query);

    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

    $stmt->execute();
    $strategies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode JSON fields
    foreach ($strategies as &$strategy) {
        $strategy['board_state'] = json_decode($strategy['board_state'], true);
        $strategy['ai_pieces_positions'] = json_decode($strategy['ai_pieces_positions'], true);
        $strategy['opponent_pieces_positions'] = json_decode($strategy['opponent_pieces_positions'], true);
    }

    // Get strategy statistics
    $stmt = $conn->prepare("
        SELECT
            COUNT(*) as total_strategies,
            AVG(success_rate) as avg_success_rate,
            SUM(total_uses) as total_uses,
            MAX(priority_score) as max_priority
        FROM ai_strategies
        WHERE difficulty_level = ?
    ");
    $stmt->execute([$difficulty]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'strategies' => $strategies,
        'stats' => $stats,
        'count' => count($strategies)
    ];

    if ($redisManager->isEnabled()) {
        $redisManager->set($cacheKey, $response, RedisManager::TTL_AI_STRATEGY);
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch strategies: ' . $e->getMessage()
    ]);
}
