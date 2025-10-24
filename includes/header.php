<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'NGO - Making a Difference' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 80px 0; }
        .cta-section { background: #f8f9fa; }
        .membership-card { cursor: pointer; transition: all 0.3s; }
        .membership-card:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .membership-radio { display: none; }
        .membership-radio:checked + label { border: 2px solid #0d6efd; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/">NGO</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="/gallery">Gallery</a></li>
                    <li class="nav-item"><a class="nav-link" href="/news">News</a></li>
                    <li class="nav-item"><a class="nav-link" href="/donate">Donate</a></li>
                    <li class="nav-item"><a class="nav-link" href="/volunteer">Volunteer</a></li>
                    <li class="nav-item"><a class="nav-link" href="/partner">Partnership</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/logout">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/login">Login</a></li>
                        <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2" href="/register">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>