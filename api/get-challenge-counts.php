<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get incoming challenge count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM game_challenges
        WHERE challenged_id = ? AND status = 'pending' AND expires_at > NOW()
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $incomingCount = $stmt->fetchColumn();

    // Get outgoing challenge count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM game_challenges
        WHERE challenger_id = ? AND status = 'pending' AND expires_at > NOW()
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $outgoingCount = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'incoming_count' => (int)$incomingCount,
        'outgoing_count' => (int)$outgoingCount
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to get counts: ' . $e->getMessage()]);
}
