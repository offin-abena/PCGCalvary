<div class="page-header">
    <h1>Income & Expenditure Report</h1>
    <?php if (!empty($financialYear)):
        $exportBase = 'reports/income-expenditure';
        include __DIR__ . '/_report_export_buttons.php';
    endif; ?>
</div>

<?php
$reportPath = 'reports/income-expenditure';
include __DIR__ . '/_financial_year_selector.php';
?>

<?php if (empty($financialYear)): ?>
<div class="alert alert-warning">Please configure a financial year in Settings before generating this report.</div>
<?php else: ?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-value text-success"><?= format_money($summary['total_income']) ?></div>
            <div class="stat-label">Total Income</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-value text-danger"><?= format_money($summary['total_expense']) ?></div>
            <div class="stat-label">Total Expenditure</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-value <?= $summary['balance'] >= 0 ? 'text-primary' : 'text-warning' ?>">
                <?= format_money($summary['balance']) ?>
            </div>
            <div class="stat-label"><?= $summary['balance'] >= 0 ? 'Surplus' : 'Deficit' ?></div>
        </div>
    </div>
</div>

<?php if (!empty($monthly)): ?>
<div class="card mb-4">
    <div class="card-header">Monthly Summary</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-end text-success">Income</th>
                    <th class="text-end text-danger">Expenditure</th>
                    <th class="text-end">Surplus / (Deficit)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthly as $m): ?>
                <tr>
                    <td><?= e(date('F Y', strtotime($m['month'] . '-01'))) ?></td>
                    <td class="text-end text-success"><?= format_money($m['income']) ?></td>
                    <td class="text-end text-danger"><?= format_money($m['expense']) ?></td>
                    <td class="text-end fw-semibold"><?= format_money($m['income'] - $m['expense']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="d-flex flex-column gap-4">
    <div class="card">
            <div class="card-header text-success fw-semibold"><i class="bi bi-arrow-down-circle"></i> Income</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Category</th><th class="text-end">Records</th><th class="text-end">Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $incomeRows = array_filter($incomeByType, fn($r) => $r['total'] > 0);
                        if (empty($incomeRows)):
                        ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">No income recorded</td></tr>
                        <?php else: foreach ($incomeRows as $row): ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td class="text-end"><?= (int) $row['record_count'] ?></td>
                            <td class="text-end text-success fw-semibold"><?= format_money($row['total']) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td>Total Income</td>
                            <td></td>
                            <td class="text-end text-success"><?= format_money($summary['total_income']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <div class="card">
            <div class="card-header text-danger fw-semibold"><i class="bi bi-arrow-up-circle"></i> Expenditure</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Category</th><th class="text-end">Records</th><th class="text-end">Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $expenseRows = array_filter($expenseByType, fn($r) => $r['total'] > 0);
                        if (empty($expenseRows)):
                        ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">No expenditure recorded</td></tr>
                        <?php else: foreach ($expenseRows as $row): ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td class="text-end"><?= (int) $row['record_count'] ?></td>
                            <td class="text-end text-danger fw-semibold"><?= format_money($row['total']) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td>Total Expenditure</td>
                            <td></td>
                            <td class="text-end text-danger"><?= format_money($summary['total_expense']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center fw-bold fs-5">
            <span><?= $summary['balance'] >= 0 ? 'Surplus for the Year' : 'Deficit for the Year' ?></span>
            <span class="<?= $summary['balance'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_money($summary['balance']) ?></span>
        </div>
    </div>
</div>

<?php endif; ?>
