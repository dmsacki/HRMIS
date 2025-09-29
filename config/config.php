<?php
// Application Configuration
define('APP_NAME', 'Mkombozi HRMIS');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/HRMIS/');

// Session Configuration
session_start();

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

// Include database connection
require_once 'database.php';

// Helper functions
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function hasRole($role_id) {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == $role_id;
}

function requireRole($role_id) {
    if (!hasRole($role_id)) {
        redirect('dashboard.php');
    }
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}
?>
