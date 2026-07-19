<?php
$ageGroupService = new \App\Services\AgeGroupService();
?>
<div class="page-header">

    <h1><?= e($title ?? 'Members') ?></h1>

    <a href="<?= url('members/create') ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add Member</a>

</div>



<div class="search-bar">

    <form method="GET" class="row g-2 align-items-end">

        <div class="col-md-3">

            <input type="text" name="search" class="form-control" placeholder="Search name, phone, membership ID..." value="<?= e($filters['search'] ?? '') ?>">

        </div>

        <div class="col-md-2">

            <select name="family_id" class="form-select">

                <option value="">All Generational Groups</option>

                <?php foreach ($families as $f): ?>

                <option value="<?= $f['id'] ?>" <?= ($filters['family_id'] ?? '') == $f['id'] ? 'selected' : '' ?>><?= e($f['family_name']) ?></option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="col-md-2">

            <select name="status" class="form-select">

                <option value="">All Statuses</option>

                <?php foreach (['active','inactive','visitor','new_convert','transferred','deceased'] as $s): ?>

                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="col-md-2">

            <select name="gender" class="form-select">

                <option value="">All Genders</option>

                <option value="male" <?= ($filters['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>

                <option value="female" <?= ($filters['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>

            </select>

        </div>

        <div class="col-md-2">

            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>

        </div>

    </form>

</div>



<div class="card">

    <div class="table-responsive">

        <table class="table table-hover">

            <thead>

                <tr><th></th><th>Membership Code</th><th>Name</th><th>Age Group</th><th>Generational Group</th><th>Phone</th><th>Status</th><th></th></tr>

            </thead>

            <tbody>

                <?php if (empty($members['data'])): ?>

                <tr><td colspan="8" class="text-center text-muted py-4">No members found</td></tr>

                <?php else: foreach ($members['data'] as $m): ?>

                <tr>

                    <td>

                        <?php if ($m['photo']): ?>

                            <img src="<?= upload_url($m['photo']) ?>" class="member-avatar" alt="">

                        <?php else: ?>

                            <div class="avatar-placeholder"><?= strtoupper(substr($m['first_name'],0,1).substr($m['last_name'],0,1)) ?></div>

                        <?php endif; ?>

                    </td>

                    <td>
                        <?php if (!empty($m['membership_id'])): ?>
                            <code><?= e($m['membership_id']) ?></code>
                            <?php if (!empty($m['member_code'])): ?>
                            <br><small class="text-muted"><?= e($m['member_code']) ?></small>
                            <?php endif; ?>
                        <?php elseif (!empty($m['member_code'])): ?>
                            <code><?= e($m['member_code']) ?></code>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>

                    <td class="fw-semibold"><?= e(trim($m['first_name'] . ' ' . ($m['middle_name'] ? $m['middle_name'] . ' ' : '') . $m['last_name'])) ?></td>

                    <td>
                        <?php
                        $groupKey = $ageGroupService->resolveGroupKey(
                            $m['date_of_birth'] ?? null,
                            null,
                            $m['family_name'] ?? null
                        );
                        if ($groupKey):
                        ?>
                        <span class="badge bg-secondary"><?= e($ageGroupService->getLabel($groupKey)) ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($m['family_name']): ?>
                        <span class="badge bg-light text-dark border"><?= e($m['family_name']) ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </td>

                    <td><?= e($m['phone'] ?? '-') ?></td>

                    <td><?= status_badge($m['membership_status']) ?></td>

                    <td>

                        <a href="<?= url('members/' . $m['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>

                        <a href="<?= url('members/' . $m['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>

                    </td>

                </tr>

                <?php endforeach; endif; ?>

            </tbody>

        </table>

    </div>

    <?php if ($members['total_pages'] > 1): ?>

    <div class="card-footer d-flex justify-content-center">

        <?php for ($i = 1; $i <= $members['total_pages']; $i++): ?>

            <a href="?page=<?= $i ?>&search=<?= e($filters['search'] ?? '') ?>&status=<?= e($filters['status'] ?? '') ?>&gender=<?= e($filters['gender'] ?? '') ?>&family_id=<?= e($filters['family_id'] ?? '') ?>"
               class="btn btn-sm <?= $i === $members['page'] ? 'btn-primary' : 'btn-outline-secondary' ?> mx-1"><?= $i ?></a>

        <?php endfor; ?>

    </div>

    <?php endif; ?>

</div>

