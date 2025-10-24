<?php
// Generate secure unique ID
function generateUID($prefix = 'USR') {
    return $prefix . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));
}

// Sanitize output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    static $user = null;
    if ($user === null) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}

// Redirect helper
function redirect($path) {
    header("Location: /" . ltrim($path, '/'));
    exit;
}

// Flash message system
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Secure file upload
function uploadFile($file, $destination_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf'
    ];
    
    if (!isset($allowed_mimes[$mime])) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    $ext = $allowed_mimes[$mime];
    if (!in_array($ext, $allowed_types, true)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $filepath = $destination_dir . '/' . $filename;
    
    if (!is_dir($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Failed to save file'];
    }
    
    return ['success' => true, 'filename' => $filename, 'path' => $filepath];
}

// Get site setting
function getSetting($key, $default = '') {
    static $settings = [];
    
    if (empty($settings)) {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings[$key] ?? $default;
}

// Validate age range
function validateAge($age) {
    return is_numeric($age) && $age >= 18 && $age <= 50;
}

// CSRF token generation and validation
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}