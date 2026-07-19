<div class="page-header"><h1>Payroll Management</h1></div>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>Employee</th><th>Period</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($payroll as $p): ?>
        <tr><td><?= e($p['member_name']) ?></td><td><?= format_date($p['pay_period_start']) ?> - <?= format_date($p['pay_period_end']) ?></td><td><?= format_money($p['gross_amount']) ?></td><td><?= format_money($p['deductions']) ?></td><td class="fw-bold"><?= format_money($p['net_amount']) ?></td><td><?= status_badge($p['status']) ?></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
