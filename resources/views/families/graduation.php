<div class="page-header">
    <h1>Annual Group Graduation</h1>
    <a href="<?= url('families') ?>" class="btn btn-outline-secondary">Back to Groups</a>
</div>

<div class="card mb-4">
    <div class="card-header">Graduation Rules</div>
    <div class="card-body">
        <ul class="mb-0">
            <li><strong>Young Peoples Guild</strong> (18–29) → graduates at age <strong>30</strong> to <strong>Young Adults Fellowship</strong></li>
            <li><strong>Young Adults Fellowship</strong> (30–39) → graduates at age <strong>40</strong> to <strong>Men's</strong> or <strong>Women's Fellowship</strong> (by gender)</li>
            <li><strong>Men's / Women's Fellowship</strong> (40+) — final groups, no further graduation</li>
        </ul>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Graduation Year</label>
                <input type="number" name="year" class="form-control" value="<?= (int) $graduationYear ?>" min="2000" max="2100">
            </div>
            <div class="col-md-5">
                <div class="form-check mt-4">
                    <input type="checkbox" name="only_this_year" value="1" class="form-check-input" id="onlyThisYear" <?= $onlyThisYear ? 'checked' : '' ?>>
                    <label class="form-check-label" for="onlyThisYear">
                        Only members who reached the age threshold during this year
                    </label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Review</button>
            </div>
        </form>
        <p class="text-muted small mt-3 mb-0">
            Reviewing members as of <strong><?= format_date($candidates['as_of']) ?></strong>.
            <?= $onlyThisYear ? 'Showing only those who turned 30 or 40 during ' . (int) $graduationYear . '.' : 'Showing all members currently above their group age limit.' ?>
        </p>
    </div>
</div>

<form method="POST" action="<?= url('families/graduation/process') ?>">
            <?= church_csrf_field() ?>
            <input type="hidden" name="graduation_year" value="<?= (int) $graduationYear ?>">
    <input type="hidden" name="only_this_year" value="<?= $onlyThisYear ? '1' : '0' ?>">

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Members Due for Graduation (<?= count($candidates['due']) ?>)</span>
            <?php if (!empty($candidates['due'])): ?>
            <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Graduate selected members to their next group?')">
                <i class="bi bi-arrow-up-circle"></i> Graduate Selected
            </button>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:40px">
                            <?php if (!empty($candidates['due'])): ?>
                            <input type="checkbox" class="form-check-input" id="selectAll">
                            <?php endif; ?>
                        </th>
                        <th>Name</th>
                        <th>Current Group</th>
                        <th>Age (as of year-end)</th>
                        <th>Threshold</th>
                        <th>Graduate To</th>
                        <th>Gender</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($candidates['due'])): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No members due for graduation with the selected criteria.</td></tr>
                    <?php else: foreach ($candidates['due'] as $m): ?>
                    <tr>
                        <td>
                            <?php if ($m['next_family_id']): ?>
                            <input type="checkbox" name="member_ids[]" value="<?= (int) $m['id'] ?>" class="form-check-input grad-check" checked>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold">
                            <a href="<?= url('members/' . $m['id']) ?>"><?= e(trim($m['first_name'] . ' ' . ($m['middle_name'] ? $m['middle_name'] . ' ' : '') . $m['last_name'])) ?></a>
                            <?php if ($m['membership_id']): ?><br><small class="text-muted"><code><?= e($m['membership_id']) ?></code></small><?php endif; ?>
                        </td>
                        <td><span class="badge bg-secondary"><?= e($m['family_name']) ?></span></td>
                        <td><?= (int) $m['age_as_of'] ?> yrs</td>
                        <td>Turned <?= (int) $m['threshold_age'] ?>+</td>
                        <td>
                            <?php if ($m['next_family_name']): ?>
                            <span class="badge bg-success"><?= e($m['next_family_name']) ?></span>
                            <?php else: ?>
                            <span class="text-danger">Cannot determine next group</span>
                            <?php endif; ?>
                        </td>
                        <td><?= ucfirst(e($m['gender'])) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<?php if (!empty($candidates['missing_dob'])): ?>
<div class="card mb-4 border-warning">
    <div class="card-header text-warning"><i class="bi bi-exclamation-triangle"></i> Missing Date of Birth (<?= count($candidates['missing_dob']) ?>)</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Name</th><th>Current Group</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($candidates['missing_dob'] as $m): ?>
                <tr>
                    <td><?= e($m['first_name'] . ' ' . $m['last_name']) ?></td>
                    <td><?= e($m['family_name']) ?></td>
                    <td><a href="<?= url('members/' . $m['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">Add DOB</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($recent)): ?>
<div class="card">
    <div class="card-header">Recent Graduations</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Member</th><th>From</th><th>To</th><th>Year</th><th>Age</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($recent as $g): ?>
                <tr>
                    <td><?= e($g['member_name']) ?></td>
                    <td><?= e($g['from_group']) ?></td>
                    <td><?= e($g['to_group']) ?></td>
                    <td><?= (int) $g['graduation_year'] ?></td>
                    <td><?= $g['member_age'] ? (int) $g['member_age'] . ' yrs' : '—' ?></td>
                    <td><?= format_date($g['graduated_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('selectAll')?.addEventListener('change', function () {
    document.querySelectorAll('.grad-check').forEach(cb => { cb.checked = this.checked; });
});
</script>
