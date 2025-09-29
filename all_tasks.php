<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
if (!(hasRole(1) || hasRole(2))) { redirect('tasks.php'); }

$db = new Database();
$conn = $db->getConnection();

$page_title = 'All Tasks';
include __DIR__ . '/includes/header.php';

// Filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$where = '';
$params = [];
if (in_array($statusFilter, ['Pending','In Progress','Completed','Overdue'], true)) {
    $where = 'WHERE t.status = :status';
    $params[':status'] = $statusFilter;
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Count total
$countSql = "SELECT COUNT(*) FROM task t JOIN task_assignment ta ON t.task_id = ta.task_id JOIN user u ON ta.user_id = u.user_id $where";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT t.task_id, t.title, t.due_date, t.status, u.name AS assignee FROM task t JOIN task_assignment ta ON t.task_id = ta.task_id JOIN user u ON ta.user_id = u.user_id $where ORDER BY t.due_date ASC LIMIT :lim OFFSET :off";
$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-table me-2"></i>All Tasks</span>
        <form class="d-flex" method="get">
            <select name="status" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                <option value="">All statuses</option>
                <?php foreach (['Pending','In Progress','Completed','Overdue'] as $st): ?>
                    <option value="<?php echo $st; ?>" <?php echo $statusFilter===$st?'selected':''; ?>><?php echo $st; ?></option>
                <?php endforeach; ?>
            </select>
            <a class="btn btn-sm btn-outline-secondary" href="all_tasks.php">Reset</a>
            <input type="text" class="form-control form-control-sm ms-2" placeholder="Search..." data-table-filter="#allTasksTable">
            <a class="btn btn-sm btn-outline-primary ms-2" href="export.php?type=all_tasks<?php echo $statusFilter?('&status='.urlencode($statusFilter)) : '' ; ?>">Export CSV</a>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle" id="allTasksTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Assignee</th>
                        <th>Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!$rows) {
                        echo '<tr><td colspan="5" class="text-center text-muted">No tasks found.</td></tr>';
                    } else {
                        foreach ($rows as $r) {
                            $badge = 'status-pending';
                            if ($r['status'] === 'Completed') $badge = 'status-completed';
                            elseif ($r['status'] === 'In Progress') $badge = 'status-in-progress';
                            elseif ($r['status'] === 'Overdue') $badge = 'status-overdue';
                            echo '<tr>';
                            echo '<td>' . (int)$r['task_id'] . '</td>';
                            echo '<td>' . htmlspecialchars($r['title']) . '</td>';
                            echo '<td>' . htmlspecialchars($r['assignee']) . '</td>';
                            echo '<td>' . htmlspecialchars(formatDate($r['due_date'])) . '</td>';
                            echo '<td><span class="badge ' . $badge . '">' . htmlspecialchars($r['status']) . '</span></td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <nav class="mt-3">
            <ul class="pagination justify-content-end">
                <?php
                $qs = $_GET; unset($qs['page']);
                $base = 'all_tasks.php' . (count($qs) ? ('?' . http_build_query($qs) . '&') : '?');
                ?>
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


