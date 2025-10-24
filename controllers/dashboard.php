<?php
// Require authentication
if (!isLoggedIn()) {
    setFlash('error', 'Please login to access dashboard');
    redirect('/login');
}

$pdo = getDB();
$user = getCurrentUser();

// Fetch user's activities (admin-inserted)
$stmt = $pdo->prepare("
    SELECT activity_title, activity_description, activity_date, created_at 
    FROM user_activities 
    WHERE user_id = ? 
    ORDER BY activity_date DESC, created_at DESC 
    LIMIT 20
");
$stmt->execute([$user['id']]);
$activities = $stmt->fetchAll();

// Fetch user's donations
$stmt = $pdo->prepare("
    SELECT amount, purpose, status, created_at, approved_at 
    FROM donations 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$user['id']]);
$donations = $stmt->fetchAll();

// Fetch nominee details
$stmt = $pdo->prepare("SELECT * FROM nominees WHERE user_id = ? LIMIT 1");
$stmt->execute([$user['id']]);
$nominee = $stmt->fetch();

// Calculate membership expiry and renewal info
$renewal_needed = false;
$days_until_expiry = null;

if ($user['membership_expiry']) {
    $expiry_date = new DateTime($user['membership_expiry']);
    $today = new DateTime();
    $interval = $today->diff($expiry_date);
    $days_until_expiry = (int)$interval->format('%R%a');
    
    if ($days_until_expiry <= 30) {
        $renewal_needed = true;
    }
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    
    <?php 
    $flash = getFlash();
    if ($flash): 
    ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <?php if ($user['profile_image']): ?>
                        <img src="/uploads/profiles/<?= e($user['profile_image']) ?>" 
                             alt="Profile" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3" 
                             style="width: 100px; height: 100px; font-size: 40px;">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <h5><?= e($user['name']) ?></h5>
                    <p class="text-muted small mb-1">UID: <?= e($user['uid']) ?></p>
                    <span class="badge bg-<?= $user['membership_type'] === 'premium' ? 'success' : 'primary' ?>">
                        <?= ucfirst(e($user['membership_type'])) ?> Member
                    </span>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#activities" class="list-group-item list-group-item-action">
                        My Activities
                    </a>
                    <a href="#donations" class="list-group-item list-group-item-action">
                        My Donations
                    </a>
                    <a href="#profile" class="list-group-item list-group-item-action">
                        Edit Profile
                    </a>
                    <a href="#nominee" class="list-group-item list-group-item-action">
                        Nominee Details
                    </a>
                    <a href="#membership" class="list-group-item list-group-item-action">
                        Membership Status
                    </a>
                    <a href="/logout" class="list-group-item list-group-item-action text-danger">
                        Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            
            <!-- Membership Alert -->
            <?php if ($renewal_needed): ?>
                <div class="alert alert-warning">
                    <h5>⚠️ Membership Renewal Required</h5>
                    <p class="mb-1">
                        Your membership expires in <strong><?= abs($days_until_expiry) ?> days</strong> 
                        on <strong><?= date('d M Y', strtotime($user['membership_expiry'])) ?></strong>
                    </p>
                    <small>Please visit our office to renew: <strong><?= e(getSetting('office_address', 'NGO Office')) ?></strong></small>
                </div>
            <?php elseif ($user['membership_status'] === 'expired'): ?>
                <div class="alert alert-danger">
                    <h5>❌ Membership Expired</h5>
                    <p class="mb-1">Your membership expired on <strong><?= date('d M Y', strtotime($user['membership_expiry'])) ?></strong></p>
                    <small>Please visit our office to renew: <strong><?= e(getSetting('office_address', 'NGO Office')) ?></strong></small>
                </div>
            <?php endif; ?>
            
            <!-- Activities Section -->
            <div id="activities" class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">My Activities</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($activities)): ?>
                        <p class="text-muted">No activities recorded yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($activities as $activity): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= e($activity['activity_title']) ?></h6>
                                            <p class="mb-1 text-muted small"><?= e($activity['activity_description']) ?></p>
                                        </div>
                                        <small class="text-muted">
                                            <?= $activity['activity_date'] ? date('d M Y', strtotime($activity['activity_date'])) : 'N/A' ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Donations Section -->
            <div id="donations" class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">My Donations</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($donations)): ?>
                        <p class="text-muted">No donations yet. <a href="/donate">Make your first donation</a></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($donations as $donation): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($donation['created_at'])) ?></td>
                                            <td>₹<?= number_format($donation['amount'], 2) ?></td>
                                            <td><?= e($donation['purpose']) ?></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'pending' => 'warning',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger'
                                                ];
                                                ?>
                                                <span class="badge bg-<?= $status_class[$donation['status']] ?>">
                                                    <?= ucfirst(e($donation['status'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Profile Edit Section -->
            <div id="profile" class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Edit Profile</h4>
                </div>
                <div class="card-body">
                    <form action="/profile" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Full Name</label>
                                    <input type="text" name="name" class="form-control" 
                                           value="<?= e($user['name']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Phone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?= e($user['phone']) ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>WhatsApp</label>
                                    <input type="tel" name="whatsapp" class="form-control" 
                                           value="<?= e($user['whatsapp']) ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Profile Image</label>
                                    <input type="file" name="profile_image" class="form-control" 
                                           accept="image/jpeg,image/png">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= e($user['address']) ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
            
            <!-- Nominee Details Section -->
            <div id="nominee" class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Nominee Details</h4>
                </div>
                <div class="card-body">
                    <form action="/profile" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                        <input type="hidden" name="action" value="update_nominee">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Nominee Name</label>
                                    <input type="text" name="nominee_name" class="form-control" 
                                           value="<?= e($nominee['nominee_name'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Relationship</label>
                                    <input type="text" name="relationship" class="form-control" 
                                           value="<?= e($nominee['relationship'] ?? '') ?>" 
                                           placeholder="e.g., Spouse, Child, Parent">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Nominee Phone</label>
                            <input type="tel" name="nominee_phone" class="form-control" 
                                   value="<?= e($nominee['phone'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Nominee Address</label>
                            <textarea name="nominee_address" class="form-control" rows="2"><?= e($nominee['address'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Nominee Details</button>
                    </form>
                </div>
            </div>
            
            <!-- Membership Status Section -->
            <div id="membership" class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Membership Status</h4>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Member ID:</th>
                            <td><?= e($user['uid']) ?></td>
                        </tr>
                        <tr>
                            <th>Membership Type:</th>
                            <td>
                                <span class="badge bg-<?= $user['membership_type'] === 'premium' ? 'success' : 'primary' ?>">
                                    <?= ucfirst(e($user['membership_type'])) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-<?= $user['membership_status'] === 'active' ? 'success' : 'warning' ?>">
                                    <?= ucfirst(e($user['membership_status'])) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Membership Price:</th>
                            <td>₹<?= number_format($user['membership_price'], 2) ?></td>
                        </tr>
                        <?php if ($user['membership_start']): ?>
                            <tr>
                                <th>Start Date:</th>
                                <td><?= date('d M Y', strtotime($user['membership_start'])) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($user['membership_expiry']): ?>
                            <tr>
                                <th>Expiry Date:</th>
                                <td>
                                    <?= date('d M Y', strtotime($user['membership_expiry'])) ?>
                                    <?php if ($days_until_expiry !== null): ?>
                                        <small class="text-muted">
                                            (<?= $days_until_expiry > 0 ? "$days_until_expiry days remaining" : abs($days_until_expiry) . " days overdue" ?>)
                                        </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Renewal Process:</th>
                            <td>Visit our office to renew your membership</td>
                        </tr>
                        <tr>
                            <th>Office Address:</th>
                            <td><?= e(getSetting('office_address', 'Contact admin for details')) ?></td>
                        </tr>
                        <tr>
                            <th>Renewal Price:</th>
                            <td>
                                <?php
                                $renewal_price = $user['membership_type'] === 'basic' 
                                    ? getSetting('basic_membership_price', 1000) 
                                    : getSetting('premium_membership_price', 2000);
                                ?>
                                ₹<?= number_format($renewal_price, 2) ?>
                            </td>
                        </tr>
                    </table>
                    
                    <?php if ($renewal_needed || $user['membership_status'] === 'expired'): ?>
                        <div class="alert alert-info mt-3">
                            <strong>Note:</strong> Membership renewal must be done at our office. 
                            Please bring your member ID (<?= e($user['uid']) ?>) and payment.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>