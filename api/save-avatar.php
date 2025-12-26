<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$avatarPath = null;

// Handle uploaded file
if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar_file'];

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG and PNG allowed.']);
        exit;
    }

    // Validate file size (5MB max)
    if ($file['size'] > 5242880) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
        exit;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
    $uploadDir = __DIR__ . '/../uploads/avatars/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $avatarPath = 'uploads/avatars/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
        exit;
    }
}
// Handle preset avatar
elseif (isset($_POST['avatar_preset'])) {
    $avatarPath = $_POST['avatar_preset'];
}

if (!$avatarPath) {
    echo json_encode(['success' => false, 'message' => 'No avatar selected.']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Delete old uploaded avatar if exists
    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $oldAvatar = $stmt->fetchColumn();

    if ($oldAvatar && strpos($oldAvatar, 'uploads/') === 0) {
        $oldPath = __DIR__ . '/../' . $oldAvatar;
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    // Update avatar in database
    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->execute([$avatarPath, $_SESSION['user_id']]);

    $_SESSION['avatar'] = $avatarPath;

    echo json_encode([
        'success' => true,
        'message' => 'Avatar updated successfully',
        'avatar' => $avatarPath
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to save avatar: ' . $e->getMessage()]);
}
