<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
if (!hasRole(1)) { redirect('dashboard.php'); }

$db = new Database();
$conn = $db->getConnection();

$page_title = 'Admin • Audit Logs';
include __DIR__ . '/includes/header.php';

// Simple pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;
$total = (int)$conn->query('SELECT COUNT(*) FROM audit_trail_report')->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$rows = $conn->query('SELECT * FROM audit_trail_report ORDER BY timestamp DESC LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-shield-alt me-2"></i>Audit Logs</span>
        <input type="text" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-filter="#tblAudit">
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm" id="tblAudit">
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
                    <?php if (!$rows) { echo '<tr><td colspan="5" class="text-center text-muted">No logs.</td></tr>'; }
                    foreach ($rows as $r) {
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
                <?php $base = 'audit_logs.php?'; ?>
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


