<div class="page-header">
    <h1><?= e($session['service_name']) ?> - <?= format_date($session['session_date']) ?></h1>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-secondary"><?= e($ageGroupLabel ?? '') ?></span>
        <?php if (!empty($general)): ?>
        <span class="badge bg-primary fs-6"><?= number_format(($maleCount ?? 0) + ($femaleCount ?? 0)) ?> attendees</span>
        <?php else: ?>
        <span class="badge bg-primary fs-6"><?= count($attendees) ?> attendees</span>
        <?php endif; ?>
        <a href="<?= url($listUrl ?? 'attendance/adults') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
</div>

<?php if (!empty($general)): ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Record Headcount</div>
            <div class="card-body">
                <p class="text-muted small mb-3">Enter the total male and female attendance for this service.</p>
                <form method="POST" action="<?= url('attendance/' . $session['id'] . '/counts') ?>">
                    <?= church_csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Male</label>
                        <input type="number" name="male_count" class="form-control" min="0" value="<?= (int) ($maleCount ?? 0) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Female</label>
                        <input type="number" name="female_count" class="form-control" min="0" value="<?= (int) ($femaleCount ?? 0) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-people"></i> Save Attendance</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Session Summary</div>
            <div class="card-body">
                <div class="row g-3 text-center mb-3">
                    <div class="col-md-6">
                        <div class="stat-card">
                            <div class="stat-value text-primary"><?= number_format($maleCount ?? 0) ?></div>
                            <div class="stat-label">Male</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card">
                            <div class="stat-value text-danger"><?= number_format($femaleCount ?? 0) ?></div>
                            <div class="stat-label">Female</div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($session['title'])): ?>
                <p class="mb-1"><strong>Title:</strong> <?= e($session['title']) ?></p>
                <?php endif; ?>
                <?php if (!empty($session['notes'])): ?>
                <p class="mb-0"><strong>Notes:</strong> <?= e($session['notes']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Check In Member</div>
            <div class="card-body">
                <form method="POST" action="<?= url('attendance/' . $session['id'] . '/checkin') ?>">
                    <?= church_csrf_field() ?>
                    <select name="member_id" class="form-select mb-3" required>
                        <option value="">Select member...</option>
                        <?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name']) ?></option><?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-circle"></i> Check In</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Attendees</div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Name</th><th>Code</th><th>Gender</th><th>Time</th><th>Method</th></tr></thead>
                    <tbody>
                        <?php if (empty($attendees)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No check-ins yet</td></tr>
                        <?php else: foreach ($attendees as $a): ?>
                        <tr>
                            <td><?= e($a['first_name'] . ' ' . $a['last_name']) ?></td>
                            <td><code><?= e($a['member_code']) ?></code></td>
                            <td><?= ucfirst(e($a['gender'] ?? '-')) ?></td>
                            <td><?= format_date($a['check_in_time'], 'h:i A') ?></td>
                            <td><span class="badge bg-secondary"><?= e($a['check_in_method']) ?></span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
