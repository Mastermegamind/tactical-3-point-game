<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Okwe - Tactical Pebble Game</title>
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
            padding: 1rem;
        }

        .setup-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 3rem;
            max-width: 700px;
            width: 100%;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .step {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: 700;
            margin-right: 0.5rem;
        }

        code {
            background: #e9ecef;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .alert {
            border-radius: 12px;
            border: none;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: white;
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <h1>Okwe - Tactical Pebble Game Setup</h1>
        <p class="text-muted mb-4">Follow these steps to set up your game</p>

        <?php
        $setupComplete = false;
        $errors = [];
        $success = [];

        // Check if database setup is being run
        if (isset($_POST['setup_database'])) {
            require_once __DIR__ . '/config/database.php';

            try {
                // Load .env
                $envFile = __DIR__ . '/.env';
                if (!file_exists($envFile)) {
                    $errors[] = '.env file not found. Please create it from .env.example';
                } else {
                    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($lines as $line) {
                        if (strpos(trim($line), '#') === 0) continue;
                        list($key, $value) = explode('=', $line, 2);
                        $_ENV[trim($key)] = trim($value);
                    }

                    $host = $_ENV['DB_HOST'] ?? 'localhost';
                    $dbname = $_ENV['DB_NAME'] ?? 'game';
                    $username = $_ENV['DB_USER'] ?? 'root';
                    $password = $_ENV['DB_PASS'] ?? 'root';
                    $port = $_ENV['DB_PORT'] ?? '3306';

                    // Connect without database first
                    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                    $conn = new PDO($dsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);

                    // Create database
                    $conn->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}`");
                    $success[] = "Database '{$dbname}' created successfully";

                    // Switch to the database
                    $conn->exec("USE `{$dbname}`");

                    // Read and execute schema
                    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
                    // Remove CREATE DATABASE lines as we already created it
                    $schema = preg_replace('/CREATE DATABASE.*?;/i', '', $schema);
                    $schema = preg_replace('/USE .*?;/i', '', $schema);

                    // Execute schema
                    $conn->exec($schema);
                    $success[] = "Database tables created successfully";

                    // Create uploads directory
                    $uploadDir = __DIR__ . '/uploads/avatars';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                        $success[] = "Upload directories created";
                    }

                    $setupComplete = true;
                }
            } catch (Exception $e) {
                $errors[] = "Setup failed: " . $e->getMessage();
            }
        }
        ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <?php foreach ($success as $msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($setupComplete): ?>
            <div class="alert alert-success">
                <h5>Setup Complete!</h5>
                <p class="mb-0">Your Okwe - Tactical Pebble Game is ready to use.</p>
            </div>
            <div class="text-center mt-4">
                <a href="register.php" class="btn btn-primary-custom">Go to Registration</a>
            </div>
        <?php else: ?>
            <div class="step">
                <h5><span class="step-number">1</span>Configure Database</h5>
                <p>Edit <code>.env</code> file with your database credentials:</p>
                <pre><code>DB_HOST=localhost
DB_NAME=game
DB_USER=root
DB_PASS=root
DB_PORT=3306</code></pre>
            </div>

            <div class="step">
                <h5><span class="step-number">2</span>Run Setup</h5>
                <p>Click the button below to create the database and tables:</p>
                <form method="POST">
                    <button type="submit" name="setup_database" class="btn btn-primary-custom">
                        Initialize Database
                    </button>
                </form>
            </div>

            <div class="step">
                <h5><span class="step-number">3</span>Verify Permissions</h5>
                <p>Ensure Apache can write to the uploads directory:</p>
                <code>chmod 755 uploads/avatars</code>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
