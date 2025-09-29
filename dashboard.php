<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$page_title = 'Dashboard';
include __DIR__ . '/includes/header.php';

// Quick stats (safe defaults if views not yet populated)
$db = new Database();
$conn = $db->getConnection();

$stats = [
    'total_tasks' => 0,
    'completed_tasks' => 0,
    'pending_tasks' => 0,
    'agreements' => 0,
];

try {
    $uid = (int)$_SESSION['user_id'];
    $stats['total_tasks'] = (int)$conn->query("SELECT COUNT(*) FROM task t JOIN task_assignment ta ON t.task_id = ta.task_id WHERE ta.user_id = $uid")->fetchColumn();
    $stats['completed_tasks'] = (int)$conn->query("SELECT COUNT(*) FROM task t JOIN task_assignment ta ON t.task_id = ta.task_id WHERE ta.user_id = $uid AND t.status = 'Completed'")->fetchColumn();
    $stats['pending_tasks'] = (int)$conn->query("SELECT COUNT(*) FROM task t JOIN task_assignment ta ON t.task_id = ta.task_id WHERE ta.user_id = $uid AND t.status IN ('Pending','In Progress','Overdue')")->fetchColumn();
    $stats['agreements'] = (int)$conn->query("SELECT COUNT(*) FROM yearly_agreement WHERE user_id = $uid")->fetchColumn();
} catch (Throwable $e) {
    // keep defaults
}
?>

<div class="row g-3">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card dashboard-card fade-in">
            <div class="card-header"><i class="fas fa-list-check me-2"></i>Total Tasks</div>
            <div class="card-body">
                <div class="stat-number"><?php echo $stats['total_tasks']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card dashboard-card fade-in">
            <div class="card-header"><i class="fas fa-check-circle me-2"></i>Completed</div>
            <div class="card-body">
                <div class="stat-number text-success"><?php echo $stats['completed_tasks']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card dashboard-card fade-in">
            <div class="card-header"><i class="fas fa-hourglass-half me-2"></i>Outstanding</div>
            <div class="card-body">
                <div class="stat-number text-warning"><?php echo $stats['pending_tasks']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card dashboard-card fade-in">
            <div class="card-header"><i class="fas fa-file-signature me-2"></i>Agreements</div>
            <div class="card-body">
                <div class="stat-number text-primary"><?php echo $stats['agreements']; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><i class="fas fa-calendar me-2"></i>My Upcoming Tasks</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmt = $conn->prepare("SELECT t.title, t.due_date, t.status FROM task t JOIN task_assignment ta ON t.task_id = ta.task_id WHERE ta.user_id = :uid ORDER BY t.due_date ASC LIMIT 10");
                        $stmt->execute([':uid' => (int)$_SESSION['user_id']]);
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (!$rows) {
                            echo '<tr><td colspan="3" class="text-center text-muted">No tasks found.</td></tr>';
                        } else {
                            foreach ($rows as $row) {
                                $badge = 'status-pending';
                                if ($row['status'] === 'Completed') $badge = 'status-completed';
                                elseif ($row['status'] === 'In Progress') $badge = 'status-in-progress';
                                elseif ($row['status'] === 'Overdue') $badge = 'status-overdue';
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['title']) . '</td>';
                                echo '<td>' . htmlspecialchars(formatDate($row['due_date'])) . '</td>';
                                echo '<td><span class="badge ' . $badge . '">' . htmlspecialchars($row['status']) . '</span></td>';
                                echo '</tr>';
                            }
                        }
                    } catch (Throwable $e) {
                        echo '<tr><td colspan="3" class="text-danger">Error loading tasks.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
 </div>

<?php include __DIR__ . '/includes/footer.php'; ?>


