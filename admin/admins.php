<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Only super_admin can access this page
if (!hasPermission('super_admin')) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$message = '';
$messageType = '';

// Handle admin operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $fullName = trim($_POST['full_name']);
        $role = $_POST['role'];

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("
                INSERT INTO admins (username, email, password, full_name, role)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $email, $hashedPassword, $fullName, $role]);
            $message = 'Admin created successfully';
            $messageType = 'success';
            logAdminActivity('create_admin', "Created admin: $username");
        } catch (Exception $e) {
            $message = 'Failed to create admin: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'toggle_status') {
        $adminId = (int)$_POST['admin_id'];
        $isActive = (int)$_POST['is_active'];

        try {
            $stmt = $conn->prepare("UPDATE admins SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive, $adminId]);
            $message = 'Admin status updated';
            $messageType = 'success';
            logAdminActivity('toggle_admin_status', "Toggled admin ID $adminId status to $isActive");
        } catch (Exception $e) {
            $message = 'Failed to update status';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $adminId = (int)$_POST['admin_id'];

        // Don't allow deleting yourself
        if ($adminId === $_SESSION['admin_id']) {
            $message = 'Cannot delete your own account';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
                $stmt->execute([$adminId]);
                $message = 'Admin deleted successfully';
                $messageType = 'success';
                logAdminActivity('delete_admin', "Deleted admin ID: $adminId");
            } catch (Exception $e) {
                $message = 'Failed to delete admin';
                $messageType = 'danger';
            }
        }
    }
}

// Get all admins
$stmt = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Admins - Admin Panel</title>

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
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1>👨‍💼 Manage Administrators</h1>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="content-card mb-4">
            <h5 class="mb-3">Create New Admin</h5>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="moderator">Moderator</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Create Admin</button>
            </form>
        </div>

        <div class="content-card">
            <h5 class="mb-3">All Administrators</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= $admin['id'] ?></td>
                            <td><strong><?= htmlspecialchars($admin['username']) ?></strong></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td><?= htmlspecialchars($admin['full_name']) ?></td>
                            <td><span class="badge bg-primary"><?= $admin['role'] ?></span></td>
                            <td>
                                <?php if ($admin['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $admin['last_login'] ? date('M d, H:i', strtotime($admin['last_login'])) : 'Never' ?></td>
                            <td>
                                <?php if ($admin['id'] !== $_SESSION['admin_id']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                    <input type="hidden" name="is_active" value="<?= $admin['is_active'] ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-sm btn-warning">
                                        <?= $admin['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <button onclick="deleteAdmin(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['username']) ?>')" class="btn btn-sm btn-danger">
                                    Delete
                                </button>
                                <?php else: ?>
                                    <span class="badge bg-info">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function deleteAdmin(id, username) {
            Swal.fire({
                title: 'Delete Admin?',
                text: `Delete ${username}? This cannot be undone.`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="admin_id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
