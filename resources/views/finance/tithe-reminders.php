<div class="page-header">
    <h1><i class="bi bi-bell"></i> Tithe SMS Reminders</h1>
    <a href="<?= url('finance/sms-logs') ?>" class="btn btn-outline-secondary btn-sm">View SMS Logs</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value"><?= $smsStats['tithe_reminders'] ?></div><div class="stat-label">Reminders Sent</div></div></div>
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value"><?= $smsStats['tithe_receipts'] ?></div><div class="stat-label">Receipt SMS</div></div></div>
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-warning"><?= count($pendingMembers) ?></div><div class="stat-label">Pending This Month</div></div></div>
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value"><?= e(ucfirst($smsConfig['provider'])) ?></div><div class="stat-label">SMS Provider</div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Send Bulk Reminders</div>
            <div class="card-body">
                <p class="text-muted small">Send SMS reminders to members who have not recorded a tithe payment this month.</p>
                <div class="alert alert-info small">
                    <strong>Preview message:</strong><br>
                    <?= e($smsConfig['templates']['tithe_reminder']) ?>
                </div>
                <form method="POST" action="<?= url('finance/tithe-reminders/send') ?>" onsubmit="return confirm('Send tithe reminders to <?= count($pendingMembers) ?>
            <?= church_csrf_field() ?>
            members?');">
                    <input type="hidden" name="only_without_tithe" value="1">
                    <button type="submit" class="btn btn-primary w-100" <?= count($pendingMembers) === 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-send"></i> Send to <?= count($pendingMembers) ?> Members
                    </button>
                </form>
                <?php if ($smsConfig['provider'] === 'log'): ?>
                    <p class="text-warning small mt-2"><i class="bi bi-info-circle"></i> Dev mode: SMS are logged, not actually sent. Set <code>SMS_PROVIDER=log</code> in <code>.env</code> for testing.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">SMS Configuration</div>
            <div class="card-body small">
                <p><strong>Provider:</strong> <?= e($smsConfig['provider']) ?></p>
                <p><strong>Enabled:</strong> <?= $smsConfig['enabled'] ? 'Yes' : 'No' ?></p>
                <p class="text-muted">Edit <code>.env</code> to configure Wigal FROG or other SMS providers.</p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Members Without Tithe This Month (<?= date('F Y') ?>)</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Name</th><th>Phone</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($pendingMembers)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">All members have tithed this month!</td></tr>
                        <?php else: foreach ($pendingMembers as $m): ?>
                            <tr>
                                <td><?= e($m['first_name'] . ' ' . $m['last_name']) ?></td>
                                <td><code><?= e($m['phone']) ?></code></td>
                                <td><span class="badge bg-warning">Pending</span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
