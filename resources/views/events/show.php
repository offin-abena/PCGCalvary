<div class="page-header"><h1><?= e($event['title']) ?></h1><?= status_badge($event['status']) ?></div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <p><i class="bi bi-calendar"></i> <?= format_date($event['start_date'], 'M d, Y h:i A') ?></p>
            <p><i class="bi bi-geo-alt"></i> <?= e($event['location'] ?? 'TBD') ?></p>
            <p><?= e($event['description'] ?? '') ?></p>
        </div></div>
        <div class="card"><div class="card-header">Register Attendee</div><div class="card-body">
            <form method="POST" action="<?= url('events/'.$event['id'].'/register') ?>">
            <?= church_csrf_field() ?>
            <select name="member_id" class="form-select mb-2"><option value="">Guest</option><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'].' '.$m['last_name']) ?></option><?php endforeach; ?></select>
                <input type="text" name="guest_name" class="form-control mb-2" placeholder="Guest name">
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="card-header">Registrations (<?= count($registrations) ?>)</div><div class="table-responsive"><table class="table table-sm">
            <thead><tr><th>Name</th><th>Date</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($registrations as $r): ?>
                <tr><td><?= e($r['member_name'] ?? $r['guest_name']) ?></td><td><?= format_date($r['registration_date']) ?></td><td><?= status_badge($r['status']) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div></div>
    </div>
</div>
