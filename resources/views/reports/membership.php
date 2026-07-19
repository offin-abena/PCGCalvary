<div class="page-header"><h1>Membership Report</h1><a href="<?= url('reports/export/members') ?>" class="btn btn-success btn-sm">Export CSV</a></div>
<div class="row g-4 mb-4">
    <?php foreach ($stats as $key => $val): if (is_numeric($val)): ?>
    <div class="col-md-2"><div class="stat-card text-center"><div class="stat-value"><?= number_format($val) ?></div><div class="stat-label"><?= ucfirst(str_replace('_',' ',$key)) ?></div></div></div>
    <?php endif; endforeach; ?>
</div>
<div class="row g-4">
    <div class="col-md-6"><div class="card"><div class="card-header">By Status</div><div class="card-body"><?php foreach ($byStatus as $s): ?><div class="d-flex justify-content-between mb-2"><span><?= ucfirst(str_replace('_',' ',$s['membership_status'])) ?></span><strong><?= $s['count'] ?></strong></div><?php endforeach; ?></div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-header">By Gender</div><div class="card-body"><?php foreach ($byGender as $g): ?><div class="d-flex justify-content-between mb-2"><span><?= ucfirst($g['gender']) ?></span><strong><?= $g['count'] ?></strong></div><?php endforeach; ?></div></div></div>
</div>
