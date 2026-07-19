<div class="page-header"><h1>Resource Bookings</h1><a href="<?= url('assets') ?>" class="btn btn-outline-secondary btn-sm">Back to Assets</a></div>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>Asset</th><th>Booked By</th><th>Start</th><th>End</th><th>Purpose</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($bookings as $b): ?>
        <tr><td><?= e($b['asset_name']) ?></td><td><?= e($b['booked_by_name']) ?></td><td><?= format_date($b['start_datetime'], 'M d h:i A') ?></td><td><?= format_date($b['end_datetime'], 'M d h:i A') ?></td><td><?= e($b['purpose'] ?? '-') ?></td><td><?= status_badge($b['status']) ?></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
