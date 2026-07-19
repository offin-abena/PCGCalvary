<div class="page-header">
    <h1><?= e($class['name']) ?></h1>
    <div>
        <a href="<?= url('youth/classes') ?>" class="btn btn-outline-secondary btn-sm">Manage Classes</a>
        <a href="<?= url('youth') ?>" class="btn btn-outline-secondary btn-sm">Back to Youth Ministry</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-value"><?= count($students) ?></div>
            <div class="stat-label">Enrolled Members</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-value text-capitalize"><?= e(str_replace('_', ' ', $class['ministry_type'])) ?></div>
            <div class="stat-label">Ministry Type</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body py-3">
                <p class="mb-1"><strong>Teacher:</strong> <?= e($class['teacher_name'] ?? 'Not assigned') ?></p>
                <p class="mb-1"><strong>Room:</strong> <?= e($class['room'] ?? 'TBD') ?></p>
                <p class="mb-0"><strong>Age Group:</strong> <?= e($class['age_group'] ?? 'All') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-plus"></i> Enroll Members</div>
            <div class="card-body">
                <?php if (empty($eligibleMembers)): ?>
                <p class="text-muted small mb-3">
                    No eligible members available. Add children under
                    <a href="<?= url($membersListUrl ?? 'members/children-service') ?>">Members</a>
                    first, or all eligible members are already in this class.
                </p>
                <?php else: ?>
                <form method="POST" action="<?= url('youth/enroll') ?>" id="enrollForm">
            <?= church_csrf_field() ?>
            <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                    <div class="mb-2">
                        <label class="form-label small">Search</label>
                        <input type="text" class="form-control form-control-sm" id="enrollSearch"
                            placeholder="Type a name to filter…" autocomplete="off">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Select member(s)</label>
                        <select name="child_member_ids[]" class="form-select" id="enrollSelect" multiple size="10" required>
                            <?php foreach ($eligibleMembers as $m): ?>
                            <?php
                                $label = trim($m['first_name'] . ' ' . ($m['middle_name'] ? $m['middle_name'] . ' ' : '') . $m['last_name']);
                                $meta = $m['membership_id'] ?: $m['member_code'];
                                if (!empty($m['enrolled_elsewhere'])) {
                                    $meta .= ' — in ' . $m['enrolled_elsewhere'];
                                }
                            ?>
                            <option value="<?= (int) $m['id'] ?>" data-search="<?= e(strtolower($label . ' ' . $meta)) ?>">
                                <?= e($label) ?> (<?= e($meta) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</small>
                    </div>
                    <?php if (!empty($eligibleMembers) && array_filter(array_column($eligibleMembers, 'enrolled_elsewhere'))): ?>
                    <p class="small text-warning mb-2">
                        Members listed with another class will be moved here automatically.
                    </p>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary w-100">Enroll Selected</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Roster</span>
                <span class="badge bg-secondary"><?= count($students) ?> enrolled</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Membership ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Date of Birth</th>
                            <th>Enrolled</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No members enrolled in this class yet</td></tr>
                        <?php else: foreach ($students as $s): ?>
                        <tr>
                            <td>
                                <?php if ($s['membership_id']): ?>
                                <code><?= e($s['membership_id']) ?></code>
                                <?php else: ?>
                                <small class="text-muted"><?= e($s['member_code']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold">
                                <?= e(trim($s['first_name'] . ' ' . ($s['middle_name'] ? $s['middle_name'] . ' ' : '') . $s['last_name'])) ?>
                            </td>
                            <td><?= ucfirst(e($s['gender'])) ?></td>
                            <td><?= format_date($s['date_of_birth']) ?></td>
                            <td><?= format_date($s['enrolled_date']) ?></td>
                            <td class="text-nowrap">
                                <a href="<?= url('members/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                <form method="POST" action="<?= url('youth/unenroll') ?>" class="d-inline"
                                    onsubmit="return confirm('Remove this member from the class?');">
            <?= church_csrf_field() ?>
            <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                    <input type="hidden" name="enrollment_id" value="<?= (int) $s['enrollment_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove from class">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($eligibleMembers)): ?>
<script>
(function () {
    const search = document.getElementById('enrollSearch');
    const select = document.getElementById('enrollSelect');
    if (!search || !select) return;

    search.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        Array.from(select.options).forEach(function (opt) {
            const hay = opt.dataset.search || opt.textContent.toLowerCase();
            opt.hidden = q !== '' && !hay.includes(q);
        });
    });
})();
</script>
<?php endif; ?>
