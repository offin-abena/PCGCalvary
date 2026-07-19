<div class="page-header"><h1>Youth Classes</h1></div>
<div class="row g-4">
    <div class="col-lg-4"><div class="card"><div class="card-header">Create Class</div><div class="card-body">
        <form method="POST" action="<?= url('youth/classes/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Class Name" required></div>
            <div class="mb-2"><select name="ministry_type" class="form-select"><option value="children">Children</option><option value="junior_youth">Junior Youth</option><option value="youth">Youth</option></select></div>
            <div class="mb-2"><input type="text" name="age_group" class="form-control" placeholder="Age Group (e.g. 5-8)"></div>
            <div class="mb-2"><select name="teacher_id" class="form-select"><option value="">Select Teacher</option><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'].' '.$m['last_name']) ?></option><?php endforeach; ?></select></div>
            <div class="mb-2"><input type="text" name="room" class="form-control" placeholder="Room"></div>
            <button type="submit" class="btn btn-primary w-100">Create</button>
        </form>
    </div></div>
    <div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table">
        <thead><tr><th>Class</th><th>Type</th><th>Enrolled</th><th>Age Group</th><th>Teacher</th><th>Room</th><th></th></tr></thead>
        <tbody><?php foreach ($classes as $c): ?><tr>
            <td><?= e($c['name']) ?></td>
            <td><?= e(str_replace('_',' ',$c['ministry_type'])) ?></td>
            <td><?= (int) ($c['student_count'] ?? 0) ?></td>
            <td><?= e($c['age_group'] ?? '-') ?></td>
            <td><?= e($c['teacher_name'] ?? '-') ?></td>
            <td><?= e($c['room'] ?? '-') ?></td>
            <td><a href="<?= url('youth/classes/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">Roster</a></td>
        </tr><?php endforeach; ?></tbody>
    </table></div></div></div>
</div>
