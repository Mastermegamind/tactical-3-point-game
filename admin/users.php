<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/RedisManager.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$redisManager = RedisManager::getInstance();

// Handle user actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && hasPermission('admin')) {
        $userId = (int)$_POST['user_id'];
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $message = 'User deleted successfully';
            $messageType = 'success';
            logAdminActivity('delete_user', "Deleted user ID: $userId", 'user', $userId);
            if ($redisManager->isEnabled()) {
                $redisManager->invalidateUserStats($userId);
                $redisManager->delete("leaderboard:top_players");
                $redisManager->deletePattern("leaderboard:online_users:*");
            }
        } catch (Exception $e) {
            $message = 'Failed to delete user: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'update' && hasPermission('admin')) {
        $userId = (int)$_POST['user_id'];
        $rating = (int)$_POST['rating'];
        $wins = (int)$_POST['wins'];
        $losses = (int)$_POST['losses'];
        $draws = (int)$_POST['draws'];

        try {
            $stmt = $conn->prepare("
                UPDATE users
                SET rating = ?, wins = ?, losses = ?, draws = ?
                WHERE id = ?
            ");
            $stmt->execute([$rating, $wins, $losses, $draws, $userId]);
            $message = 'User updated successfully';
            $messageType = 'success';
            logAdminActivity('update_user', "Updated user ID: $userId stats", 'user', $userId);
            if ($redisManager->isEnabled()) {
                $redisManager->invalidateUserStats($userId);
                $redisManager->delete("leaderboard:top_players");
            }
        } catch (Exception $e) {
            $message = 'Failed to update user: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'reset_password' && hasPermission('admin')) {
        $userId = (int)$_POST['user_id'];
        $newPassword = 'password123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            $message = "Password reset to: $newPassword";
            $messageType = 'success';
            logAdminActivity('reset_password', "Reset password for user ID: $userId", 'user', $userId);
        } catch (Exception $e) {
            $message = 'Failed to reset password: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'ban_user' && hasPermission('admin')) {
        $userId = (int)$_POST['user_id'];
        $banReason = trim($_POST['ban_reason'] ?? 'No reason provided');
        $banDuration = $_POST['ban_duration'] ?? 'permanent';

        $banExpiresAt = null;
        if ($banDuration !== 'permanent') {
            $banExpiresAt = date('Y-m-d H:i:s', strtotime($banDuration));
        }

        try {
            $stmt = $conn->prepare("
                UPDATE users
                SET is_banned = 1,
                    ban_reason = ?,
                    banned_at = NOW(),
                    banned_by = ?,
                    ban_expires_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$banReason, $_SESSION['admin_id'], $banExpiresAt, $userId]);

            // Force logout the banned user
            $stmt = $conn->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
            $stmt->execute([$userId]);

            $message = 'User banned successfully';
            $messageType = 'success';
            logAdminActivity('ban_user', "Banned user ID: $userId - Reason: $banReason", 'user', $userId);
            if ($redisManager->isEnabled()) {
                $redisManager->deletePattern("leaderboard:online_users:*");
            }
        } catch (Exception $e) {
            $message = 'Failed to ban user: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'unban_user' && hasPermission('admin')) {
        $userId = (int)$_POST['user_id'];

        try {
            $stmt = $conn->prepare("
                UPDATE users
                SET is_banned = 0,
                    ban_reason = NULL,
                    banned_at = NULL,
                    banned_by = NULL,
                    ban_expires_at = NULL
                WHERE id = ?
            ");
            $stmt->execute([$userId]);

            $message = 'User unbanned successfully';
            $messageType = 'success';
            logAdminActivity('unban_user', "Unbanned user ID: $userId", 'user', $userId);
        } catch (Exception $e) {
            $message = 'Failed to unban user: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'reset_stats' && hasPermission('admin')) {
        $userId = (int)$_POST['user_id'];

        try {
            $stmt = $conn->prepare("
                UPDATE users
                SET rating = 1000, wins = 0, losses = 0, draws = 0
                WHERE id = ?
            ");
            $stmt->execute([$userId]);

            $message = 'User statistics reset successfully';
            $messageType = 'success';
            logAdminActivity('reset_stats', "Reset stats for user ID: $userId", 'user', $userId);
            if ($redisManager->isEnabled()) {
                $redisManager->invalidateUserStats($userId);
                $redisManager->delete("leaderboard:top_players");
            }
        } catch (Exception $e) {
            $message = 'Failed to reset stats: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'force_logout' && hasPermission('admin')) {
        $userId = (int)$_POST['user_id'];

        try {
            $stmt = $conn->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
            $stmt->execute([$userId]);

            $message = 'User logged out successfully';
            $messageType = 'success';
            logAdminActivity('force_logout', "Forced logout for user ID: $userId", 'user', $userId);
            if ($redisManager->isEnabled()) {
                $redisManager->deletePattern("leaderboard:online_users:*");
            }
        } catch (Exception $e) {
            $message = 'Failed to logout user: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'delete_games' && hasPermission('admin')) {
        $userId = (int)$_POST['user_id'];

        try {
            // Delete game sessions where user was player
            $stmt = $conn->prepare("
                DELETE FROM game_sessions
                WHERE player1_id = ? OR player2_id = ?
            ");
            $stmt->execute([$userId, $userId]);

            $deletedCount = $stmt->rowCount();

            $message = "Deleted $deletedCount game(s) for user";
            $messageType = 'success';
            logAdminActivity('delete_user_games', "Deleted $deletedCount games for user ID: $userId", 'user', $userId);
        } catch (Exception $e) {
            $message = 'Failed to delete games: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'adjust_rating' && hasPermission('admin')) {
        $userId = (int)$_POST['user_id'];
        $ratingChange = (int)$_POST['rating_change'];

        try {
            $stmt = $conn->prepare("
                UPDATE users
                SET rating = GREATEST(0, rating + ?)
                WHERE id = ?
            ");
            $stmt->execute([$ratingChange, $userId]);

            $direction = $ratingChange > 0 ? 'increased' : 'decreased';
            $message = "Rating $direction by " . abs($ratingChange) . " points";
            $messageType = 'success';
            logAdminActivity('adjust_rating', "Adjusted rating by $ratingChange for user ID: $userId", 'user', $userId);
            if ($redisManager->isEnabled()) {
                $redisManager->invalidateUserStats($userId);
                $redisManager->delete("leaderboard:top_players");
            }
        } catch (Exception $e) {
            $message = 'Failed to adjust rating: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search
$search = $_GET['search'] ?? '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = "WHERE username LIKE ? OR email LIKE ?";
    $searchParams = ["%$search%", "%$search%"];
}

// Get total count
$stmt = $conn->prepare("SELECT COUNT(*) FROM users $searchCondition");
$stmt->execute($searchParams);
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Get users
$stmt = $conn->prepare("
    SELECT *
    FROM users
    $searchCondition
    ORDER BY created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($searchParams);
$users = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Management - Admin Panel</title>

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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .table-responsive {
            border-radius: 8px;
        }

        .btn-action {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        .nav-pills .nav-link {
            border-radius: 8px;
            font-weight: 500;
            color: #495057;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .admin-nav {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1>👥 User Management</h1>
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
                    <a class="nav-link active" href="users.php">Users</a>
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
            </ul>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">All Users (<?= number_format($totalUsers) ?>)</h5>
                <form method="GET" class="d-flex" style="max-width: 400px;">
                    <input
                        type="text"
                        name="search"
                        class="form-control me-2"
                        placeholder="Search users..."
                        value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Rating</th>
                            <th>W/L/D</th>
                            <th>Status</th>
                            <th>Last Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr class="<?= $user['is_banned'] ? 'table-danger' : '' ?>">
                            <td><?= $user['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                                <?php if ($user['is_online']): ?>
                                    <span class="badge bg-success ms-1">Online</span>
                                <?php endif; ?>
                                <?php if ($user['is_banned']): ?>
                                    <span class="badge bg-danger ms-1">BANNED</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><span class="badge bg-primary"><?= $user['rating'] ?></span></td>
                            <td><?= $user['wins'] ?> / <?= $user['losses'] ?> / <?= $user['draws'] ?></td>
                            <td>
                                <?php if ($user['is_banned']): ?>
                                    <small class="text-danger">
                                        <strong>Banned</strong><br>
                                        <?= htmlspecialchars($user['ban_reason'] ?? 'No reason') ?><br>
                                        <?php if ($user['ban_expires_at']): ?>
                                            Until: <?= date('M d, Y H:i', strtotime($user['ban_expires_at'])) ?>
                                        <?php else: ?>
                                            Permanent
                                        <?php endif; ?>
                                    </small>
                                <?php else: ?>
                                    <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $user['last_activity'] ? date('M d, H:i', strtotime($user['last_activity'])) : '-' ?></td>
                            <td>
                                <div class="btn-group-vertical btn-group-sm">
                                    <button
                                        onclick="showUserActions(<?= htmlspecialchars(json_encode($user)) ?>)"
                                        class="btn btn-sm btn-primary">
                                        ⚙️ Manage
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="User pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Previous</a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="edit_user_id">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <input type="number" class="form-control" name="rating" id="edit_rating" required>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Wins</label>
                                <input type="number" class="form-control" name="wins" id="edit_wins" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Losses</label>
                                <input type="number" class="form-control" name="losses" id="edit_losses" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Draws</label>
                                <input type="number" class="form-control" name="draws" id="edit_draws" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function showUserActions(user) {
            const isBanned = user.is_banned == 1;

            Swal.fire({
                title: `Manage ${user.username}`,
                html: `
                    <div class="text-start">
                        <p><strong>User ID:</strong> ${user.id}</p>
                        <p><strong>Email:</strong> ${user.email}</p>
                        <p><strong>Rating:</strong> ${user.rating}</p>
                        <p><strong>W/L/D:</strong> ${user.wins}/${user.losses}/${user.draws}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${isBanned ? 'danger' : 'success'}">${isBanned ? 'BANNED' : 'Active'}</span></p>
                    </div>
                    <hr>
                    <div class="d-grid gap-2">
                        ${!isBanned ? `
                        <button class="btn btn-danger" onclick="banUser(${user.id}, '${user.username}')">
                            🚫 Ban User
                        </button>
                        ` : `
                        <button class="btn btn-success" onclick="unbanUser(${user.id}, '${user.username}')">
                            ✅ Unban User
                        </button>
                        `}
                        <button class="btn btn-primary" onclick="editUser(${JSON.stringify(user).replace(/"/g, '&quot;')})">
                            ✏️ Edit Stats
                        </button>
                        <button class="btn btn-warning" onclick="resetPassword(${user.id}, '${user.username}')">
                            🔑 Reset Password
                        </button>
                        <button class="btn btn-info" onclick="adjustRating(${user.id}, '${user.username}')">
                            ⭐ Adjust Rating
                        </button>
                        <button class="btn btn-secondary" onclick="resetStats(${user.id}, '${user.username}')">
                            🔄 Reset All Stats
                        </button>
                        ${user.is_online == 1 ? `
                        <button class="btn btn-warning" onclick="forceLogout(${user.id}, '${user.username}')">
                            🚪 Force Logout
                        </button>
                        ` : ''}
                        <button class="btn btn-danger" onclick="deleteGames(${user.id}, '${user.username}')">
                            🗑️ Delete All Games
                        </button>
                        <button class="btn btn-danger" onclick="deleteUser(${user.id}, '${user.username}')">
                            ❌ Delete Account
                        </button>
                    </div>
                `,
                width: 600,
                showConfirmButton: false,
                showCloseButton: true
            });
        }

        function banUser(userId, username) {
            Swal.fire({
                title: 'Ban User',
                html: `
                    <p>Ban <strong>${username}</strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Ban Reason:</label>
                        <textarea id="ban_reason" class="form-control" rows="3" placeholder="Enter reason for ban..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ban Duration:</label>
                        <select id="ban_duration" class="form-select">
                            <option value="permanent">Permanent</option>
                            <option value="+1 hour">1 Hour</option>
                            <option value="+6 hours">6 Hours</option>
                            <option value="+1 day">1 Day</option>
                            <option value="+3 days">3 Days</option>
                            <option value="+1 week">1 Week</option>
                            <option value="+1 month">1 Month</option>
                        </select>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Ban User',
                confirmButtonColor: '#dc3545',
                preConfirm: () => {
                    const reason = document.getElementById('ban_reason').value;
                    const duration = document.getElementById('ban_duration').value;
                    if (!reason.trim()) {
                        Swal.showValidationMessage('Please enter a ban reason');
                        return false;
                    }
                    return { reason, duration };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('ban_user', {
                        user_id: userId,
                        ban_reason: result.value.reason,
                        ban_duration: result.value.duration
                    });
                }
            });
        }

        function unbanUser(userId, username) {
            Swal.fire({
                title: 'Unban User?',
                text: `Remove ban from ${username}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, unban',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('unban_user', { user_id: userId });
                }
            });
        }

        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_rating').value = user.rating;
            document.getElementById('edit_wins').value = user.wins;
            document.getElementById('edit_losses').value = user.losses;
            document.getElementById('edit_draws').value = user.draws;

            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }

        function resetPassword(userId, username) {
            Swal.fire({
                title: 'Reset Password?',
                text: `Reset password for ${username} to "password123"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, reset',
                confirmButtonColor: '#ffc107'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('reset_password', { user_id: userId });
                }
            });
        }

        function adjustRating(userId, username) {
            Swal.fire({
                title: 'Adjust Rating',
                html: `
                    <p>Adjust rating for <strong>${username}</strong></p>
                    <div class="mb-3">
                        <label class="form-label">Rating Change:</label>
                        <input type="number" id="rating_change" class="form-control" placeholder="e.g., +50 or -25" value="0">
                        <small class="text-muted">Positive to increase, negative to decrease</small>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Apply Change',
                preConfirm: () => {
                    const change = parseInt(document.getElementById('rating_change').value);
                    if (isNaN(change)) {
                        Swal.showValidationMessage('Please enter a valid number');
                        return false;
                    }
                    return change;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('adjust_rating', {
                        user_id: userId,
                        rating_change: result.value
                    });
                }
            });
        }

        function resetStats(userId, username) {
            Swal.fire({
                title: 'Reset All Stats?',
                text: `Reset ${username}'s stats to default (Rating: 1000, W/L/D: 0/0/0)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, reset',
                confirmButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('reset_stats', { user_id: userId });
                }
            });
        }

        function forceLogout(userId, username) {
            Swal.fire({
                title: 'Force Logout?',
                text: `Force logout ${username}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, logout',
                confirmButtonColor: '#ffc107'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('force_logout', { user_id: userId });
                }
            });
        }

        function deleteGames(userId, username) {
            Swal.fire({
                title: 'Delete All Games?',
                text: `Delete ALL games for ${username}? This cannot be undone!`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete games',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('delete_games', { user_id: userId });
                }
            });
        }

        function deleteUser(userId, username) {
            Swal.fire({
                title: 'Delete User?',
                text: `Are you sure you want to delete ${username}? This cannot be undone.`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('delete', { user_id: userId });
                }
            });
        }

        function submitForm(action, data) {
            const form = document.createElement('form');
            form.method = 'POST';

            let inputs = `<input type="hidden" name="action" value="${action}">`;
            for (const [key, value] of Object.entries(data)) {
                inputs += `<input type="hidden" name="${key}" value="${value}">`;
            }

            form.innerHTML = inputs;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
