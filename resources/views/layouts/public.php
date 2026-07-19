<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Give') ?> - <?= e($config['church_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= church_asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark" style="background: var(--primary);">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('donations/give') ?>">
                <img src="<?= church_logo() ?>" alt="Presbyterian Church of Ghana Logo" class="church-logo church-logo-nav">
                <?= e($config['church_name']) ?>
            </a>
            <a href="<?= url('login') ?>" class="btn btn-outline-light btn-sm">Staff Login</a>
        </div>
    </nav>

    <?php $flash = flash_message(); if ($flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        </div>
    <?php endif; ?>

    <?= $content ?>

    <footer class="text-center py-4 text-muted">
        <small>&copy; <?= date('Y') ?> <?= e($config['church_name']) ?>. All rights reserved.</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
