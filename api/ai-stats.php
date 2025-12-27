<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../ai/AILearningEngine.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $difficulty = $_GET['difficulty'] ?? 'all';

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

    // Load learned strategy if exists
    $learnedStrategy = AILearningEngine::loadLearnedStrategy($difficulty);

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'learned_strategy' => $learnedStrategy,
        'has_training_data' => $learnedStrategy !== null
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch stats: ' . $e->getMessage()
    ]);
}
