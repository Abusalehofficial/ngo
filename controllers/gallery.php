<?php
$pdo = getDB();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Fetch total count
$stmt = $pdo->query("SELECT COUNT(*) FROM gallery WHERE is_active = 1");
$total_items = (int)$stmt->fetchColumn();
$total_pages = ceil($total_items / $per_page);

// Fetch gallery items
$stmt = $pdo->prepare("
    SELECT id, title, image_path, description 
    FROM gallery 
    WHERE is_active = 1 
    ORDER BY display_order ASC, created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$per_page, $offset]);
$gallery_items = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Gallery</h1>
    
    <?php if (empty($gallery_items)): ?>
        <p class="text-muted">No images available yet.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($gallery_items as $item): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <img src="/<?= e($item['image_path']) ?>" 
                             class="card-img-top" 
                             alt="<?= e($item['title']) ?>"
                             style="height: 250px; object-fit: cover;">
                        <?php if ($item['title'] || $item['description']): ?>
                            <div class="card-body">
                                <?php if ($item['title']): ?>
                                    <h5 class="card-title"><?= e($item['title']) ?></h5>
                                <?php endif; ?>
                                <?php if ($item['description']): ?>
                                    <p class="card-text text-muted small"><?= e($item['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
                            <a class="page-link" href="/gallery?page=<?= $page - 1 ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="/gallery?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="/gallery?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>