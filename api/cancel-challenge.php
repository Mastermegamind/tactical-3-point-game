<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$challengeId = $input['challenge_id'] ?? null;

if (!$challengeId) {
    echo json_encode(['success' => false, 'message' => 'Missing challenge_id']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Verify that the user is the challenger
    $stmt = $conn->prepare("
        SELECT id FROM game_challenges
        WHERE id = ? AND challenger_id = ? AND status = 'pending'
    ");
    $stmt->execute([$challengeId, $_SESSION['user_id']]);
    $challenge = $stmt->fetch();

    if (!$challenge) {
        echo json_encode(['success' => false, 'message' => 'Challenge not found or already responded to']);
        exit;
    }

    // Update challenge status to cancelled
    $stmt = $conn->prepare("
        UPDATE game_challenges
        SET status = 'cancelled', responded_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$challengeId]);

    echo json_encode(['success' => true, 'message' => 'Challenge cancelled']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel challenge: ' . $e->getMessage()]);
}
