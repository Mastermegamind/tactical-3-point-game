<?php
require_once 'auth_check.php';

// Only super admins can change debug settings
if (!hasPermission('super_admin')) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_settings') {
        $envFile = __DIR__ . '/../.env';
        $envContent = file_get_contents($envFile);

        // Update DISPLAY_ERRORS
        $displayErrors = isset($_POST['display_errors']) ? 'true' : 'false';
        $envContent = preg_replace(
            '/DISPLAY_ERRORS=.*/m',
            'DISPLAY_ERRORS=' . $displayErrors,
            $envContent
        );

        // Update LOG_ERRORS
        $logErrors = isset($_POST['log_errors']) ? 'true' : 'false';
        $envContent = preg_replace(
            '/LOG_ERRORS=.*/m',
            'LOG_ERRORS=' . $logErrors,
            $envContent
        );

        // Update ERROR_REPORTING
        $errorReporting = $_POST['error_reporting'] ?? 'E_ALL';
        $envContent = preg_replace(
            '/ERROR_REPORTING=.*/m',
            'ERROR_REPORTING=' . $errorReporting,
            $envContent
        );

        file_put_contents($envFile, $envContent);

        logAdminActivity('update_debug_settings', 'Updated debug and error display settings');
        $message = 'Debug settings updated successfully!';
        $messageType = 'success';
    }
}

// Read current settings
$envFile = __DIR__ . '/../.env';
$env = parse_ini_file($envFile);
$displayErrors = ($env['DISPLAY_ERRORS'] ?? 'true') === 'true';
$logErrors = ($env['LOG_ERRORS'] ?? 'true') === 'true';
$errorReporting = $env['ERROR_REPORTING'] ?? 'E_ALL';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Debug Settings - Admin Panel</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8f9fa; }
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        .admin-nav {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .nav-pills .nav-link {
            border-radius: 8px;
            font-weight: 500;
            color: #495057;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .setting-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1>⚙️ Debug & Error Display Settings</h1>
            <p class="mb-0">Configure error reporting and debugging options</p>
        </div>
    </div>

    <div class="container">
        <div class="admin-nav">
            <ul class="nav nav-pills">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="games.php">Games</a></li>
                <li class="nav-item"><a class="nav-link" href="statistics.php">Statistics</a></li>
                <li class="nav-item"><a class="nav-link" href="ai-training.php">AI Training</a></li>
                <li class="nav-item"><a class="nav-link" href="ai-knowledge-base.php">AI Knowledge Base</a></li>
                <li class="nav-item"><a class="nav-link" href="admins.php">Admins</a></li>
                <li class="nav-item"><a class="nav-link" href="errors.php">Error Logs</a></li>
                <li class="nav-item"><a class="nav-link active" href="debug-settings.php">Debug Settings</a></li>
            </ul>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="warning-box">
            <strong>⚠️ Production Warning:</strong> Enable error display only in development environments.
            Displaying errors in production can expose sensitive information and security vulnerabilities.
        </div>

        <div class="content-card">
            <h5 class="mb-4">Error Display Configuration</h5>

            <form method="POST">
                <input type="hidden" name="action" value="update_settings">

                <div class="setting-card">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="display_errors" id="display_errors" <?= $displayErrors ? 'checked' : '' ?>>
                        <label class="form-check-label" for="display_errors">
                            <strong>Display Errors on Screen</strong>
                        </label>
                    </div>
                    <p class="text-muted small mb-0">
                        When enabled, errors will be displayed directly in the browser with detailed information including:
                        file name, line number, error message, code context, and stack trace. This is useful for debugging
                        but should be DISABLED in production.
                    </p>
                </div>

                <div class="setting-card">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="log_errors" id="log_errors" <?= $logErrors ? 'checked' : '' ?>>
                        <label class="form-check-label" for="log_errors">
                            <strong>Log Errors to Database</strong>
                        </label>
                    </div>
                    <p class="text-muted small mb-0">
                        When enabled, all errors are logged to the database and file system. You can view logged errors
                        in the Error Logs page. This should be ENABLED in both development and production.
                    </p>
                </div>

                <div class="setting-card">
                    <label class="form-label"><strong>Error Reporting Level</strong></label>
                    <select name="error_reporting" class="form-select mb-3">
                        <option value="E_ALL" <?= $errorReporting === 'E_ALL' ? 'selected' : '' ?>>
                            E_ALL - All errors and warnings (Recommended for Development)
                        </option>
                        <option value="E_ERROR" <?= $errorReporting === 'E_ERROR' ? 'selected' : '' ?>>
                            E_ERROR - Fatal run-time errors only
                        </option>
                        <option value="E_WARNING" <?= $errorReporting === 'E_WARNING' ? 'selected' : '' ?>>
                            E_WARNING - Run-time warnings only
                        </option>
                        <option value="0" <?= $errorReporting === '0' ? 'selected' : '' ?>>
                            0 - Turn off error reporting
                        </option>
                    </select>
                    <p class="text-muted small mb-0">
                        Controls which PHP errors are reported. E_ALL reports all errors including notices and deprecation warnings.
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        💾 Save Settings
                    </button>
                    <a href="errors.php" class="btn btn-outline-secondary">
                        📋 View Error Logs
                    </a>
                </div>
            </form>
        </div>

        <div class="content-card">
            <h5 class="mb-3">Current Configuration Status</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Setting</th>
                            <th>Current Value</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Display Errors</strong></td>
                            <td><code><?= $displayErrors ? 'true' : 'false' ?></code></td>
                            <td>
                                <?php if ($displayErrors): ?>
                                    <span class="badge bg-warning">⚠️ Development Mode</span>
                                <?php else: ?>
                                    <span class="badge bg-success">✅ Production Safe</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Log Errors</strong></td>
                            <td><code><?= $logErrors ? 'true' : 'false' ?></code></td>
                            <td>
                                <?php if ($logErrors): ?>
                                    <span class="badge bg-success">✅ Enabled</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">❌ Disabled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Error Reporting</strong></td>
                            <td><code><?= $errorReporting ?></code></td>
                            <td>
                                <?php if ($errorReporting === 'E_ALL'): ?>
                                    <span class="badge bg-info">🔍 Verbose</span>
                                <?php elseif ($errorReporting === '0'): ?>
                                    <span class="badge bg-secondary">🔇 Silent</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">📊 Selective</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="content-card">
            <h5 class="mb-3">📖 Quick Guide</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6>🧪 Development Environment</h6>
                    <ul class="small">
                        <li>✅ Display Errors: <strong>ON</strong></li>
                        <li>✅ Log Errors: <strong>ON</strong></li>
                        <li>✅ Error Reporting: <strong>E_ALL</strong></li>
                    </ul>
                    <p class="text-muted small">
                        See all errors immediately while developing. Helpful for catching bugs early.
                    </p>
                </div>
                <div class="col-md-6">
                    <h6>🚀 Production Environment</h6>
                    <ul class="small">
                        <li>❌ Display Errors: <strong>OFF</strong></li>
                        <li>✅ Log Errors: <strong>ON</strong></li>
                        <li>✅ Error Reporting: <strong>E_ALL or E_ERROR</strong></li>
                    </ul>
                    <p class="text-muted small">
                        Log errors silently without exposing details to users. Review logs in admin panel.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
