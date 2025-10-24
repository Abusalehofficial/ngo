<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - NGO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #212529;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: #343a40;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block sidebar p-0">
                <div class="position-sticky">
                    <div class="p-3 text-white">
                        <h4>NGO Admin</h4>
                        <small>Welcome, <?= e($admin['full_name'] ?? $admin['username']) ?></small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= $route === 'dashboard' ? 'active' : '' ?>" href="/admin/?page=dashboard">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $route === 'users' ? 'active' : '' ?>" href="/admin/?page=users">
                                <i class="bi bi-people"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $route === 'donations' ? 'active' : '' ?>" href="/admin/?page=donations">
                                <i class="bi bi-cash-coin"></i> Donations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $route === 'volunteers' ? 'active' : '' ?>" href="/admin/?page=volunteers">
                                <i class="bi bi-person-badge"></i> Volunteers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $route === 'partners' ? 'active' : '' ?>" href="/admin/?page=partners">
                                <i class="bi bi-building"></i> Partners
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $route === 'gallery' ? 'active' : '' ?>" href="/admin/?page=gallery">
                                <i class="bi bi-images"></i> Gallery
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $route === 'posts' ? 'active' : '' ?>" href="/admin/?page=posts">
                                <i class="bi bi-file-text"></i> News/Blogs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/?page=logout">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="py-4">
                    <?php 
                    $flash = getFlash();
                    if ($flash): 
                    ?>
                        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
                            <?= e($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>