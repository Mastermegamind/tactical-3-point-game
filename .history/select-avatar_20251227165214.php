<?php
require_once __DIR__ . '/config/session.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get user info
$stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$currentAvatar = $user['avatar'] ?? null;

// Preset avatars
$presetAvatars = [
    'avatar1.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#667eea"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><path d="M30 65 Q50 75 70 65" stroke="#fff" stroke-width="3" fill="none"/></svg>',
    'avatar2.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#f093fb"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><circle cx="50" cy="65" r="8" fill="#fff"/></svg>',
    'avatar3.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#4facfe"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><rect x="35" y="60" width="30" height="5" fill="#fff"/></svg>',
    'avatar4.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#43e97b"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><path d="M35 65 L50 60 L65 65" stroke="#fff" stroke-width="3" fill="none"/></svg>',
    'avatar5.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#fa709a"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><ellipse cx="50" cy="65" rx="15" ry="8" fill="#fff"/></svg>',
    'avatar6.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#764ba2"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><path d="M30 70 Q50 60 70 70" stroke="#fff" stroke-width="3" fill="none"/></svg>',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Avatar - Okwe - Tactical Pebble Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .avatar-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 3rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .avatar-option {
            aspect-ratio: 1;
            border-radius: 50%;
            border: 3px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s;
            padding: 1rem;
            background: white;
        }

        .avatar-option:hover {
            transform: scale(1.05);
            border-color: #667eea;
        }

        .avatar-option.selected {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }

        .avatar-option svg {
            width: 100%;
            height: 100%;
        }

        .upload-section {
            text-align: center;
            padding: 2rem;
            border: 2px dashed #dee2e6;
            border-radius: 16px;
            margin-bottom: 2rem;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.2s;
        }

        .upload-section:hover {
            border-color: #667eea;
            background: #f0f3ff;
        }

        .upload-section input {
            display: none;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
            transition: all 0.2s;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary-custom {
            background: #e9ecef;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: #495057;
            transition: all 0.2s;
        }

        .btn-secondary-custom:hover {
            background: #dee2e6;
        }

        .current-avatar {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            border-radius: 50%;
            border: 4px solid #667eea;
            padding: 1rem;
            background: white;
        }

        .current-avatar img, .current-avatar svg {
            width: 100%;
            height: 100%;
            border-radius: 50%;
        }

        .alert {
            border-radius: 12px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="avatar-card">
        <div class="page-header">
            <h1 class="page-title">Choose Your Avatar</h1>
            <p class="page-subtitle">Select from presets or upload your own image</p>
        </div>

        <div id="alert-container"></div>

        <?php if ($currentAvatar): ?>
        <div class="current-avatar">
            <?php if (strpos($currentAvatar, 'avatar') !== false && isset($presetAvatars[$currentAvatar])): ?>
                <?= $presetAvatars[$currentAvatar] ?>
            <?php else: ?>
                <img src="<?= htmlspecialchars($currentAvatar) ?>" alt="Current Avatar">
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <h5 class="mb-3">Preset Avatars</h5>
        <div class="avatar-grid">
            <?php foreach ($presetAvatars as $filename => $svg): ?>
            <div class="avatar-option" data-avatar="<?= $filename ?>" onclick="selectAvatar('<?= $filename ?>')">
                <?= $svg ?>
            </div>
            <?php endforeach; ?>
        </div>

        <h5 class="mb-3">Upload Custom Avatar</h5>
        <label for="avatar-upload" class="upload-section">
            <div>
                <h6>Click to upload image</h6>
                <p class="text-muted mb-0">PNG, JPG up to 5MB</p>
            </div>
            <input type="file" id="avatar-upload" accept="image/png,image/jpeg,image/jpg" onchange="handleUpload(this)">
        </label>

        <div class="d-flex gap-2 justify-content-center">
            <button class="btn btn-secondary-custom" onclick="skipAvatar()">Skip for Now</button>
            <button class="btn btn-primary-custom" id="saveBtn" onclick="saveAvatar()" disabled>Continue</button>
        </div>
    </div>

    <script>
        let selectedAvatar = null;
        let uploadedFile = null;

        function selectAvatar(filename) {
            selectedAvatar = filename;
            uploadedFile = null;

            document.querySelectorAll('.avatar-option').forEach(el => {
                el.classList.remove('selected');
            });
            document.querySelector(`[data-avatar="${filename}"]`).classList.add('selected');

            document.getElementById('saveBtn').disabled = false;
        }

        function handleUpload(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                if (file.size > 5242880) {
                    showAlert('File size must be less than 5MB', 'danger');
                    return;
                }

                uploadedFile = file;
                selectedAvatar = null;

                document.querySelectorAll('.avatar-option').forEach(el => {
                    el.classList.remove('selected');
                });

                document.getElementById('saveBtn').disabled = false;
            }
        }

        async function saveAvatar() {
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            const formData = new FormData();

            if (uploadedFile) {
                formData.append('avatar_file', uploadedFile);
            } else if (selectedAvatar) {
                formData.append('avatar_preset', selectedAvatar);
            }

            try {
                const response = await fetch('api/save-avatar.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Avatar saved successfully! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    showAlert(data.message || 'Failed to save avatar', 'danger');
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Continue';
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'danger');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Continue';
            }
        }

        function skipAvatar() {
            window.location.href = 'dashboard.php';
        }

        function showAlert(message, type = 'danger') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.getElementById('alert-container').innerHTML = alertHtml;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
