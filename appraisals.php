<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Create appraisal for a user in a cycle (manager/admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_appraisal']) && (hasRole(1) || hasRole(2))) {
    $cycle_id = (int)$_POST['cycle_id'];
    $user_id = (int)$_POST['user_id'];
    if ($cycle_id && $user_id) {
        // Prevent duplicates per user per cycle
        $exists = $conn->prepare('SELECT 1 FROM appraisal WHERE cycle_id = :cid AND user_id = :uid LIMIT 1');
        $exists->execute([':cid' => $cycle_id, ':uid' => $user_id]);
        if ($exists->fetch()) {
            $err = 'An appraisal for this employee in the selected cycle already exists.';
        } else {
            $stmt = $conn->prepare('INSERT INTO appraisal (cycle_id, user_id) VALUES (:cid, :uid)');
            $stmt->execute([':cid' => $cycle_id, ':uid' => $user_id]);
            $msg = 'Appraisal created.';
        }
    }
}

// Add criterion score (self if owner; manager if manager/admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_detail'])) {
    $appraisal_id = (int)$_POST['appraisal_id'];
    $criterion = trim($_POST['criterion']);
    $score = (int)$_POST['score'];
    $as = $_POST['as'] === 'manager' ? 'manager' : 'self';
    if ($criterion !== '' && $score >= 0 && $score <= 100) {
        // ensure permission
        $ownerStmt = $conn->prepare('SELECT user_id FROM appraisal WHERE appraisal_id = :aid');
        $ownerStmt->execute([':aid' => $appraisal_id]);
        $ownerId = (int)$ownerStmt->fetchColumn();
        $canSelf = $ownerId === (int)$_SESSION['user_id'];
        $canMgr = hasRole(1) || hasRole(2);
        if (($as === 'self' && $canSelf) || ($as === 'manager' && $canMgr)) {
            // insert or update criterion row
            $sel = $conn->prepare('SELECT detail_id FROM appraisal_detail WHERE appraisal_id = :aid AND criterion = :c');
            $sel->execute([':aid' => $appraisal_id, ':c' => $criterion]);
            $detailId = $sel->fetchColumn();
            if ($detailId) {
                if ($as === 'self') {
                    $upd = $conn->prepare('UPDATE appraisal_detail SET self_score = :s WHERE detail_id = :id');
                } else {
                    $upd = $conn->prepare('UPDATE appraisal_detail SET manager_score = :s WHERE detail_id = :id');
                }
                $upd->execute([':s' => $score, ':id' => (int)$detailId]);
            } else {
                if ($as === 'self') {
                    $ins = $conn->prepare('INSERT INTO appraisal_detail (appraisal_id, criterion, self_score) VALUES (:aid, :c, :s)');
                } else {
                    $ins = $conn->prepare('INSERT INTO appraisal_detail (appraisal_id, criterion, manager_score) VALUES (:aid, :c, :s)');
                }
                $ins->execute([':aid' => $appraisal_id, ':c' => $criterion, ':s' => $score]);
            }
            $msg = 'Score saved.';
        }
    }
}

// Recalculate final score (manager/admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recalculate']) && (hasRole(1) || hasRole(2))) {
    $appraisal_id = (int)$_POST['appraisal_id'];
    // average of available criterion scores
    $stmt = $conn->prepare('SELECT self_score, manager_score FROM appraisal_detail WHERE appraisal_id = :aid');
    $stmt->execute([':aid' => $appraisal_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = 0; $count = 0;
    foreach ($rows as $r) {
        if ($r['self_score'] !== null && $r['manager_score'] !== null) {
            $total += ((int)$r['self_score'] + (int)$r['manager_score']) / 2.0;
            $count++;
        } elseif ($r['self_score'] !== null || $r['manager_score'] !== null) {
            $total += (int)($r['self_score'] ?? $r['manager_score']);
            $count++;
        }
    }
    $final = $count ? round($total / $count, 2) : null;
    $upd = $conn->prepare('UPDATE appraisal SET final_score = :fs WHERE appraisal_id = :aid');
    $upd->execute([':fs' => $final, ':aid' => $appraisal_id]);
    $msg = 'Final score updated.';
}

// Delete appraisal (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appraisal']) && hasRole(1)) {
    $appraisal_id = (int)$_POST['appraisal_id'];
    try {
        $conn->beginTransaction();
        $delDetails = $conn->prepare('DELETE FROM appraisal_detail WHERE appraisal_id = :aid');
        $delDetails->execute([':aid' => $appraisal_id]);
        $delApp = $conn->prepare('DELETE FROM appraisal WHERE appraisal_id = :aid');
        $delApp->execute([':aid' => $appraisal_id]);
        $conn->commit();
        $msg = 'Appraisal deleted.';
    } catch (Throwable $e) {
        if ($conn->inTransaction()) { $conn->rollBack(); }
        $err = 'Failed to delete appraisal.';
    }
}

$page_title = 'Appraisals';
include __DIR__ . '/includes/header.php';

