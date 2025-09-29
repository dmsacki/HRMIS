<?php
require_once __DIR__ . '/config/config.php';

// Protect: only run by direct access and when not logged in
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain');
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // Create default roles if missing: 1-Admin, 2-Manager, 3-Employee
    $roles = [
        1 => 'Admin',
        2 => 'Manager',
        3 => 'Employee'
    ];
    $stmtRole = $conn->prepare('INSERT IGNORE INTO role (role_id, role_name) VALUES (:id, :name)');
    foreach ($roles as $id => $name) {
        $stmtRole->execute([':id' => $id, ':name' => $name]);
    }

    // Create a default department if missing
    $conn->exec("INSERT IGNORE INTO department (dept_id, dept_name) VALUES (1, 'Head Office')");

    // Create admin user if not exists
    $adminEmail = 'admin@mkombozi.tz';
    $stmt = $conn->prepare('SELECT user_id FROM user WHERE email = :email');
    $stmt->execute([':email' => $adminEmail]);
    if (!$stmt->fetch()) {
        $passwordHash = password_hash('Admin@123', PASSWORD_BCRYPT);
        $create = $conn->prepare('INSERT INTO user (name, email, password, role_id, dept_id) VALUES (:name, :email, :password, :role_id, :dept_id)');
        $create->execute([
            ':name' => 'System Administrator',
            ':email' => $adminEmail,
            ':password' => $passwordHash,
            ':role_id' => 1,
            ':dept_id' => 1
        ]);
    }

    $conn->commit();

    echo "Seed complete.\n";
    echo "Login with:\n";
    echo "Email: $adminEmail\n";
    echo "Password: Admin@123\n";
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo 'Seeding failed: ' . $e->getMessage();
}


