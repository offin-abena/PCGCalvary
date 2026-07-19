<div class="page-header">
    <h1>Automation</h1>
    <form method="POST" action="<?= url('automation/run') ?>" class="d-inline">
            <?= church_csrf_field() ?>
            <button type="submit" class="btn btn-accent"><i class="bi bi-play"></i> Run All Now</button></form>
</div>
<div class="row g-4 mb-4"><?php foreach ($rules as $r): ?>
    <div class="col-md-4"><div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <h5><?= e($r['name']) ?></h5>
            <form method="POST" action="<?= url('automation/'.$r['id'].'/toggle') ?>">
            <?= church_csrf_field() ?>
            <button class="btn btn-sm btn-<?= $r['is_active'] ? 'success' : 'secondary' ?>"><?= $r['is_active'] ? 'Active' : 'Inactive' ?></button></form>
        </div>
        <span class="badge bg-info"><?= e(str_replace('_',' ',$r['rule_type'])) ?></span>
        <p class="text-muted small mt-2">Runs: <?= $r['run_count'] ?> | Last: <?= format_date($r['last_run'], 'M d h:i A') ?></p>
    </div></div></div>
<?php endforeach; ?></div>
<div class="card"><div class="card-header">Recent Automation Logs</div><div class="table-responsive"><table class="table table-sm">
    <thead><tr><th>Rule</th><th>Executed</th><th>Recipients</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($recentLogs as $l): ?><tr><td><?= e($l['rule_name']) ?></td><td><?= format_date($l['executed_at'], 'M d h:i A') ?></td><td><?= $l['recipients_count'] ?></td><td><?= status_badge($l['status']) ?></td></tr><?php endforeach; ?></tbody>
</table></div></div>
