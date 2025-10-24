<?php
// Admin authentication helper
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function getCurrentAdmin() {
    if (!isAdminLoggedIn()) return null;
    
    static $admin = null;
    if ($admin === null) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
    }
    return $admin;
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        setFlash('error', 'Please login to access admin panel');
        header('Location: /admin/login.php');
        exit;
    }
}

function adminRedirect($path) {
    header("Location: /admin/" . ltrim($path, '/'));
    exit;
}