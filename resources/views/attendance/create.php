<div class="page-header">
    <h1><?= e($title ?? 'New Attendance Session') ?></h1>
    <a href="<?= url($listUrl ?? 'attendance/adults') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
</div>
<div class="card" style="max-width: 600px;">
    <div class="card-body">
        <div class="alert alert-info py-2">
            <i class="bi bi-info-circle"></i> This session is for <strong><?= e($ageGroupLabel ?? 'General') ?></strong> Sunday activity.
        </div>
        <form method="POST" action="<?= url('attendance/store') ?>">
            <?= church_csrf_field() ?>
            <input type="hidden" name="age_group" value="<?= e($ageGroup ?? 'adults') ?>">
            <div class="mb-3"><label class="form-label">Service Type *</label>
                <select name="service_type_id" class="form-select" required>
                    <?php foreach ($serviceTypes as $st): ?><option value="<?= $st['id'] ?>"><?= e($st['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Date *</label><input type="date" name="session_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            <div class="mb-3"><label class="form-label">Time</label><input type="time" name="session_time" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" placeholder="e.g. <?= e($ageGroupLabel ?? '') ?> worship"></div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            <button type="submit" class="btn btn-primary">Create Session</button>
        </form>
    </div>
</div>
