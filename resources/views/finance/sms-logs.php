<div class="page-header">
    <h1><i class="bi bi-chat-dots"></i> SMS Logs</h1>
    <a href="<?= url('finance/tithe-reminders') ?>" class="btn btn-primary btn-sm">Tithe Reminders</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value"><?= $smsStats['total'] ?></div><div class="stat-label">Total SMS</div></div></div>
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-success"><?= $smsStats['tithe_receipts'] ?></div><div class="stat-label">Tithe Receipts</div></div></div>
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-primary"><?= $smsStats['tithe_reminders'] ?></div><div class="stat-label">Tithe Reminders</div></div></div>
    <div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-danger"><?= $smsStats['failed'] ?></div><div class="stat-label">Failed</div></div></div>
</div>

<div class="card">
    <div class="card-header">Recent SMS Activity</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
                <tr><th>Time</th><th>Member</th><th>Phone</th><th>Context</th><th>Status</th><th>Message</th></tr>
            </thead>
            <tbody>
                <?php if (empty($smsStats['recent'])): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No SMS sent yet</td></tr>
                <?php else: foreach ($smsStats['recent'] as $log): ?>
                    <tr>
                        <td><?= format_date($log['created_at'], 'M d h:i A') ?></td>
                        <td><?= e($log['member_name'] ?? '-') ?></td>
                        <td><code><?= e($log['phone']) ?></code></td>
                        <td><span class="badge bg-secondary"><?= e(str_replace('_', ' ', $log['context'])) ?></span></td>
                        <td><?= $log['status'] === 'sent' ? '<span class="badge bg-success">Sent</span>' : '<span class="badge bg-danger">Failed</span>' ?></td>
                        <td class="small text-truncate" style="max-width: 250px;" title="<?= e($log['message']) ?>"><?= e($log['message']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
