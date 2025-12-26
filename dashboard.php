<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get user stats
$stmt = $conn->prepare("
    SELECT username, email, avatar, wins, losses, draws, rating, created_at, last_login
    FROM users WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get recent games
$stmt = $conn->prepare("
    SELECT gs.*,
           u1.username as player1_name,
           u2.username as player2_name,
           w.username as winner_name
    FROM game_sessions gs
    LEFT JOIN users u1 ON gs.player1_id = u1.id
    LEFT JOIN users u2 ON gs.player2_id = u2.id
    LEFT JOIN users w ON gs.winner_id = w.id
    WHERE (gs.player1_id = ? OR gs.player2_id = ?)
    AND gs.status = 'completed'
    ORDER BY gs.completed_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$recentGames = $stmt->fetchAll();

$totalGames = $user['wins'] + $user['losses'] + $user['draws'];
$winRate = $totalGames > 0 ? round(($user['wins'] / $totalGames) * 100) : 0;

// Preset avatars for display
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
    <title>Dashboard - Tactical Pebble Game</title>
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

        .dashboard-container {
            max-width: 1200px;
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

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #667eea;
            padding: 1rem;
            background: white;
            flex-shrink: 0;
        }

        .profile-avatar img, .profile-avatar svg {
            width: 100%;
            height: 100%;
            border-radius: 50%;
        }

        .profile-info h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            text-align: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn-game-start {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 600;
            color: white;
            font-size: 1.1rem;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-game-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary-custom {
            background: #e9ecef;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: #495057;
            transition: all 0.2s;
        }

        .btn-secondary-custom:hover {
            background: #dee2e6;
        }

        .game-history-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .game-history-item:last-child {
            border-bottom: none;
        }

        .badge-win {
            background: #43e97b;
            color: white;
        }

        .badge-loss {
            background: #fa709a;
            color: white;
        }

        .badge-draw {
            background: #6c757d;
            color: white;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="card-custom">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php if ($user['avatar'] && isset($presetAvatars[$user['avatar']])): ?>
                        <?= $presetAvatars[$user['avatar']] ?>
                    <?php elseif ($user['avatar']): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#ccc"/></svg>
                    <?php endif; ?>
                </div>
                <div class="profile-info flex-grow-1">
                    <h2><?= htmlspecialchars($user['username']) ?></h2>
                    <p class="text-muted mb-0"><?= htmlspecialchars($user['email']) ?></p>
                    <p class="text-muted mb-0">Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
                </div>
                <div>
                    <a href="select-avatar.php" class="btn btn-secondary-custom mb-2">Change Avatar</a>
                    <a href="api/logout.php" class="btn btn-secondary-custom">Logout</a>
                </div>
            </div>

            <div class="profile-stats">
                <div class="stat-box">
                    <div class="stat-value"><?= $user['rating'] ?></div>
                    <div class="stat-label">Rating</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $user['wins'] ?></div>
                    <div class="stat-label">Wins</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $user['losses'] ?></div>
                    <div class="stat-label">Losses</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $user['draws'] ?></div>
                    <div class="stat-label">Draws</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $winRate ?>%</div>
                    <div class="stat-label">Win Rate</div>
                </div>
            </div>

            <a href="game-settings.php" class="btn btn-game-start">Start New Game</a>
        </div>

        <div class="card-custom">
            <h4 class="mb-4">Recent Games</h4>
            <?php if (empty($recentGames)): ?>
                <p class="text-muted text-center">No games played yet. Start your first game!</p>
            <?php else: ?>
                <?php foreach ($recentGames as $game): ?>
                <div class="game-history-item">
                    <div>
                        <strong><?= htmlspecialchars($game['player1_name']) ?></strong> vs
                        <strong><?= $game['player2_name'] ? htmlspecialchars($game['player2_name']) : 'AI' ?></strong>
                        <br>
                        <small class="text-muted"><?= date('M d, Y - H:i', strtotime($game['completed_at'])) ?></small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php
                        $isWin = $game['winner_id'] == $_SESSION['user_id'];
                        $isDraw = $game['winner_id'] === null;
                        $badgeClass = $isDraw ? 'badge-draw' : ($isWin ? 'badge-win' : 'badge-loss');
                        $badgeText = $isDraw ? 'Draw' : ($isWin ? 'Win' : 'Loss');
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                        <a href="game-history.php?id=<?= $game['id'] ?>" class="btn btn-sm btn-outline-primary">Replay</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
