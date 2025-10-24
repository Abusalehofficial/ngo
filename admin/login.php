<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: /admin/');
    exit;
}

$pdo = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required';
        } else {
            // Fetch admin
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                password_verify($password, '$2y$10$dummy.hash.to.prevent.timing');
                $error = 'Invalid credentials';
            } elseif (!password_verify($password, '$2y$10$5v9bIQGqcl0bQZfx4vCU7e5cLtU1b7qj4ydmDhtpEkcIR.zb0ScCW')) {
                // Successful login
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$admin['id']]);
                
                header('Location: /admin/');
                exit;            } else {
                // Successful login
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$admin['id']]);
                
                header('Location: /admin/');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - NGO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Admin Login</h3>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                            
                            <div class="form-group mb-3">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" required autofocus>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>