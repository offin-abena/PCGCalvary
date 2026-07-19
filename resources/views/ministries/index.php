<div class="page-header">
    <h1>Ministries & Groups</h1>
    <a href="<?= url('ministries/create') ?>" class="btn btn-primary"><i class="bi bi-plus"></i> Create Ministry</a>
</div>
<div class="row g-4"><?php foreach ($ministries as $m): ?>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h5><?= e($m['name']) ?></h5>
            <span class="badge bg-secondary"><?= e(str_replace('_',' ',$m['ministry_type'])) ?></span>
            <p class="text-muted mt-2 small"><?= e($m['description'] ?? '') ?></p>
            <p><i class="bi bi-person"></i> <?= e($m['leader_name'] ?? 'No leader') ?></p>
            <p><i class="bi bi-people"></i> <?= $m['member_count'] ?> members</p>
            <?php if ($m['meeting_day']): ?><p><i class="bi bi-clock"></i> <?= e($m['meeting_day']) ?> <?= e($m['meeting_time'] ?? '') ?></p><?php endif; ?>
            <a href="<?= url('ministries/'.$m['id']) ?>" class="btn btn-sm btn-outline-primary">Manage</a>
        </div></div>
    </div>
<?php endforeach; ?></div>
