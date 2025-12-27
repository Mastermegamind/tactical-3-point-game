<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Overall Statistics
$stats = [];

// User statistics
$stmt = $conn->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)");
$stats['new_users_week'] = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAYS)");
$stats['new_users_month'] = $stmt->fetchColumn();

// Game statistics
$stmt = $conn->query("SELECT COUNT(*) FROM game_sessions");
$stats['total_games'] = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM game_sessions WHERE status = 'completed'");
$stats['completed_games'] = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM game_sessions WHERE status = 'active'");
$stats['active_games'] = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM game_sessions WHERE DATE(started_at) = CURDATE()");
$stats['games_today'] = $stmt->fetchColumn();

// Games by mode
$stmt = $conn->query("
    SELECT game_mode, COUNT(*) as count
    FROM game_sessions
    GROUP BY game_mode
    ORDER BY count DESC
");
$gamesByMode = $stmt->fetchAll();

// Games per day (last 7 days)
$stmt = $conn->query("
    SELECT DATE(started_at) as date, COUNT(*) as count
    FROM game_sessions
    WHERE started_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)
    GROUP BY DATE(started_at)
    ORDER BY date ASC
");
$gamesPerDay = $stmt->fetchAll();

// AI Performance
$stmt = $conn->query("
    SELECT
        difficulty_level,
        COUNT(*) as total_games,
        SUM(CASE WHEN game_outcome = 'ai_win' THEN 1 ELSE 0 END) as ai_wins,
        SUM(CASE WHEN game_outcome = 'player_win' THEN 1 ELSE 0 END) as player_wins,
        SUM(CASE WHEN game_outcome = 'draw' THEN 1 ELSE 0 END) as draws,
        AVG(total_moves) as avg_moves,
        AVG(game_duration_seconds) as avg_duration
    FROM ai_training_data
    GROUP BY difficulty_level
");
$aiPerformance = $stmt->fetchAll();

// Top Players
$stmt = $conn->query("
    SELECT username, rating, wins, losses, draws,
           (wins + losses + draws) as total_games,
           CASE WHEN (wins + losses) > 0
                THEN ROUND((wins / (wins + losses)) * 100, 1)
                ELSE 0
           END as win_rate
    FROM users
    WHERE (wins + losses + draws) > 0
    ORDER BY rating DESC
    LIMIT 10
");
$topPlayers = $stmt->fetchAll();

// Recent completions
$stmt = $conn->query("SELECT COUNT(*) FROM game_sessions WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stats['games_last_hour'] = $stmt->fetchColumn();

// Average game duration
$stmt = $conn->query("
    SELECT AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_duration
    FROM game_sessions
    WHERE status = 'completed' AND completed_at IS NOT NULL
");
$stats['avg_game_duration'] = $stmt->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Statistics - Admin Panel</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
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
            <div class="d-flex justify-content-between align-items-center">
                <h1>📊 Game Statistics</h1>
                <a href="index.php" class="btn btn-sm btn-light">← Dashboard</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="admin-nav">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="games.php">Games</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="statistics.php">Statistics</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ai-training.php">AI Training</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ai-knowledge-base.php">AI Knowledge Base</a>
                </li>
            </ul>
        </div>

        <!-- Summary Stats -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                    <div class="stat-label">Total Users</div>
                    <small class="text-muted">+<?= $stats['new_users_week'] ?> this week</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['total_games']) ?></div>
                    <div class="stat-label">Total Games</div>
                    <small class="text-muted"><?= $stats['completed_games'] ?> completed</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['games_today']) ?></div>
                    <div class="stat-label">Games Today</div>
                    <small class="text-muted"><?= $stats['games_last_hour'] ?> in last hour</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['avg_game_duration'] ? gmdate("i:s", $stats['avg_game_duration']) : '0:00' ?></div>
                    <div class="stat-label">Avg Game Duration</div>
                    <small class="text-muted">minutes:seconds</small>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-card">
                    <h5 class="mb-4">Games Per Day (Last 7 Days)</h5>
                    <canvas id="gamesPerDayChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-card">
                    <h5 class="mb-4">Games by Mode</h5>
                    <canvas id="gamesModeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- AI Performance -->
        <div class="chart-card">
            <h5 class="mb-4">AI Performance by Difficulty</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Difficulty</th>
                            <th>Total Games</th>
                            <th>AI Wins</th>
                            <th>Player Wins</th>
                            <th>Draws</th>
                            <th>Win Rate</th>
                            <th>Avg Moves</th>
                            <th>Avg Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aiPerformance as $perf): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?= ucfirst($perf['difficulty_level']) ?></span></td>
                            <td><?= number_format($perf['total_games']) ?></td>
                            <td><?= number_format($perf['ai_wins']) ?></td>
                            <td><?= number_format($perf['player_wins']) ?></td>
                            <td><?= number_format($perf['draws']) ?></td>
                            <td>
                                <?php
                                $winRate = $perf['total_games'] > 0 ? ($perf['ai_wins'] / $perf['total_games'] * 100) : 0;
                                ?>
                                <span class="badge bg-<?= $winRate > 60 ? 'success' : ($winRate > 40 ? 'warning' : 'danger') ?>">
                                    <?= number_format($winRate, 1) ?>%
                                </span>
                            </td>
                            <td><?= number_format($perf['avg_moves'], 1) ?></td>
                            <td><?= gmdate("i:s", $perf['avg_duration'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Players -->
        <div class="chart-card">
            <h5 class="mb-4">Top Players by Rating</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Username</th>
                            <th>Rating</th>
                            <th>Games</th>
                            <th>W/L/D</th>
                            <th>Win Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topPlayers as $index => $player): ?>
                        <tr>
                            <td>
                                <?php if ($index === 0): ?>
                                    🥇
                                <?php elseif ($index === 1): ?>
                                    🥈
                                <?php elseif ($index === 2): ?>
                                    🥉
                                <?php else: ?>
                                    #<?= $index + 1 ?>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($player['username']) ?></strong></td>
                            <td><span class="badge bg-primary"><?= $player['rating'] ?></span></td>
                            <td><?= $player['total_games'] ?></td>
                            <td><?= $player['wins'] ?>/<?= $player['losses'] ?>/<?= $player['draws'] ?></td>
                            <td>
                                <span class="badge bg-success"><?= $player['win_rate'] ?>%</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Games Per Day Chart
        const gamesPerDayCtx = document.getElementById('gamesPerDayChart').getContext('2d');
        new Chart(gamesPerDayCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(function($d) { return date('M d', strtotime($d['date'])); }, $gamesPerDay)) ?>,
                datasets: [{
                    label: 'Games',
                    data: <?= json_encode(array_column($gamesPerDay, 'count')) ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Games by Mode Chart
        const gamesModeCtx = document.getElementById('gamesModeChart').getContext('2d');
        new Chart(gamesModeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($gamesByMode, 'game_mode')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($gamesByMode, 'count')) ?>,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#43e97b'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
