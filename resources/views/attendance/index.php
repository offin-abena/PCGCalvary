<div class="page-header">
    <h1>Attendance</h1>
    <div>
        <a href="<?= url('attendance/scan') ?>" class="btn btn-accent btn-sm"><i class="bi bi-qr-code-scan"></i> QR Check-in</a>
        <a href="<?= url('attendance/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Session</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <?php foreach ($analytics as $a): ?>
    <div class="col-md">
        <div class="stat-card text-center">
            <div class="stat-value"><?= $a['unique_attendees'] ?></div>
            <div class="stat-label"><?= e($a['service_name']) ?></div>
            <small class="text-muted">This month</small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Date</th><th>Service</th><th>Title</th><th>Attendees</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($sessions['data'] as $s): ?>
                <tr>
                    <td><?= format_date($s['session_date']) ?></td>
                    <td><?= e($s['service_name']) ?></td>
                    <td><?= e($s['title'] ?? '-') ?></td>
                    <td><span class="badge bg-primary"><?= $s['attendee_count'] ?></span></td>
                    <td><a href="<?= url('attendance/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary">Manage</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
