<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
if (!hasRole(1)) { redirect('dashboard.php'); }

$db = new Database();
$conn = $db->getConnection();

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_role'])) {
    $name = trim($_POST['role_name'] ?? '');
    if ($name !== '') {
        try {
            $stmt = $conn->prepare('INSERT INTO role (role_name) VALUES (:n)');
            $stmt->execute([':n' => $name]);
            $msg = 'Role created.';
        } catch (Throwable $e) {
            $err = 'Role may already exist.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_role'])) {
    $id = (int)$_POST['role_id'];
    if (in_array($id, [1,2,3], true)) {
        $err = 'Default roles cannot be deleted.';
    } else {
        try {
            $del = $conn->prepare('DELETE FROM role WHERE role_id = :id');
            $del->execute([':id' => $id]);
            $msg = 'Role deleted.';
        } catch (Throwable $e) {
            $err = 'Cannot delete role (in use).';
        }
    }
}

$page_title = 'Admin • Roles';
include __DIR__ . '/includes/header.php';

$roles = $conn->query('SELECT role_id, role_name FROM role ORDER BY role_id')->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fas a fa-shield-halved me-2"></i>Create Role</div>
            <div class="card-body">
                <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
                <?php if ($err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input type="text" name="role_name" class="form-control" required>
                    </div>
                    <button class="btn btn-primary" name="create_role">Create</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list me-2"></i>Roles</span>
                <input type="text" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-filter="#tblRoles">
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="tblRoles">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$roles) { echo '<tr><td colspan="3" class="text-center text-muted">No roles.</td></tr>'; }
                            foreach ($roles as $r) {
                                echo '<tr>';
                                echo '<td>' . (int)$r['role_id'] . '</td>';
                                echo '<td>' . htmlspecialchars($r['role_name']) . '</td>';
                                echo '<td>';
                                if (!in_array((int)$r['role_id'], [1,2,3], true)) {
                                    echo '<form method="post" class="d-inline" onsubmit="return confirm(\'Delete role?\');">';
                                    echo '<input type="hidden" name="role_id" value="' . (int)$r['role_id'] . '">';
                                    echo '<button class="btn btn-sm btn-danger" name="delete_role">Delete</button>';
                                    echo '</form>';
                                } else {
                                    echo '<span class="text-muted">Protected</span>';
                                }
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


