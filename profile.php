<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();

$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $err = 'New passwords do not match.';
    } elseif (strlen($new) < 8) {
        $err = 'New password must be at least 8 characters.';
    } else {
        $stmt = $conn->prepare('SELECT password FROM user WHERE user_id = :uid');
        $stmt->execute([':uid' => (int)$_SESSION['user_id']]);
        $hash = $stmt->fetchColumn();
        if ($hash && password_verify($current, $hash)) {
            $newHash = password_hash($new, PASSWORD_BCRYPT);
            $upd = $conn->prepare('UPDATE user SET password = :p WHERE user_id = :uid');
            $upd->execute([':p' => $newHash, ':uid' => (int)$_SESSION['user_id']]);
            $msg = 'Password updated successfully.';
        } else {
            $err = 'Current password is incorrect.';
        }
    }
}

$page_title = 'My Profile';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-user-cog me-2"></i>Change Password</div>
            <div class="card-body">
                <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
                <?php if ($err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary" name="change_password">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


