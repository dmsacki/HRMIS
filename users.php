<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
if (!hasRole(1)) { redirect('dashboard.php'); }

$db = new Database();
$conn = $db->getConnection();

$msg = $err = '';

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = (int)($_POST['role_id'] ?? 3);
    $dept_id = (int)($_POST['dept_id'] ?? 1);
    if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 8) {
        try {
            $stmt = $conn->prepare('INSERT INTO user (name, email, password, role_id, dept_id) VALUES (:n, :e, :p, :r, :d)');
            $stmt->execute([
                ':n' => $name,
                ':e' => $email,
                ':p' => password_hash($password, PASSWORD_BCRYPT),
                ':r' => $role_id,
                ':d' => $dept_id,
            ]);
            $msg = 'User created.';
        } catch (Throwable $e) {
            $err = 'Failed to create user (email may already exist).';
        }
    } else {
        $err = 'Provide valid name, email and password (min 8 chars).';
    }
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['user_id'];
    if ($uid === (int)$_SESSION['user_id']) {
        $err = 'You cannot delete your own account.';
    } else {
        try {
            $del = $conn->prepare('DELETE FROM user WHERE user_id = :id');
            $del->execute([':id' => $uid]);
            $msg = 'User deleted.';
        } catch (Throwable $e) {
            $err = 'Cannot delete user (in use).';
        }
    }
}

// Reset user password (admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $uid = (int)($_POST['user_id'] ?? 0);
    $newPass = $_POST['new_password'] ?? '';
    if ($uid > 0 && strlen($newPass) >= 8) {
        try {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $upd = $conn->prepare('UPDATE user SET password = :p WHERE user_id = :id');
            $upd->execute([':p' => $hash, ':id' => $uid]);
            $msg = 'Password reset successfully.';
        } catch (Throwable $e) {
            $err = 'Failed to reset password.';
        }
    } else {
        $err = 'Provide a password with at least 8 characters.';
    }
}

$page_title = 'Admin • Users';
include __DIR__ . '/includes/header.php';

$roles = $conn->query('SELECT role_id, role_name FROM role ORDER BY role_id')->fetchAll(PDO::FETCH_ASSOC);
$depts = $conn->query('SELECT dept_id, dept_name FROM department ORDER BY dept_name')->fetchAll(PDO::FETCH_ASSOC);
$users = $conn->query('SELECT u.user_id, u.name, u.email, r.role_name, d.dept_name, u.created_at FROM user u JOIN role r ON u.role_id = r.role_id JOIN department d ON u.dept_id = d.dept_id ORDER BY u.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fas fa-user-plus me-2"></i>Create User</div>
            <div class="card-body">
                <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
                <?php if ($err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role_id" class="form-select" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo (int)$r['role_id']; ?>"><?php echo htmlspecialchars($r['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="dept_id" class="form-select" required>
                            <?php foreach ($depts as $d): ?>
                                <option value="<?php echo (int)$d['dept_id']; ?>"><?php echo htmlspecialchars($d['dept_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" name="create_user">Create</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2"></i>Users</span>
                <input type="text" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-filter="#tblUsers">
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="tblUsers">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$users) { echo '<tr><td colspan="6" class="text-center text-muted">No users.</td></tr>'; }
                            foreach ($users as $u) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($u['name']) . '</td>';
                                echo '<td>' . htmlspecialchars($u['email']) . '</td>';
                                echo '<td>' . htmlspecialchars($u['role_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($u['dept_name']) . '</td>';
                                echo '<td>' . htmlspecialchars(formatDateTime($u['created_at'])) . '</td>';
                                echo '<td>';
                                echo '<form method="post" class="d-inline" onsubmit="return confirm(\'Delete user?\');">';
                                echo '<input type="hidden" name="user_id" value="' . (int)$u['user_id'] . '">';
                                echo '<button class="btn btn-sm btn-danger" name="delete_user">Delete</button>';
                                echo '</form>';
                                echo ' ';
                                echo '<form method="post" class="d-inline ms-2">';
                                echo '<input type="hidden" name="user_id" value="' . (int)$u['user_id'] . '">';
                                echo '<input type="password" name="new_password" class="form-control form-control-sm d-inline w-auto me-1" placeholder="New password">';
                                echo '<button class="btn btn-sm btn-primary" name="reset_password">Reset</button>';
                                echo '</form>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


