<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$page_title = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="login-container">
    <div class="login-card fade-in">
        <div class="login-header">
            <h2><i class="fas fa-building me-2"></i><?php echo APP_NAME; ?></h2>
            <p>Sign in to continue</p>
        </div>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">Invalid email or password.</div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-1"></i> Login
            </button>
        </form>
    </div>
 </div>

<?php include __DIR__ . '/includes/footer.php'; ?>


