<?php
// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/dashboard');
}

$pdo = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Email and password are required';
        } else {
            // Fetch user by email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Use constant-time comparison to prevent timing attacks
                password_verify($password, '$2y$10$dummy.hash.to.prevent.timing');
                $error = 'Invalid email or password';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $error = 'Invalid email or password';
            } elseif ($user['membership_status'] === 'rejected') {
                $error = 'Your membership application was rejected. Please contact support.';
            } elseif ($user['membership_status'] === 'pending') {
                $error = 'Your account is pending approval. Please wait for admin verification.';
            } elseif ($user['membership_status'] === 'expired') {
                $error = 'Your membership has expired. Please renew to continue.';
            } else {
                // Successful login
                session_regenerate_id(true); // Prevent session fixation
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_uid'] = $user['uid'];
                $_SESSION['user_name'] = $user['name'];
                
                setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect('/dashboard');
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Member Login</h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>
                    
                    <?php 
                    $flash = getFlash();
                    if ($flash && $flash['type'] === 'success'): 
                    ?>
                        <div class="alert alert-success"><?= e($flash['message']) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                        
                        <div class="form-group mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" required 
                                   value="<?= e($_POST['email'] ?? '') ?>" autofocus>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            Login
                        </button>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? <a href="/register">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>