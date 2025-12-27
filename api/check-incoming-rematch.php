<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Missing session_id']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check for pending rematch requests for this user on this session
    $stmt = $conn->prepare("
        SELECT rr.id, rr.requester_id, u.username as requester_name
        FROM rematch_requests rr
        JOIN users u ON rr.requester_id = u.id
        WHERE rr.original_session_id = ?
        AND rr.recipient_id = ?
        AND rr.status = 'pending'
        AND rr.expires_at > NOW()
        ORDER BY rr.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id']]);
    $request = $stmt->fetch();

    if ($request) {
        echo json_encode([
            'success' => true,
            'has_request' => true,
            'request_id' => $request['id'],
            'requester_name' => $request['requester_name']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'has_request' => false
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to check requests: ' . $e->getMessage()
    ]);
}
