<div class="page-header">
    <h1>Financial Report</h1>
    <a href="<?= url('reports/export/finance') ?>" class="btn btn-success btn-sm"><i class="bi bi-download"></i> Export CSV</a>
</div>

<form method="GET" class="search-bar row g-2 align-items-end mb-4">
    <div class="col-auto">
        <label class="form-label small">From</label>
        <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
    </div>
    <div class="col-auto">
        <label class="form-label small">To</label>
        <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-value text-success"><?= format_money($summary['total_income']) ?></div>
            <div class="stat-label">Total Income</div>
            <small class="text-muted"><?= count($incomeRecords) ?> record(s)</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-value text-danger"><?= format_money($summary['total_expense']) ?></div>
            <div class="stat-label">Total Expenditure</div>
            <small class="text-muted"><?= count($expenseRecords) ?> record(s)</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-value"><?= format_money($summary['balance']) ?></div>
            <div class="stat-label">Net Balance</div>
            <small class="text-muted"><?= format_date($startDate) ?> — <?= format_date($endDate) ?></small>
        </div>
    </div>
</div>

<?php if (!empty($monthly)): ?>
<div class="card mb-4">
    <div class="card-header">Monthly Income vs Expenditure</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Month</th><th class="text-success">Income</th><th class="text-danger">Expenditure</th><th>Balance</th></tr></thead>
            <tbody>
                <?php foreach ($monthly as $m): ?>
                <tr>
                    <td><?= e(date('F Y', strtotime($m['month'] . '-01'))) ?></td>
                    <td class="text-success"><?= format_money($m['income']) ?></td>
                    <td class="text-danger"><?= format_money($m['expense']) ?></td>
                    <td class="fw-semibold"><?= format_money($m['income'] - $m['expense']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header text-success"><i class="bi bi-arrow-down-circle"></i> Income by Type</div>
            <div class="card-body">
                <?php if (empty($incomeByType) || array_sum(array_column($incomeByType, 'total')) == 0): ?>
                    <p class="text-muted text-center mb-0">No income recorded for this period</p>
                <?php else: foreach ($incomeByType as $item): if ($item['total'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= e($item['name']) ?></span>
                        <strong class="text-success"><?= format_money($item['total']) ?></strong>
                    </div>
                <?php endif; endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header text-danger"><i class="bi bi-arrow-up-circle"></i> Expenditure by Type</div>
            <div class="card-body">
                <?php if (empty($expenseByType) || array_sum(array_column($expenseByType, 'total')) == 0): ?>
                    <p class="text-muted text-center mb-0">No expenditure recorded for this period</p>
                <?php else: foreach ($expenseByType as $item): if ($item['total'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= e($item['name']) ?></span>
                        <strong class="text-danger"><?= format_money($item['total']) ?></strong>
                    </div>
                <?php endif; endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Income Records -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="text-success fw-semibold"><i class="bi bi-cash-stack"></i> Income</span>
        <span class="badge bg-success"><?= format_money($summary['total_income']) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Date</th><th>Financial Year</th><th>Type</th><th>Member</th><th>Amount</th><th>Method</th><th>Receipt No</th><th>Description</th></tr>
            </thead>
            <tbody>
                <?php if (empty($incomeRecords)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No income records for this period</td></tr>
                <?php else: foreach ($incomeRecords as $r): ?>
                <tr>
                    <td><?= format_date($r['transaction_date']) ?></td>
                    <td><?= e($r['financial_year_name'] ?? '—') ?></td>
                    <td><span class="badge bg-success"><?= e($r['type_name']) ?></span></td>
                    <td><?= e($r['member_name'] ?? '—') ?></td>
                    <td class="text-success fw-semibold"><?= format_money($r['amount']) ?></td>
                    <td><?= e(format_payment_method($r['payment_method'])) ?></td>
                    <td><code><?= e($r['receipt_number'] ?? '—') ?></code></td>
                    <td><?= e($r['description'] ?? '—') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($incomeRecords)): ?>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="4" class="text-end">Total Income</td>
                    <td class="text-success"><?= format_money($summary['total_income']) ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- Expenditure Records -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="text-danger fw-semibold"><i class="bi bi-wallet2"></i> Expenditure</span>
        <span class="badge bg-danger"><?= format_money($summary['total_expense']) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Date</th><th>Financial Year</th><th>Type</th><th>Amount</th><th>Method</th><th>Voucher No</th><th>Description</th></tr>
            </thead>
            <tbody>
                <?php if (empty($expenseRecords)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No expenditure records for this period</td></tr>
                <?php else: foreach ($expenseRecords as $r): ?>
                <tr>
                    <td><?= format_date($r['transaction_date']) ?></td>
                    <td><?= e($r['financial_year_name'] ?? '—') ?></td>
                    <td><span class="badge bg-danger"><?= e($r['type_name']) ?></span></td>
                    <td class="text-danger fw-semibold"><?= format_money($r['amount']) ?></td>
                    <td><?= e(format_payment_method($r['payment_method'])) ?></td>
                    <td><code><?= e($r['voucher_number'] ?? '—') ?></code></td>
                    <td><?= e($r['description'] ?? '—') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($expenseRecords)): ?>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="3" class="text-end">Total Expenditure</td>
                    <td class="text-danger"><?= format_money($summary['total_expense']) ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
