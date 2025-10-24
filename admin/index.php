<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';

requireAdmin();

$pdo = getDB();
$admin = getCurrentAdmin();

// Get route
$route = $_GET['page'] ?? 'dashboard';
$allowed_routes = ['dashboard', 'users', 'donations', 'volunteers', 'partners', 'gallery', 'posts', 'settings', 'logout'];

if (!in_array($route, $allowed_routes, true)) {
    $route = 'dashboard';
}

// Handle logout
if ($route === 'logout') {
    session_unset();
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}

// Include page
$page_file = __DIR__ . "/{$route}.php";
if (!file_exists($page_file)) {
    $route = 'dashboard';
    $page_file = __DIR__ . "/dashboard.php";
}

include 'includes/header.php';
include $page_file;
include 'includes/footer.php';