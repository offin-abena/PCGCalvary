<div class="page-header">
    <h1>Assign Module Links</h1>
    <a href="<?= url('users') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Users</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4"><strong>User:</strong> <?= e($user['username']) ?></div>
            <div class="col-md-4"><strong>Email:</strong> <?= e($user['email']) ?></div>
            <div class="col-md-4"><strong>Role:</strong> <span class="badge bg-primary"><?= e($user['role_name']) ?></span></div>
        </div>
        <?php if (!$hasExplicit): ?>
        <p class="text-muted small mt-3 mb-0">
            <i class="bi bi-info-circle"></i> No custom assignments yet. Checked items reflect role-based defaults. Save to apply explicit link access.
        </p>
        <?php else: ?>
        <p class="text-muted small mt-3 mb-0">
            <i class="bi bi-info-circle"></i> This user has custom link assignments. Unchecked modules will be hidden and blocked.
        </p>
        <?php endif; ?>
    </div>
</div>

<form method="POST" action="<?= url('users/' . $user['id'] . '/assign-links') ?>">
            <?= church_csrf_field() ?>
            <?php
    $bySection = [];
    foreach ($modules as $module) {
        $bySection[$module['section']][] = $module;
    }
    ?>

    <div class="row g-4">
        <?php foreach ($bySection as $section => $sectionModules): ?>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><?= e($section) ?></div>
                <div class="card-body">
                    <?php foreach ($sectionModules as $module):
                        $isDashboard = $module['key'] === 'dashboard';
                        $checked = $linkStates[$module['key']] ?? false;
                    ?>
                    <div class="form-check mb-2">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="links[]"
                            value="<?= e($module['key']) ?>"
                            id="link-<?= e($module['key']) ?>"
                            <?= $checked ? 'checked' : '' ?>
                            <?= $isDashboard ? 'checked disabled' : '' ?>
                        >
                        <?php if ($isDashboard): ?>
                        <input type="hidden" name="links[]" value="dashboard">
                        <?php endif; ?>
                        <label class="form-check-label" for="link-<?= e($module['key']) ?>">
                            <i class="bi bi-<?= e($module['icon']) ?>"></i> <?= e($module['label']) ?>
                            <?php if ($isDashboard): ?><span class="text-muted small">(always on)</span><?php endif; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Link Assignments</button>
        <a href="<?= url('users') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
