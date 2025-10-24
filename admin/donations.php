<?php
// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $donation_id = (int)($_POST['donation_id'] ?? 0);
    
    if ($action === 'approve' && $donation_id) {
        $stmt = $pdo->prepare("
            UPDATE donations 
            SET status = 'approved', approved_by = ?, approved_at = NOW() 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$admin['id'], $donation_id]);
        setFlash('success', 'Donation approved');
        
    } elseif ($action === 'reject' && $donation_id) {
        $stmt = $pdo->prepare("UPDATE donations SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$donation_id]);
        setFlash('success', 'Donation rejected');
    }
    
    adminRedirect('?page=donations');
}

// Filters
$status_filter = $_GET['status'] ?? 'pending';

$where = '1=1';
$params = [];

if ($status_filter !== 'all') {
    $where = 'status = ?';
    $params[] = $status_filter;
}

// Pagination
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Fetch donations
$stmt = $pdo->prepare("
    SELECT d.*, u.name as user_name, u.uid as user_uid 
    FROM donations d 
    LEFT JOIN users u ON d.user_id = u.id 
    WHERE $where 
    ORDER BY d.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$per_page, $offset]));
$donations = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Donations Management</h2>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="btn-group">
            <a href="?page=donations&status=pending" class="btn btn-<?= $status_filter === 'pending' ? 'primary' : 'outline-primary' ?>">Pending</a>
            <a href="?page=donations&status=approved" class="btn btn-<?= $status_filter === 'approved' ? 'primary' : 'outline-primary' ?>">Approved</a>
            <a href="?page=donations&status=rejected" class="btn btn-<?= $status_filter === 'rejected' ? 'primary' : 'outline-primary' ?>">Rejected</a>
            <a href="?page=donations&status=all" class="btn btn-<?= $status_filter === 'all' ? 'primary' : 'outline-primary' ?>">All</a>
        </div>
    </div>
</div>

<!-- Donations Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Donor</th>
                        <th>Amount</th>
                        <th>Purpose</th>
                        <th>UTR</th>
                        <th>Proof</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donations as $donation): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($donation['created_at'])) ?></td>
                            <td>
                                <?php if ($donation['user_name']): ?>
                                    <strong><?= e($donation['user_name']) ?></strong><br>
                                    <small class="text-muted">UID: <?= e($donation['user_uid']) ?></small>
                                <?php else: ?>
                                    <?= e($donation['donor_name'] ?: 'Anonymous') ?><br>
                                    <?php if ($donation['donor_phone']): ?>
                                        <small class="text-muted"><?= e($donation['donor_phone']) ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><strong>₹<?= number_format($donation['amount'], 2) ?></strong></td>
                            <td><?= e($donation['purpose']) ?></td>
                            <td><code><?= e($donation['utr_number']) ?></code></td>
                            <td>
                                <a href="/uploads/payments/<?= e($donation['payment_proof']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $donation['status'] === 'approved' ? 'success' : 
                                    ($donation['status'] === 'pending' ? 'warning' : 'danger') 
                                ?>">
                                    <?= ucfirst(e($donation['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($donation['status'] === 'pending'): ?>
                                    <div class="btn-group btn-group-sm">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="donation_id" value="<?= $donation['id'] ?>">
                                            <button type="submit" class="btn btn-success" onclick="return confirm('Approve this donation?')">Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="donation_id" value="<?= $donation['id'] ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this donation?')">Reject</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <small class="text-muted">No action</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>