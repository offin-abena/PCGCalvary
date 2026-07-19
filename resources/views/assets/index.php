<div class="page-header">
    <h1>Assets & Inventory</h1>
    <div>
        <a href="<?= url('assets/bookings') ?>" class="btn btn-outline-primary btn-sm">Bookings</a>
        <a href="<?= url('assets/create') ?>" class="btn btn-primary btn-sm">Register Asset</a>
    </div>
</div>
<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Total Assets</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= format_money($stats['total_value']) ?></div><div class="stat-label">Total Value</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value text-warning"><?= $stats['maintenance_due'] ?></div><div class="stat-label">Maintenance Due</div></div></div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Location</th><th>Condition</th><th>Assigned To</th><th></th></tr></thead>
    <tbody><?php foreach ($assets as $a): ?>
        <tr><td><code><?= e($a['asset_code']) ?></code></td><td><?= e($a['name']) ?></td><td><?= e($a['category'] ?? '-') ?></td><td><?= e($a['location'] ?? '-') ?></td><td><?= status_badge($a['condition_status']) ?></td><td><?= e($a['assigned_name'] ?? '-') ?></td><td><a href="<?= url('assets/'.$a['id']) ?>" class="btn btn-sm btn-outline-primary">View</a></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
