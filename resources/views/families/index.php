<div class="page-header">
    <h1>Generational Groups</h1>
    <div>
        <a href="<?= url('families/graduation') ?>" class="btn btn-warning"><i class="bi bi-arrow-up-circle"></i> Annual Graduation</a>
        <a href="<?= url('families/create') ?>" class="btn btn-primary"><i class="bi bi-plus"></i> Add Group</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Age Group Rules</div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($rules as $name => $rule): ?>
            <div class="col-md-6 col-lg-3">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-2"><?= e($name) ?></h6>
                    <p class="mb-1 small">
                        <strong>Age:</strong>
                        <?= (int) $rule['min_age'] ?>–<?= $rule['max_age'] ? (int) $rule['max_age'] : '∞' ?> years
                    </p>
                    <p class="mb-1 small">
                        <strong>Gender:</strong> <?= ucfirst(e($rule['gender'])) ?>
                    </p>
                    <p class="mb-0 small text-muted">
                        <?php if (!empty($rule['next'])): ?>
                        Graduates to: <?= e($rule['next']) ?>
                        <?php elseif (!empty($rule['next_by_gender'])): ?>
                        Graduates to: Men → <?= e($rule['next_by_gender']['male']) ?>, Women → <?= e($rule['next_by_gender']['female']) ?>
                        <?php else: ?>
                        Final group (no further graduation)
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($families as $f): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5><i class="bi bi-house-heart"></i> <?= e($f['family_name']) ?></h5>
                <?php if ($f['min_age']): ?>
                <p class="small text-muted mb-2">
                    Ages <?= (int) $f['min_age'] ?>–<?= $f['max_age'] ? (int) $f['max_age'] : '∞' ?>
                    · <?= ucfirst(e($f['gender_scope'] ?? 'any')) ?>
                </p>
                <?php endif; ?>
                <p><i class="bi bi-people"></i> <?= (int) $f['member_count'] ?> members</p>
                <p><i class="bi bi-person"></i> Head: <?= e($f['head_name'] ?? 'Not set') ?></p>
                <p class="text-muted small mb-0"><?= e($f['city'] ?? '') ?> <?= e($f['phone'] ?? '') ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
