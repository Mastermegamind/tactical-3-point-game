<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ErrorLogger.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$logger = ErrorLogger::getInstance();

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filter by type
$filterType = isset($_GET['type']) ? $_GET['type'] : '';

// Get errors
if ($filterType) {
    $errors = $logger->getErrorsByType($filterType, $perPage);
    $totalErrors = count($errors);
} else {
    $errors = $logger->getRecentErrors($perPage, $offset);
    $totalErrors = $logger->getErrorCount();
}

$totalPages = ceil($totalErrors / $perPage);

// Get error types for filter
$stmt = $conn->query("SELECT DISTINCT error_type FROM error_logs ORDER BY error_type");
$errorTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Logs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .card-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 2rem;
            margin-bottom: 2rem;
            border: none;
        }

        .error-row {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background 0.2s;
        }

        .error-row:hover {
            background: #f8f9fa;
        }

        .error-row:last-child {
            border-bottom: none;
        }

        .error-type-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
        }

        .badge-api { background: #667eea; color: white; }
        .badge-database { background: #fa709a; color: white; }
        .badge-validation { background: #f093fb; color: white; }
        .badge-authentication { background: #4facfe; color: white; }
        .badge-exception { background: #43e97b; color: white; }
        .badge-default { background: #6c757d; color: white; }

        .error-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .btn-filter {
            margin: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Error Logs</h2>
                    <p class="text-muted mb-0">Total Errors: <?= $totalErrors ?></p>
                </div>
                <a href="../dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>

            <div class="mb-4">
                <h6>Filter by Type:</h6>
                <a href="admin-errors.php" class="btn btn-sm btn-outline-primary btn-filter <?= !$filterType ? 'active' : '' ?>">All</a>
                <?php foreach ($errorTypes as $type): ?>
                    <a href="admin-errors.php?type=<?= urlencode($type) ?>"
                       class="btn btn-sm btn-outline-primary btn-filter <?= $filterType === $type ? 'active' : '' ?>">
                        <?= htmlspecialchars($type) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($errors)): ?>
                <div class="text-center py-5">
                    <p class="text-muted">No errors logged</p>
                </div>
            <?php else: ?>
                <div id="errors-container">
                    <?php foreach ($errors as $error): ?>
                        <div class="error-row" onclick="toggleErrorDetails(<?= $error['id'] ?>)">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="error-type-badge badge-<?= htmlspecialchars($error['error_type']) ?>">
                                            <?= htmlspecialchars($error['error_type']) ?>
                                        </span>
                                        <?php if ($error['username']): ?>
                                            <small class="text-muted">User: <?= htmlspecialchars($error['username']) ?></small>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            <?= date('M d, Y H:i:s', strtotime($error['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="fw-bold"><?= htmlspecialchars($error['error_message']) ?></div>
                                    <?php if ($error['file_path']): ?>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($error['file_path']) ?>:<?= $error['line_number'] ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-secondary">▼</span>
                            </div>
                            <div id="error-details-<?= $error['id'] ?>" class="error-details" style="display: none;">
                                <strong>Request:</strong> <?= htmlspecialchars($error['request_method'] ?? 'N/A') ?>
                                <?= htmlspecialchars($error['request_uri'] ?? 'N/A') ?><br>
                                <strong>IP:</strong> <?= htmlspecialchars($error['ip_address'] ?? 'N/A') ?><br>
                                <strong>User Agent:</strong> <?= htmlspecialchars($error['user_agent'] ?? 'N/A') ?><br>
                                <?php if ($error['stack_trace']): ?>
                                    <strong>Stack Trace:</strong><br>
                                    <pre style="white-space: pre-wrap;"><?= htmlspecialchars($error['stack_trace']) ?></pre>
                                <?php endif; ?>
                                <?php if ($error['session_data']): ?>
                                    <strong>Session Data:</strong><br>
                                    <pre><?= htmlspecialchars(json_encode(json_decode($error['session_data']), JSON_PRETTY_PRINT)) ?></pre>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1 && !$filterType): ?>
                <nav aria-label="Error logs pagination" class="mt-4">
                    <ul class="pagination pagination-sm justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleErrorDetails(errorId) {
            const details = document.getElementById('error-details-' + errorId);
            if (details.style.display === 'none') {
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
