<div class="page-header">

    <h1>Children & Youth Ministry</h1>

    <a href="<?= url('youth/classes') ?>" class="btn btn-outline-primary"><i class="bi bi-gear"></i> Manage Classes</a>

</div>



<?php if (empty($classes)): ?>

<div class="alert alert-info">No children or junior youth classes configured yet.</div>

<?php else: ?>

<div class="row g-4">

    <?php foreach ($classes as $c): ?>

    <div class="col-md-6 col-lg-4">

        <div class="card h-100">

            <div class="card-body">

                <h5 class="card-title"><?= e($c['name']) ?></h5>

                <span class="badge bg-<?= $c['ministry_type'] === 'children' ? 'info' : 'warning' ?>">

                    <?= e(str_replace('_', ' ', ucfirst($c['ministry_type']))) ?>

                </span>

                <p class="mt-3 mb-1"><i class="bi bi-people"></i> <strong><?= (int) $c['student_count'] ?></strong> enrolled</p>

                <p class="mb-1"><i class="bi bi-person"></i> <?= e($c['teacher_name'] ?? 'No teacher assigned') ?></p>

                <p class="mb-1"><i class="bi bi-door-open"></i> Room: <?= e($c['room'] ?? 'TBD') ?></p>

                <p class="text-muted mb-3">Age: <?= e($c['age_group'] ?? 'All') ?></p>

                <a href="<?= url('youth/classes/' . $c['id']) ?>" class="btn btn-sm btn-primary">View Roster</a>

            </div>

        </div>

    </div>

    <?php endforeach; ?>

</div>

<?php endif; ?>

