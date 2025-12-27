<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Only admins and super admins can view error logs
if (!hasPermission('admin')) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filter by type
$filterType = isset($_GET['type']) ? $_GET['type'] : '';

// Handle clear all errors action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'clear_all' && hasPermission('super_admin')) {
        $conn->query("DELETE FROM error_logs");
        logAdminActivity('clear_errors', 'Cleared all error logs');
        header('Location: errors.php');
        exit;
    } elseif ($_POST['action'] === 'delete_error' && hasPermission('admin')) {
        $errorId = (int)$_POST['error_id'];
        $stmt = $conn->prepare("DELETE FROM error_logs WHERE id = ?");
        $stmt->execute([$errorId]);
        logAdminActivity('delete_error', "Deleted error log ID: $errorId");
        header('Location: errors.php' . ($filterType ? '?type=' . urlencode($filterType) : ''));
        exit;
    }
}

// Build query
$query = "SELECT * FROM error_logs";
$countQuery = "SELECT COUNT(*) FROM error_logs";

if ($filterType) {
    $query .= " WHERE error_type = :type";
    $countQuery .= " WHERE error_type = :type";
}

$query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

// Get total count
$stmt = $conn->prepare($countQuery);
if ($filterType) {
    $stmt->bindValue(':type', $filterType, PDO::PARAM_STR);
}
$stmt->execute();
$totalErrors = $stmt->fetchColumn();

// Get errors
$stmt = $conn->prepare($query);
if ($filterType) {
    $stmt->bindValue(':type', $filterType, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$errors = $stmt->fetchAll();

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
            background: #f8f9fa;
            min-height: 100vh;
        }

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
    <div class="admin-header">
        <div class="container">
            <h1>🐛 Error Logs & Debugging</h1>
            <p class="mb-0">Monitor and troubleshoot system errors</p>
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
                <?php if (hasPermission('super_admin')): ?>
                <li class="nav-item"><a class="nav-link" href="admins.php">Admins</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link active" href="errors.php">Error Logs</a></li>
            </ul>
        </div>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5>Error Logs (<?= number_format($totalErrors) ?>)</h5>
                    <small class="text-muted">Real-time error tracking and debugging</small>
                </div>
                <div class="d-flex gap-2">
                    <?php if (hasPermission('super_admin')): ?>
                    <button class="btn btn-sm btn-danger" onclick="clearAllErrors()">
                        🗑️ Clear All
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-primary" onclick="location.reload()">
                        🔄 Refresh
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="mb-2">Filter by Type:</h6>
                <a href="errors.php" class="btn btn-sm btn-outline-primary btn-filter <?= !$filterType ? 'active' : '' ?>">All</a>
                <?php foreach ($errorTypes as $type): ?>
                    <a href="errors.php?type=<?= urlencode($type) ?>"
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
                                        <span class="error-type-badge badge-default">
                                            <?= htmlspecialchars($error['error_type']) ?>
                                        </span>
                                        <small class="text-muted">
                                            <?= date('M d, Y H:i:s', strtotime($error['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="fw-bold"><?= htmlspecialchars($error['error_message']) ?></div>
                                    <?php if ($error['error_file']): ?>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($error['error_file']) ?>:<?= $error['error_line'] ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (hasPermission('admin')): ?>
                                    <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deleteError(<?= $error['id'] ?>)">
                                        🗑️
                                    </button>
                                    <?php endif; ?>
                                    <span class="badge bg-secondary">▼</span>
                                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleErrorDetails(errorId) {
            const details = document.getElementById('error-details-' + errorId);
            if (details.style.display === 'none') {
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        }

        function clearAllErrors() {
            Swal.fire({
                title: 'Clear All Error Logs?',
                text: 'This will permanently delete all error logs from the database. This cannot be undone!',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, clear all'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = '<input type="hidden" name="action" value="clear_all">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function deleteError(errorId) {
            Swal.fire({
                title: 'Delete Error?',
                text: 'Remove this error from the log?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_error">
                        <input type="hidden" name="error_id" value="${errorId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
