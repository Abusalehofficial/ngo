<?php
// Handle gallery actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' && !empty($_FILES['image']['name'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        
        // Upload image
        $upload = uploadFile($_FILES['image'], 'uploads/gallery', ['jpg', 'jpeg', 'png']);
        
        if ($upload['success']) {
            $stmt = $pdo->prepare("
                INSERT INTO gallery (title, image_path, description, display_order, is_active) 
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$title, 'uploads/gallery/' . $upload['filename'], $description, $display_order]);
            setFlash('success', 'Image added to gallery');
        } else {
            setFlash('error', $upload['error']);
        }
        
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Get image path to delete file
            $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            
            if ($item && file_exists($item['image_path'])) {
                unlink($item['image_path']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Image deleted');
        }
        
    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare("UPDATE gallery SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Status updated');
        }
    }
    
    adminRedirect('?page=gallery');
}

// Fetch gallery items
$stmt = $pdo->query("
    SELECT * FROM gallery 
    ORDER BY display_order ASC, created_at DESC
");
$gallery_items = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gallery Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg"></i> Add Image
    </button>
</div>

<!-- Add Image Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Add Gallery Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label>Image *</label>
                        <input type="file" name="image" class="form-control" required accept="image/jpeg,image/png">
                        <small class="text-muted">JPG, PNG (Max 5MB)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label>Title (Optional)</label>
                        <input type="text" name="title" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label>Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label>Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="0">
                        <small class="text-muted">Lower numbers appear first</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Image</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Gallery Grid -->
<div class="row g-4">
    <?php foreach ($gallery_items as $item): ?>
        <div class="col-md-3">
            <div class="card">
                <img src="/<?= e($item['image_path']) ?>" class="card-img-top" alt="<?= e($item['title']) ?>" 
                     style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <?php if ($item['title']): ?>
                        <h6 class="card-title"><?= e($item['title']) ?></h6>
                    <?php endif; ?>
                    <?php if ($item['description']): ?>
                        <p class="card-text small text-muted"><?= e(substr($item['description'], 0, 50)) ?><?= strlen($item['description']) > 50 ? '...' : '' ?></p>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Order: <?= $item['display_order'] ?></small>
                        <span class="badge bg-<?= $item['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $item['is_active'] ? 'Active' : 'Hidden' ?>
                        </span>
                    </div>
                    <div class="btn-group btn-group-sm mt-2 w-100">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-outline-primary">
                                <?= $item['is_active'] ? 'Hide' : 'Show' ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger" 
                                    onclick="return confirm('Delete this image?')">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($gallery_items)): ?>
    <div class="alert alert-info">No images in gallery yet. Click "Add Image" to get started.</div>
<?php endif; ?>