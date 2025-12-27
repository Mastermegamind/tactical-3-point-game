<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get statistics
$stats = [];

// Total users
$stmt = $conn->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $stmt->fetchColumn();

// Active users (online now or within last 10 minutes)
$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE is_online = 1 OR last_activity > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
$stats['active_users'] = $stmt->fetchColumn();

// Total games
$stmt = $conn->query("SELECT COUNT(*) FROM game_sessions");
$stats['total_games'] = $stmt->fetchColumn();

// Games today
$stmt = $conn->query("SELECT COUNT(*) FROM game_sessions WHERE DATE(started_at) = CURDATE()");
$stats['games_today'] = $stmt->fetchColumn();

// Completed games
$stmt = $conn->query("SELECT COUNT(*) FROM game_sessions WHERE status = 'completed'");
$stats['completed_games'] = $stmt->fetchColumn();

// Average rating
$stmt = $conn->query("SELECT AVG(rating) FROM users WHERE rating > 0");
$stats['avg_rating'] = round($stmt->fetchColumn(), 1);

// AI training data count
$stmt = $conn->query("SELECT COUNT(*) FROM ai_training_data");
$stats['ai_training_count'] = $stmt->fetchColumn();

// Recent activity
$stmt = $conn->prepare("
    SELECT username, last_activity, is_online
    FROM users
    ORDER BY last_activity DESC
    LIMIT 10
");
$stmt->execute();
$recentUsers = $stmt->fetchAll();

// Recent games
$stmt = $conn->prepare("
    SELECT
        gs.id,
        gs.game_mode,
        gs.status,
        gs.started_at,
        gs.completed_at,
        u1.username as player1,
        u2.username as player2,
        uw.username as winner
    FROM game_sessions gs
    LEFT JOIN users u1 ON gs.player1_id = u1.id
    LEFT JOIN users u2 ON gs.player2_id = u2.id
    LEFT JOIN users uw ON gs.winner_id = uw.id
    ORDER BY gs.started_at DESC
    LIMIT 10
");
$stmt->execute();
$recentGames = $stmt->fetchAll();

// Admin activity log
$stmt = $conn->prepare("
    SELECT
        aal.*,
        a.username as admin_username
    FROM admin_activity_log aal
    JOIN admins a ON aal.admin_id = a.id
    ORDER BY aal.created_at DESC
    LIMIT 10
");
$stmt->execute();
$adminActivity = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - Tactical Pebble Game</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-pills .nav-link {
            border-radius: 8px;
            font-weight: 500;
            color: #495057;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .table-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .table-card h5 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: #212529;
        }

        .badge-online {
            background: #28a745;
            color: white;
        }

        .badge-offline {
            background: #6c757d;
            color: white;
        }

        .admin-nav {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .btn-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-admin:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>🎮 Admin Dashboard</h1>
                    <p class="mb-0">Welcome back, <?= htmlspecialchars($_SESSION['admin_full_name'] ?? $_SESSION['admin_username']) ?></p>
                </div>
                <div>
                    <span class="badge bg-light text-dark me-2"><?= ucfirst($_SESSION['admin_role']) ?></span>
                    <a href="logout.php" class="btn btn-sm btn-light">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="admin-nav">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="games.php">Games</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="statistics.php">Statistics</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ai-training.php">AI Training</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ai-knowledge-base.php">AI Knowledge Base</a>
                </li>
                <?php if (hasPermission('super_admin')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admins.php">Manage Admins</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="errors.php">Error Logs</a>
                </li>
            </ul>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['active_users']) ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['total_games']) ?></div>
                    <div class="stat-label">Total Games</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['games_today']) ?></div>
                    <div class="stat-label">Games Today</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['completed_games']) ?></div>
                    <div class="stat-label">Completed Games</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['avg_rating'] ?></div>
                    <div class="stat-label">Avg Rating</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['ai_training_count']) ?></div>
                    <div class="stat-label">AI Training Data</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= round(($stats['completed_games'] / max($stats['total_games'], 1)) * 100) ?>%</div>
                    <div class="stat-label">Completion Rate</div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="table-card">
                    <h5>Recent Users</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Last Activity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= $user['last_activity'] ? date('M d, H:i', strtotime($user['last_activity'])) : 'Never' ?></td>
                                    <td>
                                        <?php if ($user['is_online']): ?>
                                            <span class="badge badge-online">Online</span>
                                        <?php else: ?>
                                            <span class="badge badge-offline">Offline</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="table-card">
                    <h5>Recent Games</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Players</th>
                                    <th>Mode</th>
                                    <th>Status</th>
                                    <th>Started</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentGames as $game): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($game['player1']) ?>
                                        <?php if ($game['player2']): ?>
                                            vs <?= htmlspecialchars($game['player2']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($game['game_mode']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $game['status'] === 'completed' ? 'success' : 'warning' ?>">
                                            <?= $game['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, H:i', strtotime($game['started_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Activity Log -->
        <?php if (hasPermission('admin')): ?>
        <div class="table-card">
            <h5>Admin Activity Log</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adminActivity as $activity): ?>
                        <tr>
                            <td><?= htmlspecialchars($activity['admin_username']) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($activity['action']) ?></span></td>
                            <td><?= htmlspecialchars($activity['description'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($activity['ip_address'] ?? '-') ?></td>
                            <td><?= date('M d, H:i:s', strtotime($activity['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
