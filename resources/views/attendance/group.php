<div class="page-header">
    <h1><?= e($title ?? 'Attendance') ?></h1>
    <div>
        <?php if (empty($general)): ?>
        <a href="<?= url('attendance/scan?group=' . e($ageGroup)) ?>" class="btn btn-accent btn-sm"><i class="bi bi-qr-code-scan"></i> QR Check-in</a>
        <?php endif; ?>
        <a href="<?= url('attendance/create?group=' . e($ageGroup)) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Session</a>
    </div>
</div>

<form method="GET" class="search-bar row g-2 align-items-end mb-4">
    <div class="col-md-3">
        <label class="form-label">From</label>
        <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">To</label>
        <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
    </div>
</form>

<div class="row g-4 mb-4">
    <div class="<?= !empty($general) ? 'col-md-6' : 'col-md-4' ?>">
        <div class="stat-card text-center">
            <div class="stat-value text-primary"><?= number_format($stats['male']) ?></div>
            <div class="stat-label">Male</div>
            <small class="text-muted"><?= e($ageGroupLabel) ?> sessions</small>
        </div>
    </div>
    <div class="<?= !empty($general) ? 'col-md-6' : 'col-md-4' ?>">
        <div class="stat-card text-center">
            <div class="stat-value text-danger"><?= number_format($stats['female']) ?></div>
            <div class="stat-label">Female</div>
            <small class="text-muted"><?= e($ageGroupLabel) ?> sessions</small>
        </div>
    </div>
    <?php if (empty($general)): ?>
    <div class="col-md-4">
        <div class="stat-card text-center">
            <div class="stat-value text-success"><?= number_format($stats['total']) ?></div>
            <div class="stat-label">Total</div>
            <small class="text-muted">Male + Female</small>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header fw-semibold"><?= e($ageGroupLabel) ?> Sessions</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Service</th>
                    <th>Title</th>
                    <th class="text-center">Male</th>
                    <th class="text-center">Female</th>
                    <?php if (empty($general)): ?><th class="text-center">Total</th><?php endif; ?>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sessions['data'])): ?>
                <tr><td colspan="<?= !empty($general) ? 6 : 7 ?>" class="text-center text-muted py-4">No sessions for this group yet. Create one for today's activity.</td></tr>
                <?php else: foreach ($sessions['data'] as $s): ?>
                <tr>
                    <td><?= format_date($s['session_date']) ?></td>
                    <td><?= e($s['service_name']) ?></td>
                    <td><?= e($s['title'] ?? '-') ?></td>
                    <td class="text-center"><span class="badge bg-primary"><?= number_format($s['male_count']) ?></span></td>
                    <td class="text-center"><span class="badge bg-danger"><?= number_format($s['female_count']) ?></span></td>
                    <?php if (empty($general)): ?><td class="text-center"><span class="badge bg-success"><?= number_format($s['total_count']) ?></span></td><?php endif; ?>
                    <td><a href="<?= url('attendance/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary">Manage</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($sessions['total_pages'] > 1): ?>
    <div class="card-footer d-flex justify-content-center">
        <?php for ($i = 1; $i <= $sessions['total_pages']; $i++): ?>
            <a href="?page=<?= $i ?>&start_date=<?= e($startDate) ?>&end_date=<?= e($endDate) ?>"
               class="btn btn-sm <?= $i === $sessions['page'] ? 'btn-primary' : 'btn-outline-secondary' ?> mx-1"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
