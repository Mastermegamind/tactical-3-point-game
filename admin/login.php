<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Login successful
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_full_name'] = $admin['full_name'];

                // Update last login
                $stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$admin['id']]);

                // Log session
                $sessionToken = bin2hex(random_bytes(32));
                $stmt = $conn->prepare("
                    INSERT INTO admin_sessions (admin_id, session_token, ip_address, user_agent)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $admin['id'],
                    $sessionToken,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);

                // Log activity
                $stmt = $conn->prepare("
                    INSERT INTO admin_activity_log (admin_id, action, description, ip_address)
                    VALUES (?, 'login', 'Admin logged in', ?)
                ");
                $stmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR'] ?? null]);

                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
            error_log('Admin login error: ' . $e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Login - Tactical Pebble Game</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }

        .login-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .admin-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            display: inline-block;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1>🔐 Admin Panel</h1>
            <p>Tactical Pebble Game</p>
            <span class="admin-badge">Administrative Access</span>
        </div>

        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        required
                        autofocus
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        required>
                </div>

                <button type="submit" class="btn btn-login">
                    Login to Admin Panel
                </button>
            </form>

            <div class="back-link">
                <a href="../dashboard.php">← Back to Game Dashboard</a>
            </div>

            <div class="mt-4 text-center text-muted" style="font-size: 0.875rem;">
                <p class="mb-1"><strong>Default Credentials:</strong></p>
                <p class="mb-0">Username: <code>admin</code></p>
                <p class="mb-0">Password: <code>admin123</code></p>
            </div>
        </div>
    </div>
</body>
</html>
