<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
if (!hasRole(1)) { redirect('dashboard.php'); }

$db = new Database();
$conn = $db->getConnection();

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_dept'])) {
    $name = trim($_POST['dept_name'] ?? '');
    if ($name !== '') {
        try {
            $stmt = $conn->prepare('INSERT INTO department (dept_name) VALUES (:n)');
            $stmt->execute([':n' => $name]);
            $msg = 'Department created.';
        } catch (Throwable $e) {
            $err = 'Department may already exist.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dept'])) {
    $id = (int)$_POST['dept_id'];
    try {
        $del = $conn->prepare('DELETE FROM department WHERE dept_id = :id');
        $del->execute([':id' => $id]);
        $msg = 'Department deleted.';
    } catch (Throwable $e) {
        $err = 'Cannot delete department (in use).';
    }
}

$page_title = 'Admin • Departments';
include __DIR__ . '/includes/header.php';

$depts = $conn->query('SELECT dept_id, dept_name FROM department ORDER BY dept_name')->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fas fa-sitemap me-2"></i>Create Department</div>
            <div class="card-body">
                <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
                <?php if ($err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Department Name</label>
                        <input type="text" name="dept_name" class="form-control" required>
                    </div>
                    <button class="btn btn-primary" name="create_dept">Create</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list me-2"></i>Departments</span>
                <input type="text" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-filter="#tblDepts">
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="tblDepts">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$depts) { echo '<tr><td colspan="2" class="text-center text-muted">No departments.</td></tr>'; }
                            foreach ($depts as $d) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($d['dept_name']) . '</td>';
                                echo '<td>';
                                echo '<form method="post" class="d-inline" onsubmit="return confirm(\'Delete department?\');">';
                                echo '<input type="hidden" name="dept_id" value="' . (int)$d['dept_id'] . '">';
                                echo '<button class="btn btn-sm btn-danger" name="delete_dept">Delete</button>';
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


