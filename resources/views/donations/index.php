<div class="page-header">
    <h1>Online Donations</h1>
    <div>
        <a href="<?= url('donations/campaigns') ?>" class="btn btn-outline-primary btn-sm">Campaigns</a>
        <a href="<?= url('donations/give') ?>" class="btn btn-accent btn-sm" target="_blank">Giving Portal</a>
    </div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Date</th><th>Donor</th><th>Type</th><th>Amount</th><th>Campaign</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($donations as $d): ?>
        <tr><td><?= format_date($d['donation_date']) ?></td><td><?= e($d['member_name'] ?? 'Anonymous') ?></td><td><?= e($d['type_name']) ?></td><td class="fw-bold text-success"><?= format_money($d['amount']) ?></td><td><?= e($d['campaign_name'] ?? '-') ?></td><td><?= status_badge($d['status']) ?></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
