<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/mailer.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Handle quick status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $taskId = (int)$_POST['task_id'];
    $status = $_POST['status'];
    $allowed = ['Pending','In Progress','Completed','Overdue'];
    if (in_array($status, $allowed, true)) {
        $stmt = $conn->prepare("UPDATE task t JOIN task_assignment ta ON t.task_id = ta.task_id SET t.status = :status WHERE t.task_id = :tid AND ta.user_id = :uid");
        $stmt->execute([':status' => $status, ':tid' => $taskId, ':uid' => (int)$_SESSION['user_id']]);
        // Notify manager/admins (optional simplified: notify first manager)
        try {
            $mgr = $conn->query("SELECT email, name FROM user WHERE role_id IN (1,2) ORDER BY role_id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($mgr) {
                $subject = 'Task Status Updated';
                $body = '<p>Task #' . (int)$taskId . ' status updated to <strong>' . htmlspecialchars($status) . '</strong> by ' . htmlspecialchars($_SESSION['name']) . '.</p>';
                sendEmail($mgr['email'], $subject, $body);
            }
        } catch (Throwable $e) { /* ignore */ }
    }
    redirect('tasks.php');
}

// Handle add worklog
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_worklog') {
    $taskId = (int)$_POST['task_id'];
    $progress = trim($_POST['progress']);
    if ($progress !== '') {
        $stmt = $conn->prepare("INSERT INTO task_worklog (task_id, user_id, progress) SELECT :tid, :uid, :progress FROM task_assignment WHERE task_id = :tid AND user_id = :uid");
        $stmt->execute([':tid' => $taskId, ':uid' => (int)$_SESSION['user_id'], ':progress' => $progress]);
    }
    redirect('tasks.php');
}

$page_title = 'My Tasks';
include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-tasks me-2"></i>My Tasks</span>
        <div class="d-flex align-items-center">
            <input type="text" class="form-control form-control-sm me-2" placeholder="Search..." data-table-filter="#myTasksTable">
            <a class="btn btn-sm btn-outline-primary" href="export.php?type=my_tasks">Export CSV</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle" id="myTasksTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Update</th>
                        <th>Worklog</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT t.task_id, t.title, t.due_date, t.status FROM task t JOIN task_assignment ta ON t.task_id = ta.task_id WHERE ta.user_id = :uid ORDER BY t.due_date ASC");
                    $stmt->execute([':uid' => (int)$_SESSION['user_id']]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (!$rows) {
                        echo '<tr><td colspan="5" class="text-center text-muted">No tasks assigned yet.</td></tr>';
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
                            echo '<td>';
                            echo '<form method="post" class="d-flex gap-2">';
                            echo '<input type="hidden" name="action" value="update_status">';
                            echo '<input type="hidden" name="task_id" value="' . (int)$row['task_id'] . '">';
                            echo '<select name="status" class="form-select form-select-sm" required>';
                            foreach (['Pending','In Progress','Completed','Overdue'] as $s) {
                                $sel = $s === $row['status'] ? 'selected' : '';
                                echo '<option ' . $sel . '>' . $s . '</option>';
                            }
                            echo '</select>';
                            echo '<button class="btn btn-primary btn-sm" type="submit">Save</button>';
                            echo '</form>';
                            echo '</td>';
                            echo '<td>';
                            echo '<form method="post" class="d-flex gap-2">';
                            echo '<input type="hidden" name="action" value="add_worklog">';
                            echo '<input type="hidden" name="task_id" value="' . (int)$row['task_id'] . '">';
                            echo '<input type="text" name="progress" class="form-control form-control-sm" placeholder="Progress update">';
                            echo '<button class="btn btn-success btn-sm" type="submit">Add</button>';
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


