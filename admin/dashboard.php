<?php
// Fetch statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $stmt->fetchColumn();

// Pending users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE membership_status = 'pending'");
$stats['pending_users'] = $stmt->fetchColumn();

// Total donations
$stmt = $pdo->query("SELECT COUNT(*), SUM(amount) FROM donations WHERE status = 'approved'");
$row = $stmt->fetch();
$stats['total_donations'] = $row[0];
$stats['total_donation_amount'] = $row[1] ?? 0;

// Pending donations
$stmt = $pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'pending'");
$stats['pending_donations'] = $stmt->fetchColumn();

// Volunteers
$stmt = $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'pending'");
$stats['pending_volunteers'] = $stmt->fetchColumn();

// Partners
$stmt = $pdo->query("SELECT COUNT(*) FROM partners WHERE status = 'pending'");
$stats['pending_partners'] = $stmt->fetchColumn();
?>

<h2>Dashboard</h2>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <h2><?= number_format($stats['total_users']) ?></h2>
                <small><?= $stats['pending_users'] ?> pending approval</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Donations</h5>
                <h2>₹<?= number_format($stats['total_donation_amount'], 0) ?></h2>
                <small><?= $stats['pending_donations'] ?> pending approval</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <h5 class="card-title">Volunteers</h5>
                <h2><?= $stats['pending_volunteers'] ?></h2>
                <small>Pending approval</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <h5 class="card-title">Partners</h5>
                <h2><?= $stats['pending_partners'] ?></h2>
                <small>Pending approval</small>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent User Registrations</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->query("
                    SELECT uid, name, email, membership_type, membership_status, created_at 
                    FROM users 
                    ORDER BY created_at DESC 
                    LIMIT 5
                ");
                $recent_users = $stmt->fetchAll();
                ?>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?= e($user['name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= ucfirst(e($user['membership_type'])) ?></span></td>
                                    <td><span class="badge bg-<?= $user['membership_status'] === 'active' ? 'success' : 'warning' ?>"><?= ucfirst(e($user['membership_status'])) ?></span></td>
                                    <td><?= date('d M', strtotime($user['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Donations</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->query("
                    SELECT d.donor_name, d.amount, d.purpose, d.status, d.created_at, u.name as user_name
                    FROM donations d
                    LEFT JOIN users u ON d.user_id = u.id
                    ORDER BY d.created_at DESC 
                    LIMIT 5
                ");
                $recent_donations = $stmt->fetchAll();
                ?>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Donor</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_donations as $donation): ?>
                                <tr>
                                    <td><?= e($donation['user_name'] ?: $donation['donor_name'] ?: 'Anonymous') ?></td>
                                    <td>₹<?= number_format($donation['amount'], 0) ?></td>
                                    <td><span class="badge bg-<?= $donation['status'] === 'approved' ? 'success' : 'warning' ?>"><?= ucfirst(e($donation['status'])) ?></span></td>
                                    <td><?= date('d M', strtotime($donation['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>