<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
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

    echo json_encode([
        'success' => true,
        'message' => 'AI training completed successfully',
        'data' => $learnedData
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Training failed: ' . $e->getMessage()
    ]);
}
