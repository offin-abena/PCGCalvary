<div class="page-header">
    <h1>Events</h1>
    <a href="<?= url('events/create') ?>" class="btn btn-primary"><i class="bi bi-plus"></i> Create Event</a>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Title</th><th>Type</th><th>Date</th><th>Location</th><th>Registrations</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($events['data'] as $e): ?>
        <tr><td class="fw-semibold"><?= e($e['title']) ?></td><td><?= e($e['event_type']) ?></td><td><?= format_date($e['start_date'], 'M d, Y h:i A') ?></td><td><?= e($e['location'] ?? '-') ?></td><td><span class="badge bg-info"><?= $e['registration_count'] ?></span></td><td><?= status_badge($e['status']) ?></td><td><a href="<?= url('events/'.$e['id']) ?>" class="btn btn-sm btn-outline-primary">View</a></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
