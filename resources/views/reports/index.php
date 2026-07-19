<div class="page-header"><h1>Reports & Analytics</h1></div>
<div class="row g-4">
    <?php $reports = [
        ['membership', 'bi-people', 'Membership Growth', 'Member statistics and growth trends'],
        ['attendance', 'bi-calendar-check', 'Attendance Analytics', 'Service attendance patterns'],
        ['financial', 'bi-cash-stack', 'Financial Report', 'Income, expenditure, and balances by date range'],
        ['income-expenditure', 'bi-bar-chart-line', 'Income & Expenditure', 'Summary by category for a financial year'],
        ['receipts-payments', 'bi-journal-text', 'Receipts & Payments', 'All receipts and voucher payments by financial year'],
        ['accounts-conversion', 'bi-arrow-left-right', 'Accounts Conversion', 'Receipts & Payments to Income & Expenditure and Financial Position'],
    ]; foreach ($reports as $r): ?>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body text-center">
            <i class="bi <?= $r[1] ?> fs-1 text-primary mb-3 d-block"></i>
            <h5><?= $r[2] ?></h5>
            <p class="text-muted"><?= $r[3] ?></p>
            <a href="<?= url('reports/'.$r[0]) ?>" class="btn btn-outline-primary">View Report</a>
        </div></div>
    </div>
    <?php endforeach; ?>
</div>
<div class="card mt-4"><div class="card-header">Export Data</div><div class="card-body d-flex gap-2">
    <a href="<?= url('reports/export/members') ?>" class="btn btn-success"><i class="bi bi-download"></i> Export Members (CSV)</a>
    <a href="<?= url('reports/export/finance') ?>" class="btn btn-success"><i class="bi bi-download"></i> Export Finance (CSV)</a>
    <a href="<?= url('reports/export/attendance') ?>" class="btn btn-success"><i class="bi bi-download"></i> Export Attendance (CSV)</a>
</div></div>
