<div class="page-header"><h1>Schedule Appointment</h1></div>
<div class="card" style="max-width: 500px;"><div class="card-body">
    <form method="POST" action="<?= url('counseling/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3"><label class="form-label">Member *</label><select name="member_id" class="form-select" required><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'].' '.$m['last_name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Counselor</label><select name="counselor_id" class="form-select"><option value="">Assign later</option><?php foreach ($counselors as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['first_name'].' '.$c['last_name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Date & Time *</label><input type="datetime-local" name="appointment_date" class="form-control" required></div>
        <div class="mb-3"><input type="text" name="topic" class="form-control" placeholder="Topic"></div>
        <div class="form-check mb-3"><input type="checkbox" name="is_confidential" value="1" class="form-check-input" id="conf" checked><label for="conf">Confidential</label></div>
        <button type="submit" class="btn btn-primary">Schedule</button>
    </form>
</div></div>
