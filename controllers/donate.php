<?php
$pdo = getDB();
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $amount = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
        $purpose = trim($_POST['purpose'] ?? '');
        $utr = trim($_POST['utr_number'] ?? '');
        $donor_name = trim($_POST['donor_name'] ?? '');
        $donor_email = filter_var($_POST['donor_email'] ?? '', FILTER_VALIDATE_EMAIL);
        $donor_phone = trim($_POST['donor_phone'] ?? '');
        
        // Validation
        if (!$amount || $amount <= 0) {
            $error = 'Invalid donation amount';
        } elseif (empty($utr) || strlen($utr) < 6) {
            $error = 'Invalid UTR number';
        } elseif (empty($_FILES['payment_proof']['name'])) {
            $error = 'Payment proof is required';
        } else {
            // Check UTR uniqueness
            $stmt = $pdo->prepare("SELECT id FROM donations WHERE utr_number = ? LIMIT 1");
            $stmt->execute([$utr]);
            
            if ($stmt->fetch()) {
                $error = 'This UTR number has already been used';
            } else {
                // Upload payment proof
                $upload = uploadFile($_FILES['payment_proof'], 'uploads/payments', ['jpg', 'jpeg', 'png', 'pdf']);
                
                if (!$upload['success']) {
                    $error = $upload['error'];
                } else {
                    // Insert donation
                    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO donations 
                        (user_id, donor_name, donor_email, donor_phone, amount, purpose, payment_proof, utr_number, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    
                    $stmt->execute([
                        $user_id,
                        $donor_name ?: (isLoggedIn() ? getCurrentUser()['name'] : 'Anonymous'),
                        $donor_email,
                        $donor_phone,
                        $amount,
                        $purpose,
                        $upload['filename'],
                        $utr
                    ]);
                    
                    $success = true;
                    setFlash('success', 'Thank you! Your donation is being verified.');
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <h1>Make a Donation</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <h4>Thank You for Your Generosity!</h4>
            <p>Your donation has been received and is being verified. You will be notified once approved.</p>
            <?php if (isLoggedIn()): ?>
                <p><a href="/dashboard">View your donations in dashboard</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <form method="POST" enctype="multipart/form-data" id="donationForm">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                    
                    <div class="form-group mb-3">
                        <label>Donation Amount (₹) *</label>
                        <input type="number" name="amount" class="form-control" required min="1" step="0.01">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>Purpose of Donation</label>
                        <select name="purpose" class="form-control">
                            <option value="General Fund">General Fund</option>
                            <option value="Education">Education</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Emergency Relief">Emergency Relief</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <?php if (!isLoggedIn()): ?>
                        <div class="form-group mb-3">
                            <label>Your Name</label>
                            <input type="text" name="donor_name" class="form-control" placeholder="Optional">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Email</label>
                            <input type="email" name="donor_email" class="form-control" placeholder="Optional">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Phone</label>
                            <input type="text" name="donor_phone" class="form-control" placeholder="Optional">
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <h5>Payment Details</h5>
                    <p class="text-muted">Scan QR code and complete payment, then enter details below:</p>
                    
                    <div class="qr-code text-center mb-3">
                        <img src="/<?= e(getSetting('payment_qr_image', 'uploads/qr-code.png')) ?>" 
                             alt="Payment QR Code" style="max-width: 250px;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>UTR Number *</label>
                        <input type="text" name="utr_number" class="form-control" required 
                               placeholder="Enter 12-digit UTR/Transaction ID">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>Upload Payment Proof * (Screenshot/PDF)</label>
                        <input type="file" name="payment_proof" class="form-control" required 
                               accept="image/jpeg,image/png,application/pdf">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">Submit Donation</button>
                </form>
            </div>
            
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5>Why Donate?</h5>
                        <p>Your contribution helps us continue our mission to serve those in need.</p>
                        <ul>
                            <li>100% transparent fund usage</li>
                            <li>Regular updates on projects</li>
                            <li>Tax exemption certificates</li>
                            <li>Direct impact on communities</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>