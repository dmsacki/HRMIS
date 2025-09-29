<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
if (!(hasRole(1) || hasRole(2))) { redirect('dashboard.php'); }

$db = new Database();
$conn = $db->getConnection();

$page_title = 'Reports';
include __DIR__ . '/includes/header.php';

function fetchAllSafe(PDO $conn, string $sql): array {
    try { return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { return []; }
}

$appraisalScores = fetchAllSafe($conn, 'SELECT * FROM appraisal_scores_report ORDER BY year DESC, employee_name');
// Audit trail pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;
$totalAudit = (int)$conn->query('SELECT COUNT(*) FROM audit_trail_report')->fetchColumn();
$totalPages = max(1, (int)ceil($totalAudit / $perPage));
$auditTrail = fetchAllSafe($conn, 'SELECT * FROM audit_trail_report ORDER BY timestamp DESC LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset);
$employeeTaskSummary = fetchAllSafe($conn, 'SELECT * FROM employee_task_summary ORDER BY completion_rate DESC');
$feedbackExchange = fetchAllSafe($conn, 'SELECT * FROM feedback_exchange ORDER BY created_at DESC LIMIT 200');
$taskStatusOverview = fetchAllSafe($conn, 'SELECT * FROM task_status_overview ORDER BY due_date ASC');
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-chart-line me-2"></i>Appraisal Scores Report</span>
        <div class="d-flex align-items-center gap-2">
            <input type="text" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-filter="#tblAppraisalScores">
            <a class="btn btn-sm btn-outline-primary" href="export.php?type=appraisal_scores">Export CSV</a>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print / PDF</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm" id="tblAppraisalScores">
                <thead>
                    <tr>
                        <th>Appraisal ID</th>
                        <th>Employee</th>
                        <th>Dept</th>
                        <th>Year</th>
                        <th>Final Score</th>
                        <th>Criterion</th>
                        <th>Criterion Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$appraisalScores) { echo '<tr><td colspan="7" class="text-center text-muted">No data.</td></tr>'; }
                    foreach ($appraisalScores as $r) {
                        echo '<tr>';
                        echo '<td>' . (int)$r['appraisal_id'] . '</td>';
                        echo '<td>' . htmlspecialchars($r['employee_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($r['dept_name']) . '</td>';
                        echo '<td>' . (int)$r['year'] . '</td>';
                        echo '<td>' . htmlspecialchars($r['final_score'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($r['criterion'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($r['criterion_score'] === null ? '-' : number_format((float)$r['criterion_score'], 2)) . '</td>';
                        echo '</tr>';
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-users me-2"></i>Employee Task Summary</span>
        <div class="d-flex align-items-center gap-2">
            <input type="text" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-filter="#tblEmpSummary">
            <a class="btn btn-sm btn-outline-primary" href="export.php?type=employee_task_summary">Export CSV</a>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print / PDF</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm" id="tblEmpSummary">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Total Tasks</th>
                        <th>Completed</th>
                        <th>Completion Rate %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$employeeTaskSummary) { echo '<tr><td colspan="5" class="text-center text-muted">No data.</td></tr>'; }
                    foreach ($employeeTaskSummary as $r) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($r['employee_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($r['dept_name']) . '</td>';
                        echo '<td>' . (int)$r['total_tasks'] . '</td>';
                        echo '<td>' . (int)$r['completed_tasks'] . '</td>';
                        echo '<td>' . ($r['completion_rate'] === null ? '-' : htmlspecialchars((string)$r['completion_rate'])) . '</td>';
                        echo '</tr>';
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-comments me-2"></i>Feedback Exchange</span>
        <div class="d-flex align-items-center gap-2">
            <input type="text" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-filter="#tblFeedback">
            <a class="btn btn-sm btn-outline-primary" href="export.php?type=feedback_exchange">Export CSV</a>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print / PDF</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm" id="tblFeedback">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Comments</th>
                        <th>Created</th>
                        <th>Task</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$feedbackExchange) { echo '<tr><td colspan="6" class="text-center text-muted">No data.</td></tr>'; }
                    foreach ($feedbackExchange as $r) {
                        echo '<tr>';
                        echo '<td>' . (int)$r['feedback_id'] . '</td>';
                        echo '<td>' . htmlspecialchars($r['from_user']) . '</td>';
                        echo '<td>' . htmlspecialchars($r['to_user']) . '</td>';
                        echo '<td>' . htmlspecialchars($r['comments']) . '</td>';
                        echo '<td>' . htmlspecialchars(formatDateTime($r['created_at'])) . '</td>';
                        echo '<td>' . htmlspecialchars($r['related_task'] ?? '-') . '</td>';
                        echo '</tr>';
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-clipboard-list me-2"></i>Task Status Overview</span>
        <div class="d-flex align-items-center gap-2">
            <input type="text" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-filter="#tblTaskOverview">
            <a class="btn btn-sm btn-outline-primary" href="export.php?type=task_status_overview">Export CSV</a>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print / PDF</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm" id="tblTaskOverview">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Goal</th>
                        <th>Agreement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$taskStatusOverview) { echo '<tr><td colspan="6" class="text-center text-muted">No data.</td></tr>'; }
                    foreach ($taskStatusOverview as $r) {
                        $badge = 'status-pending';
                        if ($r['status'] === 'Completed') $badge = 'status-completed';
                        elseif ($r['status'] === 'In Progress') $badge = 'status-in-progress';
                        elseif ($r['status'] === 'Overdue') $badge = 'status-overdue';
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($r['title']) . '</td>';
                        echo '<td>' . htmlspecialchars(formatDate($r['due_date'])) . '</td>';
                        echo '<td><span class="badge ' . $badge . '">' . htmlspecialchars($r['status']) . '</span></td>';
                        echo '<td>' . htmlspecialchars($r['assigned_to'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($r['goal'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($r['agreement'] ?? '-') . '</td>';
                        echo '</tr>';
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-shield-alt me-2"></i>Audit Trail</span>
        <div class="d-flex align-items-center gap-2">
            <input type="text" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-filter="#tblAuditTrail">
            <a class="btn btn-sm btn-outline-primary" href="export.php?type=audit_trail">Export CSV</a>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print / PDF</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm" id="tblAuditTrail">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$auditTrail) { echo '<tr><td colspan="5" class="text-center text-muted">No data.</td></tr>'; }
                    foreach ($auditTrail as $r) {
                        echo '<tr>';
                        echo '<td>' . (int)$r['log_id'] . '</td>';
                        echo '<td>' . htmlspecialchars($r['user_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($r['role_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($r['action']) . '</td>';
                        echo '<td>' . htmlspecialchars(formatDateTime($r['timestamp'])) . '</td>';
                        echo '</tr>';
                    } ?>
                </tbody>
            </table>
        </div>
        <nav class="mt-3">
            <ul class="pagination justify-content-end">
                <?php $base = 'reports.php?'; ?>
                <li class="page-item <?php echo $page<=1?'disabled':''; ?>">
                    <a class="page-link" href="<?php echo $base . 'page=' . max(1, $page-1); ?>">Previous</a>
                </li>
                <li class="page-item disabled"><span class="page-link">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span></li>
                <li class="page-item <?php echo $page>=$totalPages?'disabled':''; ?>">
                    <a class="page-link" href="<?php echo $base . 'page=' . min($totalPages, $page+1); ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


