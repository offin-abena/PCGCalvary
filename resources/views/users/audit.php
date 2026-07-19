<div class="page-header"><h1>Audit Logs</h1></div>
<div class="card"><div class="table-responsive"><table class="table table-sm">
    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Record</th><th>IP</th></tr></thead>
    <tbody><?php foreach ($logs as $l): ?>
        <tr><td><?= format_date($l['created_at'], 'M d h:i A') ?></td><td><?= e($l['username'] ?? 'System') ?></td><td><code><?= e($l['action']) ?></code></td><td><?= e($l['module'] ?? '-') ?></td><td><?= $l['record_id'] ?? '-' ?></td><td><?= e($l['ip_address'] ?? '-') ?></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
