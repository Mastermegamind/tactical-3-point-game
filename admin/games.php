<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$status = $_GET['status'] ?? '';
$mode = $_GET['mode'] ?? '';

$whereConditions = [];
$params = [];

if ($status) {
    $whereConditions[] = "gs.status = ?";
    $params[] = $status;
}

if ($mode) {
    $whereConditions[] = "gs.game_mode = ?";
    $params[] = $mode;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$stmt = $conn->prepare("SELECT COUNT(*) FROM game_sessions gs $whereClause");
$stmt->execute($params);
$totalGames = $stmt->fetchColumn();
$totalPages = ceil($totalGames / $perPage);

// Get games
$stmt = $conn->prepare("
    SELECT
        gs.*,
        u1.username as player1_name,
        u2.username as player2_name,
        uw.username as winner_name
    FROM game_sessions gs
    LEFT JOIN users u1 ON gs.player1_id = u1.id
    LEFT JOIN users u2 ON gs.player2_id = u2.id
    LEFT JOIN users uw ON gs.winner_id = uw.id
    $whereClause
    ORDER BY gs.started_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$games = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Game Management - Admin Panel</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: #f8f9fa;
        }

        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1>🎮 Game Management</h1>
        </div>
    </div>

    <div class="container">
        <div class="admin-nav">
            <ul class="nav nav-pills">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link active" href="games.php">Games</a></li>
                <li class="nav-item"><a class="nav-link" href="statistics.php">Statistics</a></li>
                <li class="nav-item"><a class="nav-link" href="ai-training.php">AI Training</a></li>
                <li class="nav-item"><a class="nav-link" href="ai-knowledge-base.php">AI Knowledge Base</a></li>
            </ul>
        </div>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5>All Games (<?= number_format($totalGames) ?>)</h5>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" onchange="location.href='?status='+this.value+'&mode=<?= urlencode($mode) ?>'">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                    <select class="form-select form-select-sm" onchange="location.href='?mode='+this.value+'&status=<?= urlencode($status) ?>'">
                        <option value="">All Modes</option>
                        <option value="pvp" <?= $mode === 'pvp' ? 'selected' : '' ?>>PvP</option>
                        <option value="pvc-easy" <?= $mode === 'pvc-easy' ? 'selected' : '' ?>>vs AI (Easy)</option>
                        <option value="pvc-medium" <?= $mode === 'pvc-medium' ? 'selected' : '' ?>>vs AI (Medium)</option>
                        <option value="pvc-hard" <?= $mode === 'pvc-hard' ? 'selected' : '' ?>>vs AI (Hard)</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Players</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>Winner</th>
                            <th>Started</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($games as $game): ?>
                        <tr>
                            <td><?= $game['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($game['player1_name']) ?></strong>
                                <?php if ($game['player2_name']): ?>
                                    vs <?= htmlspecialchars($game['player2_name']) ?>
                                <?php else: ?>
                                    vs AI
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-info"><?= $game['game_mode'] ?></span></td>
                            <td>
                                <span class="badge bg-<?= $game['status'] === 'completed' ? 'success' : 'warning' ?>">
                                    <?= $game['status'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($game['winner_name']): ?>
                                    <?= htmlspecialchars($game['winner_name']) ?>
                                <?php elseif ($game['status'] === 'completed'): ?>
                                    <span class="text-muted">Draw/AI</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, H:i', strtotime($game['started_at'])) ?></td>
                            <td>
                                <?php if ($game['completed_at']): ?>
                                    <?php
                                    $duration = strtotime($game['completed_at']) - strtotime($game['started_at']);
                                    echo gmdate("i:s", $duration);
                                    ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&mode=<?= urlencode($mode) ?>">Previous</a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&mode=<?= urlencode($mode) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&mode=<?= urlencode($mode) ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
