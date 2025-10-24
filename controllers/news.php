<?php
$pdo = getDB();

// Get post type (news or blog)
$type = in_array($_GET['type'] ?? 'news', ['news', 'blog'], true) ? $_GET['type'] : 'news';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Fetch total count
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM posts 
    WHERE post_type = ? AND is_published = 1
");
$stmt->execute([$type]);
$total_items = (int)$stmt->fetchColumn();
$total_pages = ceil($total_items / $per_page);

// Fetch posts
$stmt = $pdo->prepare("
    SELECT id, title, slug, content, featured_image, published_at 
    FROM posts 
    WHERE post_type = ? AND is_published = 1 
    ORDER BY published_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$type, $per_page, $offset]);
$posts = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= ucfirst($type) ?></h1>
        <div class="btn-group">
            <a href="/news?type=news" class="btn btn-<?= $type === 'news' ? 'primary' : 'outline-primary' ?>">News</a>
            <a href="/news?type=blog" class="btn btn-<?= $type === 'blog' ? 'primary' : 'outline-primary' ?>">Blog</a>
        </div>
    </div>
    
    <?php if (empty($posts)): ?>
        <p class="text-muted">No <?= $type ?> articles available yet.</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($posts as $post): ?>
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="row g-0">
                            <?php if ($post['featured_image']): ?>
                                <div class="col-md-3">
                                    <img src="/<?= e($post['featured_image']) ?>" 
                                         class="img-fluid h-100" 
                                         style="object-fit: cover;" 
                                         alt="<?= e($post['title']) ?>">
                                </div>
                            <?php endif; ?>
                            <div class="col-md-<?= $post['featured_image'] ? '9' : '12' ?>">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <a href="/news/<?= e($post['slug']) ?>" class="text-decoration-none">
                                            <?= e($post['title']) ?>
                                        </a>
                                    </h4>
                                    <p class="text-muted small">
                                        <?= date('d M Y', strtotime($post['published_at'])) ?>
                                    </p>
                                    <p class="card-text">
                                        <?= e(substr(strip_tags($post['content']), 0, 200)) ?>...
                                    </p>
                                    <a href="/news/<?= e($post['slug']) ?>" class="btn btn-sm btn-outline-primary">
                                        Read More
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="/news?type=<?= $type ?>&page=<?= $page - 1 ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="/news?type=<?= $type ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="/news?type=<?= $type ?>&page=<?= $page + 1 ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>