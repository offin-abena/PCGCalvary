<div class="page-header"><h1>Budget Management</h1></div>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>Name</th><th>Year</th><th>Total</th><th>Status</th><th>Created By</th></tr></thead>
    <tbody><?php foreach ($budgets as $b): ?>
        <tr><td><?= e($b['name']) ?></td><td><?= $b['fiscal_year'] ?></td><td><?= format_money($b['total_amount']) ?></td><td><?= status_badge($b['status']) ?></td><td><?= e($b['created_by_name'] ?? '-') ?></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
