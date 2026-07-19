<div class="page-header">
    <h1>Sermon Archive</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSermon">Add Sermon</button>
</div>
<div class="row g-4"><?php foreach ($sermons as $s): ?>
    <div class="col-md-4"><div class="card h-100"><div class="card-body">
        <h5><?= e($s['title']) ?></h5>
        <p class="text-muted"><i class="bi bi-person"></i> <?= e($s['preacher'] ?? 'Unknown') ?></p>
        <p><i class="bi bi-calendar"></i> <?= format_date($s['sermon_date']) ?></p>
        <p><i class="bi bi-book"></i> <?= e($s['scripture_reference'] ?? '') ?></p>
        <?php if ($s['audio_url']): ?><a href="<?= e($s['audio_url']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-play"></i> Audio</a><?php endif; ?>
    </div></div></div>
<?php endforeach; ?></div>

<div class="modal fade" id="addSermon"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="<?= url('documents/sermons/store') ?>">
            <?= church_csrf_field() ?>
            <div class="modal-header"><h5>Add Sermon</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-2"><input type="text" name="title" class="form-control" placeholder="Title" required></div>
            <div class="mb-2"><input type="text" name="preacher" class="form-control" placeholder="Preacher"></div>
            <div class="mb-2"><input type="date" name="sermon_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="mb-2"><input type="text" name="scripture_reference" class="form-control" placeholder="Scripture Reference"></div>
            <div class="mb-2"><textarea name="summary" class="form-control" placeholder="Summary" rows="3"></textarea></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
</div></div></div>
