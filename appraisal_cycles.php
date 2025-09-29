<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
if (!(hasRole(1) || hasRole(2))) { redirect('dashboard.php'); }

$db = new Database();
$conn = $db->getConnection();

// Create cycle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_cycle'])) {
    $year = (int)$_POST['year'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    if ($year && $start && $end) {
        $stmt = $conn->prepare('INSERT INTO appraisal_cycle (year, start_date, end_date) VALUES (:year, :start, :end)');
        $stmt->execute([':year' => $year, ':start' => $start, ':end' => $end]);
        $msg = 'Cycle created.';
    } else {
        $err = 'Provide year, start and end dates.';
    }
}

$page_title = 'Appraisal Cycles';
include __DIR__ . '/includes/header.php';

$rows = $conn->query('SELECT cycle_id, year, start_date, end_date, created_at FROM appraisal_cycle ORDER BY year DESC')->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fas fa-calendar-plus me-2"></i>Create Appraisal Cycle</div>
            <div class="card-body">
                <?php if (!empty($msg)): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
                <?php if (!empty($err)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" min="2000" max="2100" value="<?php echo date('Y'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <button type="submit" name="create_cycle" class="btn btn-primary">Create Cycle</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fas fa-list me-2"></i>Existing Cycles</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$rows) { echo '<tr><td colspan="4" class="text-center text-muted">No cycles yet.</td></tr>'; }
                            foreach ($rows as $r) {
                                echo '<tr>';
                                echo '<td>' . (int)$r['year'] . '</td>';
                                echo '<td>' . htmlspecialchars(formatDate($r['start_date'])) . '</td>';
                                echo '<td>' . htmlspecialchars(formatDate($r['end_date'])) . '</td>';
                                echo '<td>' . htmlspecialchars(formatDateTime($r['created_at'])) . '</td>';
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


