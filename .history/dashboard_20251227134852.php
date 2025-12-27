<?php
require_once __DIR__ . '/config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/RedisManager.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$redisManager = RedisManager::getInstance();

// Get user stats
$user = null;
if ($redisManager->isEnabled()) {
    $user = $redisManager->getUserStats($_SESSION['user_id']);
}

if (!$user) {
    $stmt = $conn->prepare("
        SELECT id, username, email, avatar, wins, losses, draws, rating, created_at, last_login
        FROM users WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($redisManager->isEnabled()) {
        $redisManager->cacheUserStats($_SESSION['user_id'], $user);
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total completed game count for pagination
$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM game_sessions
    WHERE (player1_id = ? OR player2_id = ?)
    AND status = 'completed'
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$totalCompletedGames = $stmt->fetch()['total'];
$totalPages = ceil($totalCompletedGames / $perPage);

// Get challenges - incoming (received)
$stmt = $conn->prepare("
    SELECT gc.*, u.username as challenger_name, u.avatar as challenger_avatar, u.is_online as challenger_online
    FROM game_challenges gc
    JOIN users u ON gc.challenger_id = u.id
    WHERE gc.challenged_id = ? AND gc.status = 'pending' AND gc.expires_at > NOW()
    ORDER BY gc.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$incomingChallenges = $stmt->fetchAll();

// Get challenges - outgoing (sent)
$stmt = $conn->prepare("
    SELECT gc.*, u.username as challenged_name, u.avatar as challenged_avatar, u.is_online as challenged_online
    FROM game_challenges gc
    JOIN users u ON gc.challenged_id = u.id
    WHERE gc.challenger_id = ? AND gc.status = 'pending' AND gc.expires_at > NOW()
    ORDER BY gc.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$outgoingChallenges = $stmt->fetchAll();

// Get accepted challenges
$stmt = $conn->prepare("
    SELECT gc.*,
           u1.username as challenger_name, u1.avatar as challenger_avatar,
           u2.username as challenged_name, u2.avatar as challenged_avatar,
           gs.status as game_status
    FROM game_challenges gc
    JOIN users u1 ON gc.challenger_id = u1.id
    JOIN users u2 ON gc.challenged_id = u2.id
    LEFT JOIN game_sessions gs ON gc.session_id = gs.id
    WHERE (gc.challenger_id = ? OR gc.challenged_id = ?)
    AND gc.status = 'accepted'
    ORDER BY gc.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$acceptedChallenges = $stmt->fetchAll();

// Get rejected challenges
$stmt = $conn->prepare("
    SELECT gc.*,
           u1.username as challenger_name, u1.avatar as challenger_avatar,
           u2.username as challenged_name, u2.avatar as challenged_avatar
    FROM game_challenges gc
    JOIN users u1 ON gc.challenger_id = u1.id
    JOIN users u2 ON gc.challenged_id = u2.id
    WHERE (gc.challenger_id = ? OR gc.challenged_id = ?)
    AND gc.status = 'rejected'
    ORDER BY gc.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$rejectedChallenges = $stmt->fetchAll();

// Get cancelled challenges
$stmt = $conn->prepare("
    SELECT gc.*,
           u1.username as challenger_name, u1.avatar as challenger_avatar,
           u2.username as challenged_name, u2.avatar as challenged_avatar
    FROM game_challenges gc
    JOIN users u1 ON gc.challenger_id = u1.id
    JOIN users u2 ON gc.challenged_id = u2.id
    WHERE (gc.challenger_id = ? OR gc.challenged_id = ?)
    AND gc.status = 'cancelled'
    ORDER BY gc.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$cancelledChallenges = $stmt->fetchAll();

// Get expired challenges
$stmt = $conn->prepare("
    SELECT gc.*,
           u1.username as challenger_name, u1.avatar as challenger_avatar,
           u2.username as challenged_name, u2.avatar as challenged_avatar
    FROM game_challenges gc
    JOIN users u1 ON gc.challenger_id = u1.id
    JOIN users u2 ON gc.challenged_id = u2.id
    WHERE (gc.challenger_id = ? OR gc.challenged_id = ?)
    AND (gc.status = 'pending' AND gc.expires_at <= NOW())
    ORDER BY gc.created_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$expiredChallenges = $stmt->fetchAll();

// Get active/paused games
$stmt = $conn->prepare("
    SELECT gs.*,
           u1.username as player1_name, u1.is_online as player1_online, u1.avatar as player1_avatar,
           u2.username as player2_name, u2.is_online as player2_online, u2.avatar as player2_avatar
    FROM game_sessions gs
    LEFT JOIN users u1 ON gs.player1_id = u1.id
    LEFT JOIN users u2 ON gs.player2_id = u2.id
    WHERE (gs.player1_id = ? OR gs.player2_id = ?)
    AND gs.status = 'active'
    ORDER BY gs.last_move_at DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$activeGames = $stmt->fetchAll();

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
    LIMIT ? OFFSET ?
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $perPage, $offset]);
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

        .active-game-item {
            padding: 1.25rem;
            border: 2px solid #e9ecef;
            border-radius: 16px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
            background: white;
        }

        .active-game-item:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .active-game-item:last-child {
            margin-bottom: 0;
        }

        .opponent-avatar-container {
            position: relative;
        }

        .opponent-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid #e9ecef;
            padding: 0.5rem;
            background: white;
        }

        .opponent-avatar svg, .opponent-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
        }

        .online-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            background: #10b981;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .pulse-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: currentColor;
            border-radius: 50%;
            animation: pulse-animation 2s infinite;
        }

        @keyframes pulse-animation {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.5;
                transform: scale(1.2);
            }
        }

        @media (max-width: 768px) {
            .active-game-item {
                flex-direction: column;
                gap: 1rem;
            }

            .active-game-item .d-flex.gap-2 {
                width: 100%;
            }

            .active-game-item .d-flex.gap-2 a,
            .active-game-item .d-flex.gap-2 button {
                flex: 1;
            }

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
                    <?php if ($user['id'] == 1): ?>
                        <a href="admin/ai-training.php" class="btn btn-secondary-custom mb-2">🤖 AI Training</a>
                    <?php endif; ?>
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

        <?php if (!empty($activeGames)): ?>
        <div class="card-custom">
            <h4 class="mb-4">
                <span class="badge bg-success me-2">Active</span>
                Continue Your Games
            </h4>
            <?php foreach ($activeGames as $game):
                $isPlayer1 = ($game['player1_id'] == $_SESSION['user_id']);
                $opponentName = $isPlayer1 ? ($game['player2_name'] ?? 'AI') : $game['player1_name'];
                $opponentOnline = $isPlayer1 ? ($game['player2_online'] ?? false) : ($game['player1_online'] ?? false);
                $opponentAvatar = $isPlayer1 ? ($game['player2_avatar'] ?? null) : ($game['player1_avatar'] ?? null);
                $isVsAI = strpos($game['game_mode'], 'pvc') !== false;

                $boardState = json_decode($game['board_state'], true);
                $piecesPlaced = count(array_filter($boardState['board'] ?? [], fn($p) => $p !== null));
                $progress = round(($piecesPlaced / 6) * 100);

                // Time since last move
                $lastMove = strtotime($game['last_move_at']);
                $timeSince = time() - $lastMove;
                if ($timeSince < 60) {
                    $timeAgo = 'Just now';
                } elseif ($timeSince < 3600) {
                    $timeAgo = floor($timeSince / 60) . ' min ago';
                } elseif ($timeSince < 86400) {
                    $timeAgo = floor($timeSince / 3600) . ' hours ago';
                } else {
                    $timeAgo = floor($timeSince / 86400) . ' days ago';
                }
            ?>
            <div class="active-game-item">
                <div class="d-flex align-items-center gap-3 flex-grow-1">
                    <div class="opponent-avatar-container">
                        <?php if ($opponentAvatar && isset($presetAvatars[$opponentAvatar])): ?>
                            <div class="opponent-avatar">
                                <?= $presetAvatars[$opponentAvatar] ?>
                            </div>
                        <?php else: ?>
                            <div class="opponent-avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="45" fill="#6c757d"/>
                                    <circle cx="35" cy="40" r="5" fill="#fff"/>
                                    <circle cx="65" cy="40" r="5" fill="#fff"/>
                                    <circle cx="50" cy="65" r="8" fill="#fff"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                        <?php if (!$isVsAI && $opponentOnline): ?>
                            <span class="online-indicator" title="Online"></span>
                        <?php endif; ?>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <strong>vs <?= htmlspecialchars($opponentName) ?></strong>
                            <?php if (!$isVsAI): ?>
                                <?php if ($opponentOnline): ?>
                                    <span class="badge bg-success-subtle text-success-emphasis" style="font-size: 0.75rem;">
                                        <span class="pulse-dot"></span> Online
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size: 0.75rem;">Offline</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-info-subtle text-info-emphasis" style="font-size: 0.75rem;">
                                    <?= ucfirst(str_replace('pvc-', '', $game['game_mode'])) ?> AI
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted">
                            <?= ucfirst($boardState['phase'] ?? 'placement') ?> Phase • Last move <?= $timeAgo ?>
                        </div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="play.php?session=<?= $game['id'] ?>" class="btn btn-primary btn-sm">
                        <svg width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M6.271 5.055a.5.5 0 0 1 .52.038l3.5 2.5a.5.5 0 0 1 0 .814l-3.5 2.5A.5.5 0 0 1 6 10.5v-5a.5.5 0 0 1 .271-.445z"/>
                        </svg>
                        Resume
                    </a>
                    <button class="btn btn-outline-danger btn-sm" onclick="abandonGame(<?= $game['id'] ?>)">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1H2.5zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5zM8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5zm3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0z"/>
                        </svg>
                        Abandon
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Challenge Management Box -->
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">🎯 Challenges</h4>
                <span class="badge bg-primary"><?= count($incomingChallenges) ?> Incoming</span>
            </div>

            <!-- Challenge Tabs -->
            <ul class="nav nav-tabs mb-3" id="challengeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="incoming-tab" data-bs-toggle="tab" data-bs-target="#incoming" type="button" role="tab">
                        Incoming <?php if(count($incomingChallenges) > 0): ?><span class="badge bg-danger ms-1"><?= count($incomingChallenges) ?></span><?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab">
                        Sent <?php if(count($outgoingChallenges) > 0): ?><span class="badge bg-warning ms-1"><?= count($outgoingChallenges) ?></span><?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="accepted-tab" data-bs-toggle="tab" data-bs-target="#accepted" type="button" role="tab">
                        Accepted <?php if(count($acceptedChallenges) > 0): ?><span class="badge bg-success ms-1"><?= count($acceptedChallenges) ?></span><?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab">
                        Rejected
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled" type="button" role="tab">
                        Cancelled
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="expired-tab" data-bs-toggle="tab" data-bs-target="#expired" type="button" role="tab">
                        Expired
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="challengeTabContent">
                <!-- Incoming Challenges -->
                <div class="tab-pane fade show active" id="incoming" role="tabpanel">
                    <?php if (empty($incomingChallenges)): ?>
                        <p class="text-muted text-center py-4">No pending challenges</p>
                    <?php else: ?>
                        <?php foreach ($incomingChallenges as $challenge): ?>
                            <?php
                                $timeRemaining = strtotime($challenge['expires_at']) - time();
                                $minutesRemaining = floor($timeRemaining / 60);
                            ?>
                            <div class="challenge-item mb-3 p-3 border rounded">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="position-relative">
                                                <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; border: 2px solid #667eea;">
                                                    <?php
                                                    if (isset($presetAvatars[$challenge['challenger_avatar']])) {
                                                        echo $presetAvatars[$challenge['challenger_avatar']];
                                                    } else {
                                                        echo '<div style="width:100%;height:100%;background:#ccc;"></div>';
                                                    }
                                                    ?>
                                                </div>
                                                <?php if ($challenge['challenger_online']): ?>
                                                    <span style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #28a745; border: 2px solid white; border-radius: 50%;"></span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($challenge['challenger_name']) ?></strong>
                                                <div class="text-muted small">
                                                    Mode: <?= htmlspecialchars($challenge['game_mode']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-<?= $minutesRemaining < 2 ? 'danger' : 'muted' ?>">
                                            ⏱️ <?= $minutesRemaining ?>m remaining
                                        </small>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <button class="btn btn-sm btn-success me-1" onclick="respondToChallenge(<?= $challenge['id'] ?>, true)">
                                            Accept
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="respondToChallenge(<?= $challenge['id'] ?>, false)">
                                            Decline
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Sent Challenges -->
                <div class="tab-pane fade" id="sent" role="tabpanel">
                    <?php if (empty($outgoingChallenges)): ?>
                        <p class="text-muted text-center py-4">No pending outgoing challenges</p>
                    <?php else: ?>
                        <?php foreach ($outgoingChallenges as $challenge): ?>
                            <?php
                                $timeRemaining = strtotime($challenge['expires_at']) - time();
                                $minutesRemaining = floor($timeRemaining / 60);
                            ?>
                            <div class="challenge-item mb-3 p-3 border rounded bg-light">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="position-relative">
                                                <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; border: 2px solid #667eea;">
                                                    <?php
                                                    if (isset($presetAvatars[$challenge['challenged_avatar']])) {
                                                        echo $presetAvatars[$challenge['challenged_avatar']];
                                                    } else {
                                                        echo '<div style="width:100%;height:100%;background:#ccc;"></div>';
                                                    }
                                                    ?>
                                                </div>
                                                <?php if ($challenge['challenged_online']): ?>
                                                    <span style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #28a745; border: 2px solid white; border-radius: 50%;"></span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($challenge['challenged_name']) ?></strong>
                                                <div class="text-muted small">
                                                    Mode: <?= htmlspecialchars($challenge['game_mode']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">
                                            ⏱️ Expires in <?= $minutesRemaining ?>m
                                        </small>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="cancelChallenge(<?= $challenge['id'] ?>)">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Accepted Challenges -->
                <div class="tab-pane fade" id="accepted" role="tabpanel">
                    <?php if (empty($acceptedChallenges)): ?>
                        <p class="text-muted text-center py-4">No accepted challenges</p>
                    <?php else: ?>
                        <?php foreach ($acceptedChallenges as $challenge): ?>
                            <div class="challenge-item mb-3 p-3 border rounded bg-success bg-opacity-10">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <strong><?= htmlspecialchars($challenge['challenger_name']) ?></strong>
                                        <span class="text-muted">vs</span>
                                        <strong><?= htmlspecialchars($challenge['challenged_name']) ?></strong>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($challenge['game_mode']) ?> • Accepted <?= date('M j, g:i A', strtotime($challenge['responded_at'])) ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-info text-dark">Session ID: <?= $challenge['session_id'] ?? 'N/A' ?></span>
                                            <?php if (isset($challenge['game_status'])): ?>
                                                <span class="badge bg-secondary"><?= ucfirst($challenge['game_status']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <?php if ($challenge['session_id'] && isset($challenge['game_status']) && $challenge['game_status'] === 'active'): ?>
                                            <a href="play.php?session=<?= $challenge['session_id'] ?>" class="btn btn-sm btn-primary">
                                                Continue Game
                                            </a>
                                        <?php elseif ($challenge['session_id']): ?>
                                            <span class="text-muted small">Game <?= $challenge['game_status'] ?? 'ended' ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Rejected Challenges -->
                <div class="tab-pane fade" id="rejected" role="tabpanel">
                    <?php if (empty($rejectedChallenges)): ?>
                        <p class="text-muted text-center py-4">No rejected challenges</p>
                    <?php else: ?>
                        <?php foreach ($rejectedChallenges as $challenge): ?>
                            <div class="challenge-item mb-3 p-3 border rounded bg-danger bg-opacity-10">
                                <div class="row align-items-center">
                                    <div class="col-md-9">
                                        <strong><?= htmlspecialchars($challenge['challenger_name']) ?></strong>
                                        <span class="text-muted">vs</span>
                                        <strong><?= htmlspecialchars($challenge['challenged_name']) ?></strong>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($challenge['game_mode']) ?> • Rejected <?= date('M j, g:i A', strtotime($challenge['responded_at'])) ?>
                                        </div>
                                        <?php if ($challenge['session_id']): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-info text-dark">Session ID: <?= $challenge['session_id'] ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <span class="badge bg-danger">Rejected</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Cancelled Challenges -->
                <div class="tab-pane fade" id="cancelled" role="tabpanel">
                    <?php if (empty($cancelledChallenges)): ?>
                        <p class="text-muted text-center py-4">No cancelled challenges</p>
                    <?php else: ?>
                        <?php foreach ($cancelledChallenges as $challenge): ?>
                            <div class="challenge-item mb-3 p-3 border rounded bg-warning bg-opacity-10">
                                <div class="row align-items-center">
                                    <div class="col-md-9">
                                        <strong><?= htmlspecialchars($challenge['challenger_name']) ?></strong>
                                        <span class="text-muted">vs</span>
                                        <strong><?= htmlspecialchars($challenge['challenged_name']) ?></strong>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($challenge['game_mode']) ?> • Cancelled <?= date('M j, g:i A', strtotime($challenge['responded_at'])) ?>
                                        </div>
                                        <?php if ($challenge['session_id']): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-info text-dark">Session ID: <?= $challenge['session_id'] ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <span class="badge bg-warning text-dark">Cancelled</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Expired Challenges -->
                <div class="tab-pane fade" id="expired" role="tabpanel">
                    <?php if (empty($expiredChallenges)): ?>
                        <p class="text-muted text-center py-4">No expired challenges</p>
                    <?php else: ?>
                        <?php foreach ($expiredChallenges as $challenge): ?>
                            <div class="challenge-item mb-3 p-3 border rounded opacity-50">
                                <div class="row align-items-center">
                                    <div class="col-md-9">
                                        <strong><?= htmlspecialchars($challenge['challenger_name']) ?></strong>
                                        <span class="text-muted">vs</span>
                                        <strong><?= htmlspecialchars($challenge['challenged_name']) ?></strong>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($challenge['game_mode']) ?> • Expired <?= date('M j, g:i A', strtotime($challenge['expires_at'])) ?>
                                        </div>
                                        <?php if ($challenge['session_id']): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-info text-dark">Session ID: <?= $challenge['session_id'] ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <span class="badge bg-secondary">Expired</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
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
                        // Determine game outcome for current user
                        $isWin = $game['winner_id'] !== null && $game['winner_id'] == $_SESSION['user_id'];
                        $isDraw = $game['winner_id'] === null;
                        $isLoss = $game['winner_id'] !== null && $game['winner_id'] != $_SESSION['user_id'];

                        if ($isDraw) {
                            $badgeClass = 'badge-draw';
                            $badgeText = 'Draw';
                        } elseif ($isWin) {
                            $badgeClass = 'badge-win';
                            $badgeText = 'Win';
                        } else {
                            $badgeClass = 'badge-loss';
                            $badgeText = 'Loss';
                        }
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                        <a href="game-history.php?id=<?= $game['id'] ?>" class="btn btn-sm btn-outline-primary">Replay</a>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($totalPages > 1): ?>
                <nav aria-label="Game history pagination" class="mt-4">
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
    <script>
        const autoStartNotifications = true;

        async function abandonGame(sessionId) {
            const result = await Swal.fire({
                title: 'Abandon Game?',
                text: 'Are you sure you want to abandon this game? This action cannot be undone and will count as a loss.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, abandon',
                cancelButtonText: 'No, keep it',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch('api/abandon-game.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ session_id: sessionId })
                    });

                    const data = await response.json();

                    if (data.success) {
                        await Swal.fire({
                            title: 'Game Abandoned',
                            text: 'The game has been removed from your active games.',
                            icon: 'success',
                            confirmButtonColor: '#667eea'
                        });
                        location.reload();
                    } else {
                        await Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to abandon game',
                            icon: 'error',
                            confirmButtonColor: '#667eea'
                        });
                    }
                } catch (error) {
                    console.error('Error abandoning game:', error);
                    await Swal.fire({
                        title: 'Error',
                        text: 'Failed to abandon game. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#667eea'
                    });
                }
            }
        }

        // Challenge Management Functions
        async function respondToChallenge(challengeId, accept) {
            try {
                const response = await fetch('api/respond-challenge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        challenge_id: challengeId,
                        accept: accept
                    })
                });

                const data = await response.json();

                if (data.success) {
                    if (accept && data.session_id) {
                        await Swal.fire({
                            title: 'Challenge Accepted!',
                            text: 'Starting game...',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.href = `play.php?session=${data.session_id}`;
                    } else {
                        await Swal.fire({
                            title: accept ? 'Challenge Accepted!' : 'Challenge Declined',
                            icon: 'success',
                            confirmButtonColor: '#667eea'
                        });
                        location.reload();
                    }
                } else {
                    await Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to respond to challenge',
                        icon: 'error',
                        confirmButtonColor: '#667eea'
                    });
                }
            } catch (error) {
                console.error('Error responding to challenge:', error);
                await Swal.fire({
                    title: 'Error',
                    text: 'Failed to respond to challenge. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#667eea'
                });
            }
        }

        async function cancelChallenge(challengeId) {
            const result = await Swal.fire({
                title: 'Cancel Challenge?',
                text: 'Are you sure you want to cancel this challenge?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, cancel it',
                cancelButtonText: 'No, keep it',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch('api/cancel-challenge.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ challenge_id: challengeId })
                    });

                    const data = await response.json();

                    if (data.success) {
                        await Swal.fire({
                            title: 'Challenge Cancelled',
                            icon: 'success',
                            confirmButtonColor: '#667eea'
                        });
                        location.reload();
                    } else {
                        await Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to cancel challenge',
                            icon: 'error',
                            confirmButtonColor: '#667eea'
                        });
                    }
                } catch (error) {
                    console.error('Error cancelling challenge:', error);
                    await Swal.fire({
                        title: 'Error',
                        text: 'Failed to cancel challenge. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#667eea'
                    });
                }
            }
        }

        // Auto-refresh challenges every 10 seconds
        setInterval(() => {
            // Silently refresh challenge counts
            fetch('api/get-challenge-counts.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.incoming_count > 0) {
                        const badge = document.querySelector('.badge.bg-primary');
                        if (badge) {
                            badge.textContent = `${data.incoming_count} Incoming`;
                        }
                    }
                })
                .catch(err => console.error('Failed to update challenge counts:', err));
        }, 10000);
    </script>
    <script src="js/notification-handler.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
