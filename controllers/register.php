<?php
$pdo = getDB();
$error = '';
$step = $_POST['step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        if ($step == 1) {
            // Step 1: Basic details validation
            $name = trim($_POST['name'] ?? '');
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $phone = trim($_POST['phone'] ?? '');
            $whatsapp = trim($_POST['whatsapp'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $address = trim($_POST['address'] ?? '');
            $country = trim($_POST['country'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $age = filter_var($_POST['age'] ?? 0, FILTER_VALIDATE_INT);
            $membership_type = $_POST['membership_type'] ?? '';
            
            // Validation
            if (empty($name) || strlen($name) < 3) {
                $error = 'Name must be at least 3 characters';
            } elseif (!$email) {
                $error = 'Valid email is required';
            } elseif (empty($phone) || strlen($phone) < 10) {
                $error = 'Valid phone number required';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match';
            } elseif (!validateAge($age)) {
                $error = 'Age must be between 18-50';
            } elseif (!in_array($membership_type, ['basic', 'premium'], true)) {
                $error = 'Invalid membership type';
            } else {
                // Check email uniqueness
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already registered';
                } else {
                    // Check phone uniqueness
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
                    $stmt->execute([$phone]);
                    if ($stmt->fetch()) {
                        $error = 'Phone number already registered';
                    } else {
                        // Upload profile image if provided
                        $profile_image = null;
                        if (!empty($_FILES['profile_image']['name'])) {
                            $upload = uploadFile($_FILES['profile_image'], 'uploads/profiles', ['jpg', 'jpeg', 'png']);
                            if ($upload['success']) {
                                $profile_image = $upload['filename'];
                            }
                        }
                        
                        // Store in session for step 2
                        $_SESSION['registration_data'] = [
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'whatsapp' => $whatsapp ?: $phone,
                            'password' => $password,
                            'address' => $address,
                            'country' => $country,
                            'state' => $state,
                            'city' => $city,
                            'age' => $age,
                            'membership_type' => $membership_type,
                            'profile_image' => $profile_image
                        ];
                        
                        $step = 2;
                    }
                }
            }
            
        } elseif ($step == 2) {
            // Step 2: Payment verification
            if (!isset($_SESSION['registration_data'])) {
                redirect('/register');
            }
            
            $utr = trim($_POST['utr_number'] ?? '');
            
            if (empty($utr) || strlen($utr) < 6) {
                $error = 'Invalid UTR number';
            } elseif (empty($_FILES['payment_proof']['name'])) {
                $error = 'Payment proof is required';
            } else {
                // Upload payment proof
                $upload = uploadFile($_FILES['payment_proof'], 'uploads/payments', ['jpg', 'jpeg', 'png', 'pdf']);
                
                if (!$upload['success']) {
                    $error = $upload['error'];
                } else {
                    $data = $_SESSION['registration_data'];
                    
                    // Determine membership price
                    $prices = [
                        'basic' => floatval(getSetting('basic_membership_price', 1000)),
                        'premium' => floatval(getSetting('premium_membership_price', 2000))
                    ];
                    $membership_price = $prices[$data['membership_type']];
                    
                    // Generate unique UID
                    $uid = generateUID('USR');
                    
                    // Insert user (idempotent check on email/phone already done in step 1)
                    $stmt = $pdo->prepare("
                        INSERT INTO users 
                        (uid, name, email, phone, whatsapp, password_hash, profile_image, address, 
                         country, state, city, age, membership_type, membership_price, 
                         membership_status, payment_proof, utr_number)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
                    ");
                    
                    $stmt->execute([
                        $uid,
                        $data['name'],
                        $data['email'],
                        $data['phone'],
                        $data['whatsapp'],
                        password_hash($data['password'], PASSWORD_DEFAULT),
                        $data['profile_image'],
                        $data['address'],
                        $data['country'],
                        $data['state'],
                        $data['city'],
                        $data['age'],
                        $data['membership_type'],
                        $membership_price,
                        $upload['filename'],
                        $utr
                    ]);
                    
                    unset($_SESSION['registration_data']);
                    setFlash('success', 'Registration successful! Your account is pending approval.');
                    redirect('/login');
                }
            }
        }
    }
}

// Get membership prices
$basic_price = getSetting('basic_membership_price', 1000);
$premium_price = getSetting('premium_membership_price', 2000);

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Member Registration - Step <?= (int)$step ?> of 2</h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($step == 1): ?>
                        <!-- Step 1: Personal Details -->
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                            <input type="hidden" name="step" value="1">
                            
                            <div class="form-group mb-3">
                                <label>Full Name *</label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?= e($_POST['name'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label>Email Address *</label>
                                <input type="email" name="email" class="form-control" required 
                                       value="<?= e($_POST['email'] ?? '') ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Phone Number *</label>
                                        <input type="tel" name="phone" class="form-control" required 
                                               value="<?= e($_POST['phone'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>WhatsApp Number</label>
                                        <input type="tel" name="whatsapp" class="form-control" 
                                               value="<?= e($_POST['whatsapp'] ?? '') ?>" 
                                               placeholder="Leave blank if same as phone">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Password *</label>
                                        <input type="password" name="password" class="form-control" required 
                                               minlength="8">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Confirm Password *</label>
                                        <input type="password" name="confirm_password" class="form-control" required 
                                               minlength="8">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label>Profile Image (Optional)</label>
                                <input type="file" name="profile_image" class="form-control" 
                                       accept="image/jpeg,image/png">
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
                            
                            <div class="form-group mb-3">
                                <label>Age * (18-50)</label>
                                <input type="number" name="age" class="form-control" required 
                                       min="18" max="50" value="<?= e($_POST['age'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group mb-4">
                                <label>Membership Type *</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card membership-card">
                                            <div class="card-body text-center">
                                                <input type="radio" name="membership_type" value="basic" 
                                                       id="basic" required class="membership-radio">
                                                <label for="basic" class="w-100">
                                                    <h5>Basic Membership</h5>
                                                    <h3 class="text-primary">₹<?= e($basic_price) ?></h3>
                                                    <p class="text-muted">Standard benefits</p>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card membership-card">
                                            <div class="card-body text-center">
                                                <input type="radio" name="membership_type" value="premium" 
                                                       id="premium" required class="membership-radio">
                                                <label for="premium" class="w-100">
                                                    <h5>Premium Membership</h5>
                                                    <h3 class="text-success">₹<?= e($premium_price) ?></h3>
                                                    <p class="text-muted">Enhanced benefits</p>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                Continue to Payment
                            </button>
                        </form>
                        
                    <?php elseif ($step == 2 && isset($_SESSION['registration_data'])): ?>
                        <!-- Step 2: Payment -->
                        <?php 
                        $data = $_SESSION['registration_data'];
                        $amount = ($data['membership_type'] === 'basic') ? $basic_price : $premium_price;
                        ?>
                        
                        <div class="alert alert-info">
                            <strong>Membership:</strong> <?= ucfirst(e($data['membership_type'])) ?> - ₹<?= e($amount) ?><br>
                            <strong>Name:</strong> <?= e($data['name']) ?>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                            <input type="hidden" name="step" value="2">
                            
                            <div class="text-center mb-4">
                                <h5>Scan QR Code to Pay ₹<?= e($amount) ?></h5>
                                <img src="/<?= e(getSetting('payment_qr_image', 'uploads/qr-code.png')) ?>" 
                                     alt="Payment QR" style="max-width: 300px;">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label>UTR Number / Transaction ID *</label>
                                <input type="text" name="utr_number" class="form-control" required 
                                       placeholder="Enter 12-digit UTR">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label>Upload Payment Screenshot *</label>
                                <input type="file" name="payment_proof" class="form-control" required 
                                       accept="image/jpeg,image/png,application/pdf">
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                Complete Registration
                            </button>
                        </form>
                        
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <div class="text-center mt-3">
                <p>Already have an account? <a href="/login">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>