<div class="page-header">
    <h1>Counseling</h1>
    <a href="<?= url('counseling/schedule') ?>" class="btn btn-primary"><i class="bi bi-calendar-plus"></i> Schedule</a>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Member</th><th>Counselor</th><th>Date</th><th>Topic</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($appointments as $a): ?>
        <tr><td><?= e($a['member_name']) ?></td><td><?= e($a['counselor_name'] ?? 'TBD') ?></td><td><?= format_date($a['appointment_date'], 'M d, Y h:i A') ?></td><td><?= e($a['topic'] ?? '-') ?></td><td><?= status_badge($a['status']) ?></td><td><a href="<?= url('counseling/'.$a['id']) ?>" class="btn btn-sm btn-outline-primary">View</a></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
