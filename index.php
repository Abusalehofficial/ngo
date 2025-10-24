<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get route from URL
$route = $_GET['route'] ?? 'home';
$route = trim($route, '/');

// Whitelist allowed routes (prevents directory traversal)
$allowed_routes = [
    'home', 'donate', 'volunteer', 'partner', 'register', 
    'login', 'logout', 'dashboard', 'profile', 'gallery', 
    'news', 'blog', 'about', 'contact'
];

// Default to home if route not allowed
if (!in_array($route, $allowed_routes, true)) {
    $route = 'home';
}

// Map route to controller
$controller_file = __DIR__ . "/controllers/{$route}.php";

if (file_exists($controller_file)) {
    require_once $controller_file;
} else {
    http_response_code(404);
    require_once 'includes/header.php';
    echo '<div class="container"><h1>404 - Page Not Found</h1></div>';
    require_once 'includes/footer.php';
}