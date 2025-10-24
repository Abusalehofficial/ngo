<?php
// Handle post actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $post_type = in_array($_POST['post_type'] ?? '', ['news', 'blog'], true) ? $_POST['post_type'] : 'news';
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        
        if ($title && $content) {
            // Generate slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
            $slug = $slug . '-' . time();
            
            // Upload featured image if provided
            $featured_image = null;
            if (!empty($_FILES['featured_image']['name'])) {
                $upload = uploadFile($_FILES['featured_image'], 'uploads/posts', ['jpg', 'jpeg', 'png']);
                if ($upload['success']) {
                    $featured_image = 'uploads/posts/' . $upload['filename'];
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO posts (title, slug, content, featured_image, post_type, is_published, author_id, published_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title, 
                $slug, 
                $content, 
                $featured_image, 
                $post_type, 
                $is_published, 
                $admin['id'],
                $is_published ? date('Y-m-d H:i:s') : null
            ]);
            
            setFlash('success', ucfirst($post_type) . ' created successfully');
        }
        
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Get image path to delete file
            $stmt = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            $post = $stmt->fetch();
            
            if ($post && $post['featured_image'] && file_exists($post['featured_image'])) {
                unlink($post['featured_image']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Post deleted');
        }
        
    } elseif ($action === 'toggle_publish') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE posts 
                SET is_published = NOT is_published,
                    published_at = CASE WHEN is_published = 0 THEN NOW() ELSE published_at END
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            setFlash('success', 'Publish status updated');
        }
    }
    
    adminRedirect('?page=posts');
}

// Filters
$type_filter = in_array($_GET['type'] ?? 'all', ['news', 'blog', 'all'], true) ? $_GET['type'] : 'all';

$where = '1=1';
$params = [];

if ($type_filter !== 'all') {
    $where = 'post_type = ?';
    $params[] = $type_filter;
}

// Fetch posts
$stmt = $pdo->prepare("
    SELECT * FROM posts 
    WHERE $where 
    ORDER BY created_at DESC
");
$stmt->execute($params);
$posts = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>News & Blog Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg"></i> Create Post
    </button>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="btn-group">
            <a href="?page=posts&type=all" class="btn btn-<?= $type_filter === 'all' ? 'primary' : 'outline-primary' ?>">All</a>
            <a href="?page=posts&type=news" class="btn btn-<?= $type_filter === 'news' ? 'primary' : 'outline-primary' ?>">News</a>
            <a href="?page=posts&type=blog" class="btn btn-<?= $type_filter === 'blog' ? 'primary' : 'outline-primary' ?>">Blogs</a>
        </div>
    </div>
</div>

<!-- Add Post Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label>Post Type *</label>
                        <select name="post_type" class="form-control" required>
                            <option value="news">News</option>
                            <option value="blog">Blog</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label>Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Content *</label>
                        <textarea name="content" class="form-control" rows="10" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label>Featured Image (Optional)</label>
                        <input type="file" name="featured_image" class="form-control" accept="image/jpeg,image/png">
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" name="is_published" class="form-check-input" id="publishCheck" checked>
                        <label class="form-check-label" for="publishCheck">
                            Publish immediately
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Posts Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Published</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <strong><?= e($post['title']) ?></strong><br>
                                <small class="text-muted"><?= e($post['slug']) ?></small>
                            </td>
                            <td><span class="badge bg-secondary"><?= ucfirst(e($post['post_type'])) ?></span></td>
                            <td>
                                <span class="badge bg-<?= $post['is_published'] ? 'success' : 'warning' ?>">
                                    <?= $post['is_published'] ? 'Published' : 'Draft' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($post['published_at']): ?>
                                    <?= date('d M Y', strtotime($post['published_at'])) ?>
                                <?php else: ?>
                                    <small class="text-muted">Not published</small>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($post['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/news/<?= e($post['slug']) ?>" target="_blank" class="btn btn-outline-primary">View</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                        <input type="hidden" name="action" value="toggle_publish">
                                        <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                        <button type="submit" class="btn btn-outline-info">
                                            <?= $post['is_published'] ? 'Unpublish' : 'Publish' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger" 
                                                onclick="return confirm('Delete this post?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (empty($posts)): ?>
    <div class="alert alert-info mt-3">No posts yet. Click "Create Post" to get started.</div>
<?php endif; ?>