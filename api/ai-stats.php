<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/RedisManager.php';
require_once __DIR__ . '/../ai/AILearningEngine.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $difficulty = $_GET['difficulty'] ?? 'all';

    $redisManager = RedisManager::getInstance();
    $cacheKey = "ai:stats:{$difficulty}";
    if ($redisManager->isEnabled()) {
        $cachedResponse = $redisManager->get($cacheKey);
        if ($cachedResponse !== false) {
            echo json_encode($cachedResponse);
            exit;
        }
    }

    if ($difficulty === 'all') {
        // Get stats for all difficulties
        $stmt = $conn->query("
            SELECT
                difficulty_level,
                COUNT(*) as total_games,
                SUM(CASE WHEN game_outcome = 'ai_win' THEN 1 ELSE 0 END) as ai_wins,
                SUM(CASE WHEN game_outcome = 'player_win' THEN 1 ELSE 0 END) as player_wins,
                SUM(CASE WHEN game_outcome = 'draw' THEN 1 ELSE 0 END) as draws,
                ROUND(AVG(total_moves), 2) as avg_moves,
                ROUND(AVG(CASE WHEN game_outcome = 'ai_win' THEN total_moves END), 2) as avg_moves_ai_win
            FROM ai_training_data
            GROUP BY difficulty_level
        ");
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $learningEngine = new AILearningEngine($conn, $difficulty);
        $stats = $learningEngine->getPerformanceStats();
    }

    // Load learned strategy from database (latest training record)
    $learnedStrategy = null;
    if ($difficulty !== 'all') {
        $stmt = $conn->prepare("
            SELECT position_weights, opening_patterns, winning_sequences,
                   games_analyzed, win_rate, avg_moves, created_at
            FROM ai_training_data
            WHERE session_id IS NULL AND difficulty_level = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$difficulty]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            $learnedStrategy = [
                'version' => '2.0',
                'difficulty' => $difficulty,
                'generated_at' => $record['created_at'],
                'weights' => [
                    'position_weights' => json_decode($record['position_weights'], true),
                    'total_games_analyzed' => $record['games_analyzed']
                ],
                'patterns' => [
                    'opening_moves' => json_decode($record['opening_patterns'], true),
                    'winning_sequences' => json_decode($record['winning_sequences'], true)
                ],
                'stats' => [
                    'total_games' => $record['games_analyzed'],
                    'win_rate' => $record['win_rate'],
                    'avg_moves' => $record['avg_moves']
                ]
            ];
        }
    }

    $response = [
        'success' => true,
        'stats' => $stats,
        'learned_strategy' => $learnedStrategy,
        'has_training_data' => $learnedStrategy !== null
    ];

    if ($redisManager->isEnabled()) {
        $redisManager->set($cacheKey, $response, RedisManager::TTL_TRAINING_DATA);
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch stats: ' . $e->getMessage()
    ]);
}
