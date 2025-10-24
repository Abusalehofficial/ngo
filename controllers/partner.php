<?php
$pdo = getDB();
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone = trim($_POST['phone'] ?? '');
        $organization = trim($_POST['organization'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $city = trim($_POST['city'] ?? '');
        
        // Validation
        if (empty($name) || strlen($name) < 3) {
            $error = 'Name must be at least 3 characters';
        } elseif (empty($phone) || strlen($phone) < 10) {
            $error = 'Valid phone number required';
        } elseif (empty($organization)) {
            $error = 'Organization name is required';
        } else {
            // Check duplicate phone
            $stmt = $pdo->prepare("SELECT id FROM partners WHERE phone = ? LIMIT 1");
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                $error = 'This phone number is already registered as partner';
            } else {
                // Generate UID
                $uid = generateUID('PTR');
                
                // Insert partner
                $stmt = $pdo->prepare("
                    INSERT INTO partners 
                    (uid, name, email, phone, organization, address, country, state, city, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                
                $stmt->execute([
                    $uid, $name, $email, $phone, $organization, 
                    $address, $country, $state, $city
                ]);
                
                $success = true;
                setFlash('success', 'Thank you! Your partnership request is under review.');
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1>Become a Partner</h1>
            <p class="lead">Partner with us to create lasting impact together.</p>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h4>Partnership Request Received!</h4>
                    <p>Thank you for your interest. Our team will review your request and contact you soon.</p>
                </div>
            <?php else: ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                            
                            <div class="form-group mb-3">
                                <label>Contact Person Name *</label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?= e($_POST['name'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label>Organization Name *</label>
                                <input type="text" name="organization" class="form-control" required 
                                       value="<?= e($_POST['organization'] ?? '') ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Email *</label>
                                        <input type="email" name="email" class="form-control" required 
                                               value="<?= e($_POST['email'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Phone Number *</label>
                                        <input type="tel" name="phone" class="form-control" required 
                                               value="<?= e($_POST['phone'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label>Address *</label>
                                <textarea name="address" class="form-control" required rows="2"><?= e($_POST['address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label>Country *</label>
                                        <input type="text" name="country" class="form-control" required 
                                               value="<?= e($_POST['country'] ?? 'India') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label>State *</label>
                                        <input type="text" name="state" class="form-control" required 
                                               value="<?= e($_POST['state'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label>City *</label>
                                        <input type="text" name="city" class="form-control" required 
                                               value="<?= e($_POST['city'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-info btn-lg w-100">
                                Submit Partnership Request
                            </button>
                        </form>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>