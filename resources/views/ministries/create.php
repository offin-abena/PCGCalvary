<div class="page-header"><h1>Create Ministry</h1></div>
<div class="card" style="max-width: 600px;"><div class="card-body">
    <form method="POST" action="<?= url('ministries/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Type</label><select name="ministry_type" class="form-select"><option value="department">Department</option><option value="cell_group">Cell Group</option><option value="children">Children</option><option value="youth">Youth</option><option value="worship">Worship</option></select></div>
        <div class="mb-3"><label class="form-label">Leader</label><select name="leader_id" class="form-select"><option value="">Select...</option><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'].' '.$m['last_name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><textarea name="description" class="form-control" placeholder="Description" rows="3"></textarea></div>
        <div class="row g-3">
            <div class="col-md-4"><input type="text" name="meeting_day" class="form-control" placeholder="Meeting Day"></div>
            <div class="col-md-4"><input type="time" name="meeting_time" class="form-control"></div>
            <div class="col-md-4"><input type="text" name="meeting_location" class="form-control" placeholder="Location"></div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Create</button>
    </form>
</div></div>
