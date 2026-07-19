<div class="page-header"><h1><?= e($asset['name']) ?></h1><code><?= e($asset['asset_code']) ?></code></div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <p><strong>Category:</strong> <?= e($asset['category'] ?? '-') ?></p>
            <p><strong>Location:</strong> <?= e($asset['location'] ?? '-') ?></p>
            <p><strong>Condition:</strong> <?= status_badge($asset['condition_status']) ?></p>
            <p><strong>Value:</strong> <?= format_money($asset['current_value'] ?? $asset['purchase_cost'] ?? 0) ?></p>
            <p><strong>Assigned:</strong> <?= e($asset['assigned_name'] ?? 'Unassigned') ?></p>
        </div></div>
        <div class="card"><div class="card-header">Log Maintenance</div><div class="card-body">
            <form method="POST" action="<?= url('assets/'.$asset['id'].'/maintenance') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-2"><input type="date" name="maintenance_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                <div class="mb-2"><input type="text" name="maintenance_type" class="form-control" placeholder="Type"></div>
                <div class="mb-2"><input type="number" name="cost" class="form-control" placeholder="Cost" step="0.01"></div>
                <div class="mb-2"><input type="date" name="next_due_date" class="form-control" placeholder="Next Due"></div>
                <button type="submit" class="btn btn-primary w-100">Log</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8"><div class="card"><div class="card-header">Maintenance History</div><div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>Date</th><th>Type</th><th>Cost</th><th>Next Due</th></tr></thead>
        <tbody><?php foreach ($maintenance as $m): ?><tr><td><?= format_date($m['maintenance_date']) ?></td><td><?= e($m['maintenance_type']) ?></td><td><?= format_money($m['cost']) ?></td><td><?= format_date($m['next_due_date']) ?></td></tr><?php endforeach; ?></tbody>
    </table></div></div></div>
</div>
