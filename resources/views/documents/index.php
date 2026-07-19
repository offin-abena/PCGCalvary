<div class="page-header">
    <h1>Documents</h1>
    <div>
        <a href="<?= url('documents/sermons') ?>" class="btn btn-outline-primary btn-sm">Sermons</a>
        <a href="<?= url('documents/upload') ?>" class="btn btn-primary btn-sm">Upload</a>
    </div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Title</th><th>Type</th><th>Size</th><th>Uploaded By</th><th>Date</th><th></th></tr></thead>
    <tbody><?php foreach ($documents as $d): ?>
        <tr><td><?= e($d['title']) ?></td><td><span class="badge bg-secondary"><?= e($d['document_type']) ?></span></td><td><?= round(($d['file_size'] ?? 0) / 1024, 1) ?> KB</td><td><?= e($d['uploaded_by_name'] ?? '-') ?></td><td><?= format_date($d['created_at']) ?></td><td><a href="<?= url('documents/download/'.$d['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
