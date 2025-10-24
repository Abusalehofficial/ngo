<?php
// Handle approval/rejection and group assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $partner_id = (int)($_POST['partner_id'] ?? 0);
    
    if ($action === 'approve' && $partner_id) {
        $group_name = trim($_POST['group_name'] ?? '');
        
        $stmt = $pdo->prepare("
            UPDATE partners 
            SET status = 'approved', group_name = ?, approved_by = ?, approved_at = NOW() 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$group_name, $admin['id'], $partner_id]);
        setFlash('success', 'Partner approved');
        
    } elseif ($action === 'reject' && $partner_id) {
        $stmt = $pdo->prepare("UPDATE partners SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$partner_id]);
        setFlash('success', 'Partner rejected');
        
    } elseif ($action === 'edit' && $partner_id) {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $organization = trim($_POST['organization'] ?? '');
        $group_name = trim($_POST['group_name'] ?? '');
        
        if ($name && $phone && $organization) {
            $stmt = $pdo->prepare("
                UPDATE partners 
                SET name = ?, phone = ?, email = ?, organization = ?, group_name = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $phone, $email, $organization, $group_name, $partner_id]);
            setFlash('success', 'Partner updated');
        }
    }
    
    adminRedirect('?page=partners');
}

// Filters
$status_filter = $_GET['status'] ?? 'pending';
$search = trim($_GET['search'] ?? '');

$where = ['1=1'];
$params = [];

if ($status_filter !== 'all') {
    $where[] = 'status = ?';
    $params[] = $status_filter;
}

if ($search) {
    $where[] = '(name LIKE ? OR email LIKE ? OR uid LIKE ? OR phone LIKE ? OR organization LIKE ?)';
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
}

$where_clause = implode(' AND ', $where);

// Pagination
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Fetch partners
$stmt = $pdo->prepare("
    SELECT * FROM partners 
    WHERE $where_clause 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$per_page, $offset]));
$partners = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Partners Management</h2>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="partners">
            
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= e($search) ?>">
            </div>
            
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            
            <div class="col-md-2">
                <a href="?page=partners" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Partners Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Contact Person</th>
                        <th>Organization</th>
                        <th>Contact Info</th>
                        <th>Location</th>
                        <th>Group</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partners as $partner): ?>
                        <tr>
                            <td><?= e($partner['uid']) ?></td>
                            <td><?= e($partner['name']) ?></td>
                            <td><strong><?= e($partner['organization']) ?></strong></td>
                            <td>
                                <?= e($partner['email']) ?><br>
                                <small class="text-muted"><?= e($partner['phone']) ?></small>
                            </td>
                            <td>
                                <?= e($partner['city']) ?>, <?= e($partner['state']) ?><br>
                                <small class="text-muted"><?= e($partner['country']) ?></small>
                            </td>
                            <td>
                                <?php if ($partner['group_name']): ?>
                                    <span class="badge bg-info"><?= e($partner['group_name']) ?></span>
                                <?php else: ?>
                                    <small class="text-muted">Not assigned</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $partner['status'] === 'approved' ? 'success' : 
                                    ($partner['status'] === 'pending' ? 'warning' : 'danger') 
                                ?>">
                                    <?= ucfirst(e($partner['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($partner['status'] === 'pending'): ?>
                                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?= $partner['id'] ?>">
                                            Approve
                                        </button>
                                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $partner['id'] ?>">
                                            Reject
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $partner['id'] ?>">
                                            Edit
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Approve Modal -->
                                <div class="modal fade" id="approveModal<?= $partner['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Approve Partner</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                                                    
                                                    <p>Approve <strong><?= e($partner['organization']) ?></strong> as partner?</p>
                                                    
                                                    <div class="mb-3">
                                                        <label>Assign to Group (Optional)</label>
                                                        <input type="text" name="group_name" class="form-control" 
                                                               placeholder="e.g., Corporate Partners, NGO Partners, etc.">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Approve</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal<?= $partner['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reject Partner</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Reject <strong><?= e($partner['organization']) ?></strong>?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?= $partner['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Partner</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label>Contact Person</label>
                                                        <input type="text" name="name" class="form-control" value="<?= e($partner['name']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Organization</label>
                                                        <input type="text" name="organization" class="form-control" value="<?= e($partner['organization']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Phone</label>
                                                        <input type="tel" name="phone" class="form-control" value="<?= e($partner['phone']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Email</label>
                                                        <input type="email" name="email" class="form-control" value="<?= e($partner['email']) ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Group</label>
                                                        <input type="text" name="group_name" class="form-control" value="<?= e($partner['group_name']) ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>