<?php
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare('SELECT user_id, name, email, password, role_id, dept_id FROM user WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role_id'] = (int)$user['role_id'];
        $_SESSION['dept_id'] = (int)$user['dept_id'];

        redirect('dashboard.php');
    } else {
        redirect('index.php?error=1');
    }
} else {
    redirect('index.php');
}


