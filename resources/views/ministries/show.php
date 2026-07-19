<div class="page-header"><h1><?= e($ministry['name']) ?></h1></div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <p><strong>Leader:</strong> <?= e($ministry['leader_name'] ?? 'None') ?></p>
            <p><strong>Meeting:</strong> <?= e($ministry['meeting_day'] ?? '-') ?> <?= e($ministry['meeting_time'] ?? '') ?></p>
            <p><?= e($ministry['description'] ?? '') ?></p>
        </div></div>
        <div class="card"><div class="card-header">Add Member</div><div class="card-body">
            <form method="POST" action="<?= url('ministries/'.$ministry['id'].'/add-member') ?>">
            <?= church_csrf_field() ?>
            <select name="member_id" class="form-select mb-2" required><?php foreach ($allMembers as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'].' '.$m['last_name']) ?></option><?php endforeach; ?></select>
                <select name="role" class="form-select mb-2"><option value="member">Member</option><option value="volunteer">Volunteer</option><option value="assistant">Assistant</option></select>
                <button type="submit" class="btn btn-primary w-100">Add</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8"><div class="card"><div class="card-header">Members (<?= count($members) ?>)</div><div class="table-responsive"><table class="table">
        <thead><tr><th>Name</th><th>Role</th><th>Joined</th></tr></thead>
        <tbody><?php foreach ($members as $m): ?>
            <tr><td><?= e($m['member_name']) ?></td><td><?= e($m['role']) ?></td><td><?= format_date($m['joined_date']) ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div></div></div>
</div>
