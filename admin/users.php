<?php
// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($action === 'approve' && $user_id) {
        // Approve user - set membership dates
        $stmt = $pdo->prepare("
            UPDATE users 
            SET membership_status = 'active', 
                membership_start = CURDATE(), 
                membership_expiry = DATE_ADD(CURDATE(), INTERVAL 1 YEAR)
            WHERE id = ? AND membership_status = 'pending'
        ");
        $stmt->execute([$user_id]);
        setFlash('success', 'User approved successfully');
        
    } elseif ($action === 'reject' && $user_id) {
        $stmt = $pdo->prepare("UPDATE users SET membership_status = 'rejected' WHERE id = ?");
        $stmt->execute([$user_id]);
        setFlash('success', 'User rejected');
        
    } elseif ($action === 'add_activity' && $user_id) {
        $title = trim($_POST['activity_title'] ?? '');
        $description = trim($_POST['activity_description'] ?? '');
        $date = $_POST['activity_date'] ?? date('Y-m-d');
        
        if ($title) {
            $stmt = $pdo->prepare("
                INSERT INTO user_activities (user_id, activity_title, activity_description, activity_date, added_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $title, $description, $date, $admin['id']]);
            setFlash('success', 'Activity added successfully');
        }
        
    } elseif ($action === 'edit_user' && $user_id) {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if ($name && $phone && $email) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $email, $user_id]);
            setFlash('success', 'User updated successfully');
        }
    }
    
    adminRedirect('?page=users');
}

// Filters
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Build query
$where = ['1=1'];
$params = [];

if ($status_filter !== 'all') {
    $where[] = 'membership_status = ?';
    $params[] = $status_filter;
}

if ($search) {
    $where[] = '(name LIKE ? OR email LIKE ? OR uid LIKE ? OR phone LIKE ?)';
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

// Pagination
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_clause = implode(' AND ', $where);

// Count total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where_clause");
$stmt->execute($params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Fetch users
$stmt = $pdo->prepare("
    SELECT id, uid, name, email, phone, membership_type, membership_status, 
           membership_start, membership_expiry, created_at 
    FROM users 
    WHERE $where_clause 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$per_page, $offset]));
$users = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Users Management</h2>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="users">
            
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by name, email, UID, phone" value="<?= e($search) ?>">
            </div>
            
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="expired" <?= $status_filter === 'expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            
            <div class="col-md-2">
                <a href="/admin/?page=users" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Name</th>
                        <th>Email / Phone</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Expiry</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= e($user['uid']) ?></td>
                            <td><?= e($user['name']) ?></td>
                            <td>
                                <?= e($user['email']) ?><br>
                                <small class="text-muted"><?= e($user['phone']) ?></small>
                            </td>
                            <td><span class="badge bg-<?= $user['membership_type'] === 'premium' ? 'success' : 'primary' ?>"><?= ucfirst(e($user['membership_type'])) ?></span></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $user['membership_status'] === 'active' ? 'success' : 
                                    ($user['membership_status'] === 'pending' ? 'warning' : 'danger') 
                                ?>">
                                    <?= ucfirst(e($user['membership_status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['membership_expiry']): ?>
                                    <?= date('d M Y', strtotime($user['membership_expiry'])) ?>
                                <?php else: ?>
                                    <small class="text-muted">N/A</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($user['membership_status'] === 'pending'): ?>
                                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?= $user['id'] ?>">
                                            Approve
                                        </button>
                                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $user['id'] ?>">
                                            Reject
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $user['id'] ?>">
                                        Edit
                                    </button>
                                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#activityModal<?= $user['id'] ?>">
                                        Activity
                                    </button>
                                </div>
                                
                                <!-- Approve Modal -->
                                <div class="modal fade" id="approveModal<?= $user['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Approve User</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Approve <strong><?= e($user['name']) ?></strong>?</p>
                                                <p>Membership will be activated for 1 year.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Approve</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal<?= $user['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reject User</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Reject <strong><?= e($user['name']) ?></strong>?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?= $user['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                                    <input type="hidden" name="action" value="edit_user">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label>Name</label>
                                                        <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Phone</label>
                                                        <input type="tel" name="phone" class="form-control" value="<?= e($user['phone']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Email</label>
                                                        <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
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
                                
                                <!-- Activity Modal -->
                                <div class="modal fade" id="activityModal<?= $user['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Add Activity for <?= e($user['name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                                    <input type="hidden" name="action" value="add_activity">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label>Activity Title</label>
                                                        <input type="text" name="activity_title" class="form-control" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Description</label>
                                                        <textarea name="activity_description" class="form-control" rows="3"></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Activity Date</label>
                                                        <input type="date" name="activity_date" class="form-control" value="<?= date('Y-m-d') ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Add Activity</button>
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
                            <a class="page-link" href="?page=users&status=<?= e($status_filter) ?>&search=<?= e($search) ?>&p=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>