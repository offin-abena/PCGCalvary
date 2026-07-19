<div class="page-header"><h1>Fundraising Campaigns</h1></div>
<div class="row g-4">
    <div class="col-lg-4"><div class="card"><div class="card-header">New Campaign</div><div class="card-body">
        <form method="POST" action="<?= url('donations/campaigns/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Campaign Name" required></div>
            <div class="mb-2"><textarea name="description" class="form-control" placeholder="Description" rows="3"></textarea></div>
            <div class="mb-2"><input type="number" name="target_amount" class="form-control" placeholder="Target Amount"></div>
            <div class="mb-2"><input type="date" name="start_date" class="form-control"></div>
            <div class="mb-2"><input type="date" name="end_date" class="form-control"></div>
            <button type="submit" class="btn btn-primary w-100">Create</button>
        </form>
    </div></div>
    <div class="col-lg-8"><?php foreach ($campaigns as $c): ?>
        <div class="card mb-3"><div class="card-body">
            <h5><?= e($c['name']) ?></h5>
            <p class="text-muted"><?= e($c['description'] ?? '') ?></p>
            <?php if ($c['target_amount']): ?>
                <?php $pct = $c['target_amount'] > 0 ? min(100, ($c['amount_raised'] / $c['target_amount']) * 100) : 0; ?>
                <div class="progress mb-2"><div class="progress-bar" style="width: <?= $pct ?>%"></div></div>
                <small><?= format_money($c['amount_raised']) ?> of <?= format_money($c['target_amount']) ?> (<?= round($pct) ?>%)</small>
            <?php endif; ?>
        </div></div>
    <?php endforeach; ?></div>
</div>
