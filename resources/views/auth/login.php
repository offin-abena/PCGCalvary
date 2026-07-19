<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Church Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= church_asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<?php $config = config('church'); ?>
<div class="login-page">
    <div class="login-card">
        <div class="logo">
            <img src="<?= church_logo() ?>" alt="Presbyterian Church of Ghana Logo" class="church-logo church-logo-lg mb-3">
            <h2><?= e($config['church_name']) ?></h2>
            <p class="text-muted">Church Management System — Sign in</p>
        </div>

        <?php $flash = flash_message(); if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= url('login') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <input type="text" name="username" class="form-control" value="<?= old('username') ?>" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="<?= url('donations/give') ?>" class="text-muted small">Online Giving Portal</a>
        </div>
    </div>
</div>
</body>
</html>
