<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/RedisManager.php';
require_once __DIR__ . '/../ai/AILearningEngine.php';

// Only allow authenticated users (you can add admin check here)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $difficulty = $_GET['difficulty'] ?? 'hard';
    $validDifficulties = ['easy', 'medium', 'hard'];

    if (!in_array($difficulty, $validDifficulties)) {
        echo json_encode(['success' => false, 'message' => 'Invalid difficulty level']);
        exit;
    }

    $learningEngine = new AILearningEngine($conn, $difficulty);

    // Train and save the strategy
    $learnedData = $learningEngine->saveLearnedStrategy();

    // Save to database for AI Knowledge Base
    $stats = $learnedData['stats'] ?? [];
    $patterns = $learnedData['patterns'] ?? [];
    $weights = $learnedData['weights'] ?? [];

    $totalGames = $stats['total_games'] ?? 0;
    $aiWins = $stats['ai_wins'] ?? 0;
    $winRate = $totalGames > 0 ? ($aiWins / $totalGames * 100) : 0;
    $avgMoves = $stats['avg_moves'] ?? 0;

    // Insert training record into database (session_id is NULL for training summaries)
    $stmt = $conn->prepare("
        INSERT INTO ai_training_data
        (session_id, game_outcome, difficulty_level, total_moves, position_weights,
         opening_patterns, winning_sequences, games_analyzed, win_rate, avg_moves, created_at)
        VALUES (NULL, 'ai_win', ?, 0, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $difficulty,
        json_encode($weights['position_weights'] ?? []),
        json_encode($patterns['opening_moves'] ?? []),
        json_encode($patterns['winning_sequences'] ?? []),
        $totalGames,
        round($winRate, 2),
        round($avgMoves, 2)
    ]);

    // Generate strategies from training data and save to ai_strategies table
    $strategiesCreated = $learningEngine->generateStrategiesFromTraining();

    $redisManager = RedisManager::getInstance();
    if ($redisManager->isEnabled()) {
        $redisManager->invalidateAIStrategies($difficulty);
        $redisManager->invalidateTrainingData($difficulty);
        $redisManager->delete("ai:training:all");
        $redisManager->deletePattern("ai:stats:*");
        $redisManager->cacheTrainingData($difficulty, $learnedData);
    }

    $response = [
        'success' => true,
        'message' => 'AI training completed successfully',
        'data' => $learnedData,
        'strategies_created' => $strategiesCreated,
        'info' => "Analyzed {$totalGames} games and created {$strategiesCreated} strategies"
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Training failed: ' . $e->getMessage()
    ]);
}
