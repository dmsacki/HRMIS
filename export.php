<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$type = isset($_GET['type']) ? $_GET['type'] : '';

$db = new Database();
$conn = $db->getConnection();

function outputCsv($filename, $header, $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $out = fopen('php://output', 'w');
    fputcsv($out, $header);
    foreach ($rows as $r) { fputcsv($out, $r); }
    fclose($out);
    exit;
}

switch ($type) {
    case 'appraisal_scores':
        $data = $conn->query('SELECT appraisal_id, employee_name, dept_name, year, final_score, criterion, criterion_score FROM appraisal_scores_report ORDER BY year DESC, employee_name')->fetchAll(PDO::FETCH_NUM);
        outputCsv('appraisal_scores.csv', ['Appraisal ID','Employee','Department','Year','Final Score','Criterion','Criterion Score'], $data);
        break;
    case 'employee_task_summary':
        $data = $conn->query('SELECT user_id, employee_name, dept_name, total_tasks, completed_tasks, completion_rate FROM employee_task_summary ORDER BY completion_rate DESC')->fetchAll(PDO::FETCH_NUM);
        outputCsv('employee_task_summary.csv', ['User ID','Employee','Department','Total Tasks','Completed','Completion Rate %'], $data);
        break;
    case 'feedback_exchange':
        $data = $conn->query('SELECT feedback_id, from_user, to_user, comments, created_at, related_task FROM feedback_exchange ORDER BY created_at DESC')->fetchAll(PDO::FETCH_NUM);
        outputCsv('feedback_exchange.csv', ['Feedback ID','From','To','Comments','Created At','Related Task'], $data);
        break;
    case 'task_status_overview':
        $data = $conn->query('SELECT task_id, title, due_date, status, assigned_to, goal, agreement FROM task_status_overview ORDER BY due_date ASC')->fetchAll(PDO::FETCH_NUM);
        outputCsv('task_status_overview.csv', ['Task ID','Title','Due Date','Status','Assigned To','Goal','Agreement'], $data);
        break;
    case 'audit_trail':
        $data = $conn->query('SELECT log_id, user_name, role_name, action, timestamp FROM audit_trail_report ORDER BY timestamp DESC')->fetchAll(PDO::FETCH_NUM);
        outputCsv('audit_trail.csv', ['Log ID','User','Role','Action','Timestamp'], $data);
        break;
    case 'all_tasks':
        if (!(hasRole(1) || hasRole(2))) { die('Unauthorized'); }
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $where = '';
        if (in_array($status, ['Pending','In Progress','Completed','Overdue'], true)) {
            $where = "WHERE t.status = '" . str_replace("'","''", $status) . "'";
        }
        $sql = "SELECT t.task_id, t.title, t.due_date, t.status, u.name AS assignee FROM task t JOIN task_assignment ta ON t.task_id = ta.task_id JOIN user u ON ta.user_id = u.user_id $where ORDER BY t.due_date ASC";
        $rows = $conn->query($sql)->fetchAll(PDO::FETCH_NUM);
        outputCsv('all_tasks.csv', ['Task ID','Title','Due Date','Status','Assignee'], $rows);
        break;
    case 'my_tasks':
        // allow any logged-in user to export their own tasks
        $uid = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT t.task_id, t.title, t.due_date, t.status FROM task t JOIN task_assignment ta ON t.task_id = ta.task_id WHERE ta.user_id = :uid ORDER BY t.due_date ASC");
        $stmt->execute([':uid' => $uid]);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        outputCsv('my_tasks.csv', ['Task ID','Title','Due Date','Status'], $rows);
        break;
    case 'appraisals':
        if (!(hasRole(1) || hasRole(2))) { die('Unauthorized'); }
        $cycleId = isset($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : 0;
        if ($cycleId) {
            $stmt = $conn->prepare('SELECT u.name, ac.year, a.appraisal_id, a.final_score FROM appraisal a JOIN user u ON a.user_id = u.user_id JOIN appraisal_cycle ac ON a.cycle_id = ac.cycle_id WHERE ac.cycle_id = :cid ORDER BY u.name');
            $stmt->execute([':cid' => $cycleId]);
        } else {
            $stmt = $conn->prepare('SELECT u.name, ac.year, a.appraisal_id, a.final_score FROM appraisal a JOIN user u ON a.user_id = u.user_id JOIN appraisal_cycle ac ON a.cycle_id = ac.cycle_id ORDER BY ac.year DESC, u.name');
            $stmt->execute();
        }
        $rowsAssoc = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rows = [];
        foreach ($rowsAssoc as $r) { $rows[] = [$r['name'], $r['year'], $r['appraisal_id'], $r['final_score']]; }
        outputCsv('appraisals.csv', ['Employee','Year','Appraisal ID','Final Score'], $rows);
        break;
    default:
        http_response_code(400);
        echo 'Unknown export type';
}


