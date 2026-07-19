<div class="page-header">
    <h1>Receipts & Payments Report</h1>
    <?php if (!empty($financialYear)):
        $exportBase = 'reports/receipts-payments';
        include __DIR__ . '/_report_export_buttons.php';
    endif; ?>
</div>

<?php
$reportPath = 'reports/receipts-payments';
include __DIR__ . '/_financial_year_selector.php';
?>

<?php if (empty($financialYear)): ?>
<div class="alert alert-warning">Please configure a financial year in Settings before generating this report.</div>
<?php else:

$receiptCount = array_sum(array_map(fn($r) => (int) $r['record_count'], $incomeByType));
$paymentCount = array_sum(array_map(fn($r) => (int) $r['record_count'], $expenseByType));
?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-value text-success"><?= format_money($summary['total_income']) ?></div>
            <div class="stat-label">Total Receipts</div>
            <small class="text-muted"><?= $receiptCount ?> record(s)</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-value text-danger"><?= format_money($summary['total_expense']) ?></div>
            <div class="stat-label">Total Payments</div>
            <small class="text-muted"><?= $paymentCount ?> record(s)</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-value"><?= format_money($summary['balance']) ?></div>
            <div class="stat-label">Net Receipts − Payments</div>
        </div>
    </div>
</div>

<div class="d-flex flex-column gap-4">
    <div class="card">
        <div class="card-header text-success fw-semibold"><i class="bi bi-receipt"></i> Receipts</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Category</th><th class="text-end">Records</th><th class="text-end">Amount</th></tr>
                </thead>
                <tbody>
                    <?php
                    $receiptRows = array_filter($incomeByType, fn($r) => $r['total'] > 0);
                    if (empty($receiptRows)):
                    ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">No receipts recorded</td></tr>
                    <?php else: foreach ($receiptRows as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td class="text-end"><?= (int) $row['record_count'] ?></td>
                        <td class="text-end text-success fw-semibold"><?= format_money($row['total']) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td>Total Receipts</td>
                        <td></td>
                        <td class="text-end text-success"><?= format_money($summary['total_income']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header text-danger fw-semibold"><i class="bi bi-wallet2"></i> Payments</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Category</th><th class="text-end">Records</th><th class="text-end">Amount</th></tr>
                </thead>
                <tbody>
                    <?php
                    $paymentRows = array_filter($expenseByType, fn($r) => $r['total'] > 0);
                    if (empty($paymentRows)):
                    ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">No payments recorded</td></tr>
                    <?php else: foreach ($paymentRows as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td class="text-end"><?= (int) $row['record_count'] ?></td>
                        <td class="text-end text-danger fw-semibold"><?= format_money($row['total']) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td>Total Payments</td>
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
            <span>Net Receipts − Payments</span>
            <span class="<?= $summary['balance'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_money($summary['balance']) ?></span>
        </div>
    </div>
</div>

<?php endif; ?>