// Data for selects
$cycles = $conn->query('SELECT cycle_id, year FROM appraisal_cycle ORDER BY year DESC')->fetchAll(PDO::FETCH_ASSOC);
$employees = $conn->query('SELECT user_id, name FROM user WHERE role_id = 3 ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

// Cycle filter (show one row per user for the selected cycle)
$selectedCycleId = null;
if (!empty($cycles)) {
    $selectedCycleId = isset($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : (int)$cycles[0]['cycle_id'];
}

// List appraisals: employees see theirs; managers/admin see all for selected cycle
if ($selectedCycleId) {
    if (hasRole(1) || hasRole(2)) {
        $stmt = $conn->prepare('SELECT a.appraisal_id, a.final_score, u.name, ac.year FROM appraisal a JOIN user u ON a.user_id = u.user_id JOIN appraisal_cycle ac ON a.cycle_id = ac.cycle_id WHERE ac.cycle_id = :cid ORDER BY u.name');
        $stmt->execute([':cid' => $selectedCycleId]);
        $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $conn->prepare('SELECT a.appraisal_id, a.final_score, ac.year FROM appraisal a JOIN appraisal_cycle ac ON a.cycle_id = ac.cycle_id WHERE a.user_id = :uid AND ac.cycle_id = :cid');
        $stmt->execute([':uid' => (int)$_SESSION['user_id'], ':cid' => $selectedCycleId]);
        $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $apps = [];
}
?>

<div class="row g-3">
    <?php if (hasRole(1) || hasRole(2)): ?>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-plus me-2"></i>Create Appraisal</span>
                <form method="get" class="d-flex align-items-center">
                    <label class="me-2">Cycle</label>
                    <select name="cycle_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($cycles as $c): ?>
                            <option value="<?php echo (int)$c['cycle_id']; ?>" <?php echo ($selectedCycleId===(int)$c['cycle_id'])?'selected':''; ?>><?php echo (int)$c['year']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="card-body">
                <?php if (!empty($msg)): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
                <?php if (!empty($err)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Cycle</label>
                        <select name="cycle_id" class="form-select" required>
                            <option value="">Select cycle</option>
                            <?php foreach ($cycles as $c): ?>
                                <option value="<?php echo (int)$c['cycle_id']; ?>" <?php echo ($selectedCycleId===(int)$c['cycle_id'])?'selected':''; ?>><?php echo (int)$c['year']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select employee</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?php echo (int)$e['user_id']; ?>"><?php echo htmlspecialchars($e['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" name="create_appraisal">Create</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list me-2"></i>Appraisals</span>
                <?php if (hasRole(1) || hasRole(2)): ?>
                <a class="btn btn-sm btn-outline-primary" href="export.php?type=appraisals<?php echo $selectedCycleId ? ('&cycle_id='.(int)$selectedCycleId) : '' ; ?>">Export CSV</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Year</th>
                                <th>Final Score</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!$apps) { echo '<tr><td colspan="4" class="text-center text-muted">No appraisals.</td></tr>'; }
                            foreach ($apps as $a) {
                                $emp = isset($a['name']) ? $a['name'] : $_SESSION['name'];
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($emp) . '</td>';
                                echo '<td>' . (int)$a['year'] . '</td>';
                                echo '<td>' . htmlspecialchars($a['final_score'] ?? '-') . '</td>';
                                echo '<td>';
                                echo '<button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#details-' . (int)$a['appraisal_id'] . '">Open</button>';
                                if (hasRole(1)) {
                                    echo ' <form method="post" class="d-inline ms-2" onsubmit="return confirm(\'Delete this appraisal? This can\'\'t be undone.\');">';
                                    echo '<input type="hidden" name="appraisal_id" value="' . (int)$a['appraisal_id'] . '">';
                                    echo '<button class="btn btn-sm btn-danger" name="delete_appraisal">Delete</button>';
                                    echo '</form>';
                                }
                                echo '</td>';
                                echo '</tr>';
                                echo '<tr class="collapse" id="details-' . (int)$a['appraisal_id'] . '"><td colspan="4">';
                                // list details
                                $ds = $conn->prepare('SELECT criterion, self_score, manager_score FROM appraisal_detail WHERE appraisal_id = :aid');
                                $ds->execute([':aid' => (int)$a['appraisal_id']]);
                                $list = $ds->fetchAll(PDO::FETCH_ASSOC);
                                echo '<div class="row">';
                                echo '<div class="col-md-7">';
                                echo '<table class="table table-sm"><thead><tr><th>Criterion</th><th>Self</th><th>Manager</th></tr></thead><tbody>';
                                if (!$list) {
                                    echo '<tr><td colspan="3" class="text-muted">No criteria.</td></tr>';
                                } else {
                                    foreach ($list as $d) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($d['criterion']) . '</td>';
                                        echo '<td>' . htmlspecialchars($d['self_score'] === null ? '-' : $d['self_score']) . '</td>';
                                        echo '<td>' . htmlspecialchars($d['manager_score'] === null ? '-' : $d['manager_score']) . '</td>';
                                        echo '</tr>';
                                    }
                                }
                                echo '</tbody></table>';
                                echo '</div>';
                                echo '<div class="col-md-5">';
                                echo '<div class="card"><div class="card-body">';
                                echo '<form method="post" class="mb-3">';
                                echo '<input type="hidden" name="appraisal_id" value="' . (int)$a['appraisal_id'] . '">';
                                echo '<input type="hidden" name="add_detail" value="1">';
                                echo '<div class="mb-2"><label class="form-label">Criterion</label><input type="text" name="criterion" class="form-control" required></div>';
                                echo '<div class="mb-2"><label class="form-label">Score (0-100)</label><input type="number" name="score" class="form-control" min="0" max="100" required></div>';
                                echo '<div class="mb-2"><label class="form-label">As</label><select name="as" class="form-select">';
                                echo '<option value="self">Self</option>';
                                if (hasRole(1) || hasRole(2)) echo '<option value="manager">Manager</option>';
                                echo '</select></div>';
                                echo '<button class="btn btn-primary">Save Score</button>';
                                echo '</form>';
                                if (hasRole(1) || hasRole(2)) {
                                    echo '<form method="post">';
                                    echo '<input type="hidden" name="appraisal_id" value="' . (int)$a['appraisal_id'] . '">';
                                    echo '<button class="btn btn-success" name="recalculate">Recalculate Final Score</button>';
                                    echo '</form>';
                                }
                                echo '</div></div>';
                                echo '</div>';
                                echo '</div>';
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


