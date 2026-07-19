<div class="page-header">
    <h1>User Management</h1>
    <div>
        <a href="<?= url('audit-logs') ?>" class="btn btn-outline-secondary btn-sm">Audit Logs</a>
        <a href="<?= url('users/create') ?>" class="btn btn-primary btn-sm">Create User</a>
    </div>
</div>

<?php if ($isSuperAdmin): ?>
<div class="alert alert-info py-2 small">
    <i class="bi bi-shield-lock"></i> As Super Admin you can assign navigation links to each user and activate or deactivate accounts.
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <?php if ($isSuperAdmin): ?><th class="text-end">Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge bg-primary"><?= e($u['role_name']) ?></span></td>
                    <td><?= $u['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                    <td><?= format_date($u['last_login'], 'M d, Y h:i A') ?></td>
                    <?php if ($isSuperAdmin): ?>
                    <td class="text-end text-nowrap">
                        <a href="<?= url('users/' . $u['id'] . '/assign-links') ?>" class="btn btn-sm btn-outline-primary" title="Assign Links">
                            <i class="bi bi-link-45deg"></i> Links
                        </a>
                        <form method="POST" action="<?= url('users/' . $u['id'] . '/toggle-status') ?>" class="d-inline">
            <?= church_csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?>" title="<?= $u['is_active'] ? 'Deactivate User' : 'Activate User' ?>">
                                <i class="bi bi-<?= $u['is_active'] ? 'person-x' : 'person-check' ?>"></i>
                            </button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
