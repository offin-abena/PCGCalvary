<?php include __DIR__ . '/_financial_year_banner.php'; ?>

<div class="page-header">
    <h1>Financial Management</h1>
</div>

<!-- Income & Expenditure -->
<div class="mb-2"><h5 class="text-muted"><i class="bi bi-arrow-left-right"></i> Income & Expenditure</h5></div>
<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-value text-success"><?= format_money($summary['total_income']) ?></div><div class="stat-label">Total Income</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value text-danger"><?= format_money($summary['total_expense']) ?></div><div class="stat-label">Total Expenditure</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= format_money($summary['balance']) ?></div><div class="stat-label">Net Balance</div></div></div>
</div>

<div class="row g-4 mb-5">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header text-success"><i class="bi bi-arrow-down-circle"></i> Income</div>
            <div class="card-body">
                <p class="text-muted small">Record tithes, offerings, and other church income.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= url('finance/income') ?>" class="btn btn-success btn-sm">Record Income</a>
                    <a href="<?= url('finance/tithe-reminders') ?>" class="btn btn-outline-success btn-sm">Tithe SMS</a>
                    <a href="<?= url('finance/sms-logs') ?>" class="btn btn-outline-secondary btn-sm">SMS Logs</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header text-danger"><i class="bi bi-arrow-up-circle"></i> Expense</div>
            <div class="card-body">
                <p class="text-muted small">Record church expenditures and outgoing payments.</p>
                <a href="<?= url('finance/expense') ?>" class="btn btn-danger btn-sm">Record Expense</a>
            </div>
        </div>
    </div>
</div>

<!-- Assets & Liabilities -->
<div class="mb-2"><h5 class="text-muted"><i class="bi bi-bank"></i> Assets & Liabilities</h5></div>
<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-value text-primary"><?= format_money($assetsLiabilities['total_assets']) ?></div><div class="stat-label">Total Assets</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value text-danger"><?= format_money($assetsLiabilities['total_liabilities']) ?></div><div class="stat-label">Total Liabilities</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= format_money($assetsLiabilities['net_worth']) ?></div><div class="stat-label">Net Worth</div></div></div>
</div>

<div class="row g-4 mb-5">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header text-primary"><i class="bi bi-building"></i> Assets</div>
            <div class="card-body">
                <p class="text-muted small">Record balance sheet assets such as cash, property, and investments.</p>
                <a href="<?= url('finance/assets') ?>" class="btn btn-primary btn-sm">Record Assets</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header text-warning"><i class="bi bi-journal-minus"></i> Liabilities</div>
            <div class="card-body">
                <p class="text-muted small">Record loans, payables, and other church liabilities.</p>
                <a href="<?= url('finance/liabilities') ?>" class="btn btn-warning btn-sm">Record Liabilities</a>
            </div>
        </div>
    </div>
</div>

<!-- Other Finance Tools -->
<div class="mb-2"><h5 class="text-muted"><i class="bi bi-tools"></i> Other Tools</h5></div>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Quick Links</div>
            <div class="list-group list-group-flush">
                <a href="<?= url('finance/pledges') ?>" class="list-group-item list-group-item-action"><i class="bi bi-hand-thumbs-up"></i> Pledge Management</a>
                <a href="<?= url('finance/budgets') ?>" class="list-group-item list-group-item-action"><i class="bi bi-piggy-bank"></i> Budget Management</a>
                <a href="<?= url('finance/payroll') ?>" class="list-group-item list-group-item-action"><i class="bi bi-wallet2"></i> Payroll</a>
                <a href="<?= url('finance/reconciliation') ?>" class="list-group-item list-group-item-action"><i class="bi bi-bank2"></i> Bank Reconciliation</a>
                <a href="<?= url('reports/income-expenditure') ?>" class="list-group-item list-group-item-action"><i class="bi bi-bar-chart-line"></i> Income & Expenditure Report</a>
                <a href="<?= url('reports/receipts-payments') ?>" class="list-group-item list-group-item-action"><i class="bi bi-journal-text"></i> Receipts & Payments Report</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Income by Type</div>
            <div class="card-body">
                <?php foreach ($incomeByType as $item): if ($item['total'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= e($item['name']) ?></span>
                        <strong><?= format_money($item['total']) ?></strong>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        </div>
    </div>
</div>
