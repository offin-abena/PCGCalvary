<div class="page-header"><h1>Announcements</h1></div>
<div class="row g-4">
    <div class="col-lg-4"><div class="card"><div class="card-header">New Announcement</div><div class="card-body">
        <form method="POST" action="<?= url('communication/announcements/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-2"><input type="text" name="title" class="form-control" placeholder="Title" required></div>
            <div class="mb-2"><textarea name="content" class="form-control" placeholder="Content" rows="4" required></textarea></div>
            <div class="mb-2"><select name="priority" class="form-select"><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
            <div class="form-check mb-2"><input type="checkbox" name="is_published" value="1" class="form-check-input" id="pub" checked><label for="pub">Publish Now</label></div>
            <button type="submit" class="btn btn-primary w-100">Post</button>
        </form>
    </div></div>
    <div class="col-lg-8"><?php foreach ($announcements as $a): ?>
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between"><h5><?= e($a['title']) ?></h5><span class="badge bg-<?= $a['priority'] === 'urgent' ? 'danger' : 'secondary' ?>"><?= e($a['priority']) ?></span></div>
            <p><?= nl2br(e($a['content'])) ?></p>
            <small class="text-muted">By <?= e($a['author'] ?? 'System') ?> - <?= format_date($a['created_at']) ?></small>
        </div></div>
    <?php endforeach; ?></div>
</div>
