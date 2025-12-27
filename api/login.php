<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if login is email or username
    $stmt = $conn->prepare("SELECT id, username, email, password, avatar FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }

    // Check if user is banned
    $stmt = $conn->prepare("SELECT is_banned, ban_reason, ban_expires_at FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $banInfo = $stmt->fetch();

    if ($banInfo['is_banned']) {
        // Check if ban has expired
        if ($banInfo['ban_expires_at'] && strtotime($banInfo['ban_expires_at']) < time()) {
            // Ban has expired, unban the user
            $stmt = $conn->prepare("
                UPDATE users
                SET is_banned = 0, ban_reason = NULL, banned_at = NULL, banned_by = NULL, ban_expires_at = NULL
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
        } else {
            // User is still banned
            $banMessage = 'Your account has been banned';
            if ($banInfo['ban_reason']) {
                $banMessage .= ': ' . $banInfo['ban_reason'];
            }
            if ($banInfo['ban_expires_at']) {
                $banMessage .= ' (Until: ' . date('M d, Y H:i', strtotime($banInfo['ban_expires_at'])) . ')';
            }
            echo json_encode(['success' => false, 'message' => $banMessage, 'banned' => true]);
            exit;
        }
    }

    // Update last login and online status
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW(), is_online = TRUE, last_activity = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['avatar'] = $user['avatar'];

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'avatar' => $user['avatar']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
}
