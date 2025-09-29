<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/mailer.php';
requireLogin();
if (!(hasRole(1) || hasRole(2))) { redirect('tasks.php'); }

$db = new Database();
$conn = $db->getConnection();

// Create task and assign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $assignee = (int)$_POST['assignee'];

    if ($title !== '' && $due_date !== '' && $assignee > 0) {
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare('INSERT INTO task (goal_id, title, description, due_date, status) VALUES (NULL, :title, :description, :due_date, "Pending")');
            $stmt->execute([':title' => $title, ':description' => $description, ':due_date' => $due_date]);
            $taskId = (int)$conn->lastInsertId();

            $assign = $conn->prepare('INSERT INTO task_assignment (task_id, user_id) VALUES (:tid, :uid)');
            $assign->execute([':tid' => $taskId, ':uid' => $assignee]);

            // Email notification to assignee
            $userStmt = $conn->prepare('SELECT email, name FROM user WHERE user_id = :uid');
            $userStmt->execute([':uid' => $assignee]);
            if ($u = $userStmt->fetch(PDO::FETCH_ASSOC)) {
                $subject = 'New Task Assigned: ' . $title;
                $body = '<p>Dear ' . htmlspecialchars($u['name']) . ',</p>'
                    . '<p>You have been assigned a new task: <strong>' . htmlspecialchars($title) . '</strong></p>'
                    . '<p>Due Date: ' . htmlspecialchars($due_date) . '</p>'
                    . '<p>Description: ' . nl2br(htmlspecialchars($description)) . '</p>'
                    . '<p>Please log in to the HRMIS to view details.</p>';
                sendEmail($u['email'], $subject, $body);
            }

            $conn->commit();
            $success = 'Task created and assigned successfully.';
        } catch (Throwable $e) {
            $conn->rollBack();
            $error = 'Failed to create task.';
        }
    } else {
        $error = 'Please provide title, due date and assignee.';
    }
}

$page_title = 'Assign Tasks';
include __DIR__ . '/includes/header.php';

// Load employees for assignment (role_id=3)
$employees = $conn->query("SELECT user_id, name FROM user WHERE role_id = 3 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fas fa-plus me-2"></i>Create & Assign Task</div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <select name="assignee" class="form-select" required>
                            <option value="">Select employee</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo (int)$emp['user_id']; ?>"><?php echo htmlspecialchars($emp['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="create_task" class="btn btn-primary">Create Task</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fas fa-list me-2"></i>Recent Tasks</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Assignee</th>
                                <th>Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rows = $conn->query("SELECT t.title, t.due_date, t.status, u.name AS assignee FROM task t JOIN task_assignment ta ON t.task_id = ta.task_id JOIN user u ON ta.user_id = u.user_id ORDER BY t.created_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
                            if (!$rows) {
                                echo '<tr><td colspan="4" class="text-center text-muted">No tasks yet.</td></tr>';
                            } else {
                                foreach ($rows as $r) {
                                    $badge = 'status-pending';
                                    if ($r['status'] === 'Completed') $badge = 'status-completed';
                                    elseif ($r['status'] === 'In Progress') $badge = 'status-in-progress';
                                    elseif ($r['status'] === 'Overdue') $badge = 'status-overdue';
                                    echo '<tr>';
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
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


