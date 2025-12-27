<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$requestId = $_GET['request_id'] ?? null;

if (!$requestId) {
    echo json_encode(['success' => false, 'message' => 'Missing request_id']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT status, new_session_id
        FROM rematch_requests
        WHERE id = ? AND requester_id = ?
    ");
    $stmt->execute([$requestId, $_SESSION['user_id']]);
    $request = $stmt->fetch();

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'status' => $request['status'],
        'new_session_id' => $request['new_session_id']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to check status: ' . $e->getMessage()
    ]);
}
