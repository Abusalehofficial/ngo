<?php
// Handle approval/rejection and group assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $volunteer_id = (int)($_POST['volunteer_id'] ?? 0);
    
    if ($action === 'approve' && $volunteer_id) {
        $group_name = trim($_POST['group_name'] ?? '');
        
        $stmt = $pdo->prepare("
            UPDATE volunteers 
            SET status = 'approved', group_name = ?, approved_by = ?, approved_at = NOW() 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$group_name, $admin['id'], $volunteer_id]);
        setFlash('success', 'Volunteer approved');
        
    } elseif ($action === 'reject' && $volunteer_id) {
        $stmt = $pdo->prepare("UPDATE volunteers SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$volunteer_id]);
        setFlash('success', 'Volunteer rejected');
        
    } elseif ($action === 'edit' && $volunteer_id) {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $group_name = trim($_POST['group_name'] ?? '');
        
        if ($name && $phone) {
            $stmt = $pdo->prepare("
                UPDATE volunteers 
                SET name = ?, phone = ?, email = ?, group_name = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $phone, $email, $group_name, $volunteer_id]);
            setFlash('success', 'Volunteer updated');
        }
    }
    
    adminRedirect('?page=volunteers');
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
    $where[] = '(name LIKE ? OR email LIKE ? OR uid LIKE ? OR phone LIKE ?)';
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$where_clause = implode(' AND ', $where);

// Pagination
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Count total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM volunteers WHERE $where_clause");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// Fetch volunteers
$stmt = $pdo->prepare("
    SELECT * FROM volunteers 
    WHERE $where_clause 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$per_page, $offset]));
$volunteers = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Volunteers Management</h2>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="volunteers">
            
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
                <a href="?page=volunteers" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Volunteers Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Age</th>
                        <th>Group</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($volunteers as $volunteer): ?>
                        <tr>
                            <td><?= e($volunteer['uid']) ?></td>
                            <td><?= e($volunteer['name']) ?></td>
                            <td>
                                <?= e($volunteer['email'] ?: 'N/A') ?><br>
                                <small class="text-muted"><?= e($volunteer['phone']) ?></small>
                            </td>
                            <td>
                                <?= e($volunteer['city']) ?>, <?= e($volunteer['state']) ?><br>
                                <small class="text-muted"><?= e($volunteer['country']) ?></small>
                            </td>
                            <td><?= e($volunteer['age']) ?></td>
                            <td>
                                <?php if ($volunteer['group_name']): ?>
                                    <span class="badge bg-info"><?= e($volunteer['group_name']) ?></span>
                                <?php else: ?>
                                    <small class="text-muted">Not assigned</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $volunteer['status'] === 'approved' ? 'success' : 
                                    ($volunteer['status'] === 'pending' ? 'warning' : 'danger') 
                                ?>">
                                    <?= ucfirst(e($volunteer['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($volunteer['status'] === 'pending'): ?>
                                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?= $volunteer['id'] ?>">
                                            Approve
                                        </button>
                                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $volunteer['id'] ?>">
                                            Reject
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $volunteer['id'] ?>">
                                            Edit
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Approve Modal -->
                                <div class="modal fade" id="approveModal<?= $volunteer['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Approve Volunteer</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="volunteer_id" value="<?= $volunteer['id'] ?>">
                                                    
                                                    <p>Approve <strong><?= e($volunteer['name']) ?></strong> as volunteer?</p>
                                                    
                                                    <div class="mb-3">
                                                        <label>Assign to Group (Optional)</label>
                                                        <input type="text" name="group_name" class="form-control" 
                                                               placeholder="e.g., Group A, Community Outreach, etc.">
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
                                <div class="modal fade" id="rejectModal<?= $volunteer['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reject Volunteer</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Reject <strong><?= e($volunteer['name']) ?></strong>?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="volunteer_id" value="<?= $volunteer['id'] ?>">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?= $volunteer['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Volunteer</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="volunteer_id" value="<?= $volunteer['id'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label>Name</label>
                                                        <input type="text" name="name" class="form-control" value="<?= e($volunteer['name']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Phone</label>
                                                        <input type="tel" name="phone" class="form-control" value="<?= e($volunteer['phone']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Email</label>
                                                        <input type="email" name="email" class="form-control" value="<?= e($volunteer['email']) ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Group</label>
                                                        <input type="text" name="group_name" class="form-control" value="<?= e($volunteer['group_name']) ?>">
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
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=volunteers&status=<?= e($status_filter) ?>&search=<?= e($search) ?>&p=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>