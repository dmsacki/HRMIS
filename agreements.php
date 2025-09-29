<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Create yearly agreement (employee creates their own)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_agreement'])) {
    $title = trim($_POST['title']);
    $year = (int)$_POST['year'];
    if ($title !== '' && $year) {
        $stmt = $conn->prepare('INSERT INTO yearly_agreement (user_id, title, year) VALUES (:uid, :title, :year)');
        $stmt->execute([':uid' => (int)$_SESSION['user_id'], ':title' => $title, ':year' => $year]);
        $msg = 'Agreement created.';
    } else {
        $err = 'Provide title and year.';
    }
}

// Add goal to agreement (owner or admin/manager)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    $agreement_id = (int)$_POST['agreement_id'];
    $description = trim($_POST['description']);
    if ($description !== '') {
        // ownership or elevated role
        $owns = $conn->prepare('SELECT 1 FROM yearly_agreement WHERE agreement_id = :aid AND user_id = :uid');
        $owns->execute([':aid' => $agreement_id, ':uid' => (int)$_SESSION['user_id']]);
        if ($owns->fetch() || hasRole(1) || hasRole(2)) {
            $stmt = $conn->prepare('INSERT INTO goal (agreement_id, description) VALUES (:aid, :desc)');
            $stmt->execute([':aid' => $agreement_id, ':desc' => $description]);
            $msg = 'Goal added.';
        }
    }
}

// Approve/Reject (manager/admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_status']) && (hasRole(1) || hasRole(2))) {
    $agreement_id = (int)$_POST['agreement_id'];
    $status = $_POST['status'];
    if (in_array($status, ['Pending','Approved','Rejected'], true)) {
        $stmt = $conn->prepare('UPDATE yearly_agreement SET status = :status WHERE agreement_id = :aid');
        $stmt->execute([':status' => $status, ':aid' => $agreement_id]);
        $msg = 'Status updated.';
    }
}

$page_title = 'Yearly Agreements';
include __DIR__ . '/includes/header.php';

// Load agreements: employees see their own; managers/admins see all
if (hasRole(1) || hasRole(2)) {
    $agreements = $conn->query('SELECT ya.agreement_id, ya.title, ya.year, ya.status, u.name AS owner FROM yearly_agreement ya JOIN user u ON ya.user_id = u.user_id ORDER BY ya.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare('SELECT agreement_id, title, year, status FROM yearly_agreement WHERE user_id = :uid ORDER BY created_at DESC');
    $stmt->execute([':uid' => (int)$_SESSION['user_id']]);
    $agreements = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fas fa-file-signature me-2"></i>Create Agreement</div>
            <div class="card-body">
                <?php if (!empty($msg)): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
                <?php if (!empty($err)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" min="2000" max="2100" value="<?php echo date('Y'); ?>" required>
                    </div>
                    <button type="submit" name="create_agreement" class="btn btn-primary">Create</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fas fa-list me-2"></i>Agreements</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Owner</th>
                                <th>Year</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!$agreements) { echo '<tr><td colspan="5" class="text-center text-muted">No agreements.</td></tr>'; }
                            foreach ($agreements as $a) {
                                $owner = isset($a['owner']) ? $a['owner'] : $_SESSION['name'];
                                $badge = $a['status'] === 'Approved' ? 'status-approved' : ($a['status'] === 'Rejected' ? 'status-rejected' : 'status-pending');
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($a['title']) . '</td>';
                                echo '<td>' . htmlspecialchars($owner) . '</td>';
                                echo '<td>' . (int)$a['year'] . '</td>';
                                echo '<td><span class="badge ' . $badge . '">' . htmlspecialchars($a['status']) . '</span></td>';
                                echo '<td>';
                                echo '<button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#goals-' . (int)$a['agreement_id'] . '">Goals</button> ';
                                if (hasRole(1) || hasRole(2)) {
                                    echo '<form method="post" class="d-inline ms-2">';
                                    echo '<input type="hidden" name="agreement_id" value="' . (int)$a['agreement_id'] . '">';
                                    echo '<select name="status" class="form-select form-select-sm d-inline w-auto me-1">';
                                    foreach (['Pending','Approved','Rejected'] as $st) {
                                        $sel = $st === $a['status'] ? 'selected' : '';
                                        echo '<option ' . $sel . '>' . $st . '</option>';
                                    }
                                    echo '</select>';
                                    echo '<button class="btn btn-sm btn-primary" name="set_status">Set</button>';
                                    echo '</form>';
                                }
                                echo '</td>';
                                echo '</tr>';
                                // Goals row
                                echo '<tr class="collapse" id="goals-' . (int)$a['agreement_id'] . '"><td colspan="5">';
                                // list goals
                                $gs = $conn->prepare('SELECT goal_id, description FROM goal WHERE agreement_id = :aid');
                                $gs->execute([':aid' => (int)$a['agreement_id']]);
                                $list = $gs->fetchAll(PDO::FETCH_ASSOC);
                                echo '<ul class="mb-3">';
                                if (!$list) {
                                    echo '<li class="text-muted">No goals.</li>';
                                } else {
                                    foreach ($list as $g) {
                                        echo '<li>' . htmlspecialchars($g['description']) . '</li>';
                                    }
                                }
                                echo '</ul>';
                                // add goal form
                                echo '<form method="post" class="d-flex gap-2">';
                                echo '<input type="hidden" name="agreement_id" value="' . (int)$a['agreement_id'] . '">';
                                echo '<input type="hidden" name="add_goal" value="1">';
                                echo '<input type="text" name="description" class="form-control" placeholder="New goal description">';
                                echo '<button class="btn btn-success">Add Goal</button>';
                                echo '</form>';
                                echo '</td></tr>';
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


