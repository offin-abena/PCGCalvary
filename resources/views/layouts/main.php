<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Dashboard') ?> - <?= e($config['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= church_asset('css/style.css') ?>" rel="stylesheet">
</head>
<body class="sidebar-expanded" data-authenticated="1">
    <?php
    $user = \App\Core\Auth::user();
    $navModules = \App\Services\ModuleAccess::getAccessibleModules();
    $currentUrl = current_path();
    ?>
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="<?= church_logo() ?>" alt="Presbyterian Church of Ghana Logo" class="church-logo church-logo-sidebar mb-2">
            <h4>CMS</h4>
            <small><?= e($config['church_name']) ?></small>
        </div>
        <div class="sidebar-nav">
            <?php foreach ($navModules as $section => $links): ?>
            <div class="nav-section"><?= e($section) ?></div>
            <?php foreach ($links as $link):
                $isActive = \App\Services\ModuleAccess::isNavLinkActive($link, $currentUrl);
                $children = $link['children'] ?? [];
                $isNavToggle = !empty($link['nav_toggle']) && !empty($children);
                $subActive = false;
                foreach ($children as $child) {
                    if (\App\Services\ModuleAccess::isNavLinkActive($child, $currentUrl)) {
                        $subActive = true;
                        break;
                    }
                }
                $submenuOpen = $subActive;
                $parentActive = $isNavToggle ? $subActive : $isActive;
            ?>
            <?php if ($isNavToggle): ?>
            <button type="button"
                    class="nav-toggle-btn <?= $parentActive ? 'active' : '' ?> <?= $submenuOpen ? 'expanded' : '' ?>"
                    data-nav-toggle
                    aria-expanded="<?= $submenuOpen ? 'true' : 'false' ?>">
                <i class="bi bi-<?= e($link['icon']) ?>"></i>
                <span><?= e($link['label']) ?></span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <?php else: ?>
            <a href="<?= url($link['url']) ?>" class="<?= $isActive ? 'active' : '' ?>">
                <i class="bi bi-<?= e($link['icon']) ?>"></i> <?= e($link['label']) ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($children)): ?>
            <div class="nav-submenu <?= $submenuOpen ? 'open' : '' ?>">
                <?php foreach ($children as $child):
                    $childActive = \App\Services\ModuleAccess::isNavLinkActive($child, $currentUrl);
                ?>
                <a href="<?= url($child['url']) ?>" class="nav-submenu-link <?= $childActive ? 'active' : '' ?>">
                    <i class="bi bi-<?= e($child['icon']) ?>"></i> <?= e($child['label']) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endforeach; endforeach; ?>
        </div>
    </nav>
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <div class="main-content">
        <div class="top-bar">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm sidebar-toggle" id="sidebarToggle" title="Toggle navigation menu" aria-label="Toggle navigation menu">
                    <i class="bi bi-list fs-5" id="sidebarToggleIcon"></i>
                </button>
                <span class="text-muted"><?= date('l, F j, Y') ?></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary"><?= e($user['role_name'] ?? 'User') ?></span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= e($user['username'] ?? '') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= url('settings') ?>"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><a class="dropdown-item" href="<?= url('settings#change-password') ?>"><i class="bi bi-key"></i> Change Password</a></li>
                        <?php if (\App\Core\Auth::isSuperAdmin()): ?>
                        <li><a class="dropdown-item" href="<?= url('users') ?>"><i class="bi bi-shield-lock"></i> Manage Users</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="page-content">
            <?php $flash = flash_message(); if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </div>

    <script>
        window.APP_URL = '<?= e(app_base_url()) ?>';
        window.CSRF_TOKEN = '<?= e(church_csrf_token()) ?>';
        window.SESSION_TIMEOUT_MS = <?= (int) \App\Core\Auth::sessionTimeoutSeconds() * 1000 ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= church_asset('js/app.js') ?>"></script>
</body>
</html>
