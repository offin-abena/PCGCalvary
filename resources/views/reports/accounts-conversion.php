<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h1 class="mb-0">Accounts Conversion Report</h1>
    <?php if (!empty($financialYear)):
        $exportBase = 'reports/accounts-conversion';
        include __DIR__ . '/_report_export_buttons.php';
    endif; ?>
</div>

<p class="text-muted">
    Converts Receipts &amp; Payments into an accrual-based Income &amp; Expenditure Account and Statement of Financial Position.
    Capital items are excluded from I&amp;E and posted to the balance sheet.
</p>

<?php
$reportPath = 'reports/accounts-conversion';
include __DIR__ . '/_financial_year_selector.php';
?>

<?php if (!empty($financialYears)): ?>
<form method="GET" action="<?= url($reportPath) ?>" class="search-bar row g-2 align-items-end mb-4">
    <input type="hidden" name="financial_year_id" value="<?= (int) ($financialYear['id'] ?? 0) ?>">
    <div class="col-md-4">
        <label class="form-label small">Compare with (optional)</label>
        <select name="compare_year_id" class="form-select" onchange="this.form.submit()">
            <option value="">— None —</option>
            <?php foreach ($financialYears as $fy): ?>
            <?php if (($financialYear['id'] ?? 0) == $fy['id']) continue; ?>
            <option value="<?= $fy['id'] ?>" <?= ($compareYearId ?? 0) == $fy['id'] ? 'selected' : '' ?>>
                <?= e($fy['name']) ?> (<?= e($fy['year_label']) ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
<?php endif; ?>

<?php if (empty($financialYear)): ?>
<div class="alert alert-warning">Please configure a financial year in Settings before generating this report.</div>
<?php elseif (!empty($error)): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php elseif (empty($report)): ?>
<div class="alert alert-info">Unable to build report.</div>
<?php else: ?>

<?php $ie = $report['income_expenditure']; $fp = $report['financial_position']; ?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-value text-success"><?= format_money($ie['total_income']) ?></div>
            <div class="stat-label">Total Income (Adjusted)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-value text-danger"><?= format_money($ie['total_expenditure']) ?></div>
            <div class="stat-label">Total Expenditure (Adjusted)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-value <?= $ie['surplus'] >= 0 ? 'text-primary' : 'text-warning' ?>">
                <?= format_money($ie['result_amount']) ?>
            </div>
            <div class="stat-label"><?= e($ie['result_label']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-value"><?= format_money($fp['accumulated_fund']['closing']) ?></div>
            <div class="stat-label">Accumulated Fund (Closing)</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-shield-check"></i> AI Validation Rules</div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <?php foreach ($report['validations'] as $v): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div>
                    <strong><?= e($v['rule']) ?></strong>
                    <div class="small text-muted"><?= e($v['detail']) ?></div>
                </div>
                <span class="badge bg-<?= $v['passed'] ? 'success' : 'danger' ?>"><?= $v['passed'] ? 'Pass' : 'Fail' ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Financial Ratios &amp; Dashboard</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach ($report['ratios'] as $ratio): ?>
                        <tr>
                            <td><?= e($ratio['label']) ?></td>
                            <td class="text-end fw-semibold"><?= is_numeric($ratio['value']) ? format_money($ratio['value']) : e($ratio['value']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Receipts &amp; Payments Summary</div>
            <div class="card-body small">
                <p class="mb-1">Total Receipts: <strong class="text-success"><?= format_money($report['receipts_payments']['total_receipts']) ?></strong></p>
                <p class="mb-1">Total Payments: <strong class="text-danger"><?= format_money($report['receipts_payments']['total_payments']) ?></strong></p>
                <p class="mb-0">Net Cash Movement: <strong><?= format_money($report['receipts_payments']['net_cash']) ?></strong></p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-gear"></i> Period Balances &amp; Accrual Settings</span>
        <span class="small text-muted">Required for cash validation and accrual adjustments</span>
    </div>
    <div class="card-body">
        <?php if (!empty($suggestedBalances)): ?>
        <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="small">
                <strong>Suggested closing balances</strong>
                (<?= e($suggestedBalances['closing_source']) ?>):
                Cash <?= format_money($suggestedBalances['closing_cash']) ?>,
                Bank <?= format_money($suggestedBalances['closing_bank']) ?>.
                <?= !empty($suggestedBalances['closing_detail']) ? e($suggestedBalances['closing_detail']) : '' ?>
            </div>
            <form method="POST" action="<?= url('reports/accounts-conversion/suggest') ?>" class="mb-0">
            <?= church_csrf_field() ?>
            <input type="hidden" name="financial_year_id" value="<?= (int) $financialYear['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary">Apply Suggested Balances</button>
            </form>
        </div>
        <?php endif; ?>
        <?php if (!empty($fp['closing_source']) && empty($suggestedBalances)): ?>
        <p class="small text-muted mb-3">
            Closing balances source: <strong><?= e($fp['closing_source']) ?></strong>.
            <?= !empty($fp['closing_detail']) ? e($fp['closing_detail']) : '' ?>
        </p>
        <?php endif; ?>
        <form method="POST" action="<?= url('reports/accounts-conversion/settings') ?>" id="conversionSettingsForm">
            <?= church_csrf_field() ?>
            <input type="hidden" name="financial_year_id" value="<?= (int) $financialYear['id'] ?>">
            <input type="hidden" name="accruals_json" id="accrualsJson" value="">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Opening Cash</label>
                    <input type="number" step="0.01" name="opening_cash" class="form-control" value="<?= e($fp['opening_cash'] ?? 0) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Opening Bank</label>
                    <input type="number" step="0.01" name="opening_bank" class="form-control" value="<?= e($fp['opening_bank'] ?? 0) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Opening Accumulated Fund</label>
                    <input type="number" step="0.01" name="opening_accumulated_fund" class="form-control" value="<?= e($fp['accumulated_fund']['opening'] ?? 0) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Closing Cash</label>
                    <input type="number" step="0.01" name="closing_cash" class="form-control"
                        value="<?= ($financialYear['closing_cash'] ?? null) !== null ? e($financialYear['closing_cash']) : '' ?>"
                        placeholder="<?= format_money($fp['closing_cash'] ?? 0) ?> (auto)">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Closing Bank</label>
                    <input type="number" step="0.01" name="closing_bank" class="form-control"
                        value="<?= ($financialYear['closing_bank'] ?? null) !== null ? e($financialYear['closing_bank']) : '' ?>"
                        placeholder="<?= format_money($fp['closing_bank'] ?? 0) ?> (auto)">
                </div>
            </div>
            <p class="small text-muted mb-2">Accrual adjustments per category (outstanding = receivable/payable; prepaid = advance payments).</p>
            <div class="table-responsive mb-3">
                <table class="table table-sm" id="accrualTable">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Side</th>
                            <th class="text-end">Outstanding (End)</th>
                            <th class="text-end">Outstanding (Begin)</th>
                            <th class="text-end">Prepaid (End)</th>
                            <th class="text-end">Prepaid (Begin)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['accrual_schedule'] as $row): ?>
                        <tr data-category="<?= e($row['category']) ?>" data-side="<?= e($row['side']) ?>">
                            <td><?= e($row['category']) ?></td>
                            <td><?= e(ucfirst($row['side'])) ?></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end acc-out-end" value="<?= e($row['outstanding_end'] ?? 0) ?>"></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end acc-out-begin" value="<?= e($row['outstanding_begin'] ?? 0) ?>"></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end acc-prep-end" value="<?= e($row['prepaid_end'] ?? 0) ?>"></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end acc-prep-begin" value="<?= e($row['prepaid_begin'] ?? 0) ?>"></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary">Save Settings &amp; Recalculate</button>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header text-success fw-semibold">Income &amp; Expenditure — Income</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Category</th><th class="text-end">Cash</th><th class="text-end">Adjusted</th></tr></thead>
                    <tbody>
                        <?php foreach ($ie['income']['lines'] as $line): ?>
                        <tr>
                            <td><?= e($line['category']) ?></td>
                            <td class="text-end"><?= format_money($line['cash_amount']) ?></td>
                            <td class="text-end fw-semibold text-success"><?= format_money($line['adjusted_amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td>Total Income</td><td></td>
                            <td class="text-end text-success"><?= format_money($ie['total_income']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header text-danger fw-semibold">Income &amp; Expenditure — Expenditure</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Category</th><th class="text-end">Cash</th><th class="text-end">Adjusted</th></tr></thead>
                    <tbody>
                        <?php foreach ($ie['expenditure']['lines'] as $line): ?>
                        <tr>
                            <td><?= e($line['category']) ?><?= !empty($line['is_depreciation']) ? ' <span class="badge bg-secondary">Depreciation</span>' : '' ?></td>
                            <td class="text-end"><?= format_money($line['cash_amount'] ?? 0) ?></td>
                            <td class="text-end fw-semibold text-danger"><?= format_money($line['adjusted_amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td>Total Expenditure</td><td></td>
                            <td class="text-end text-danger"><?= format_money($ie['total_expenditure']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header fw-semibold"><?= e($ie['result_label']) ?>: <?= format_money($ie['result_amount']) ?></div>
</div>

<div class="card mb-4">
    <div class="card-header">Statement of Financial Position</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Assets</h6>
                <table class="table table-sm">
                    <?php foreach ($fp['assets'] as $a): ?>
                    <tr><td><?= e($a['label']) ?></td><td class="text-end"><?= format_money($a['amount']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold"><td>Total Assets</td><td class="text-end"><?= format_money($fp['total_assets']) ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Liabilities</h6>
                <table class="table table-sm">
                    <?php if (empty($fp['liabilities'])): ?>
                    <tr><td colspan="2" class="text-muted">No liabilities recorded</td></tr>
                    <?php else: foreach ($fp['liabilities'] as $l): ?>
                    <tr><td><?= e($l['label']) ?></td><td class="text-end"><?= format_money($l['amount']) ?></td></tr>
                    <?php endforeach; endif; ?>
                    <tr class="fw-bold"><td>Total Liabilities</td><td class="text-end"><?= format_money($fp['total_liabilities']) ?></td></tr>
                </table>
                <h6 class="mt-3">Accumulated Fund</h6>
                <table class="table table-sm">
                    <tr><td>Opening Fund</td><td class="text-end"><?= format_money($fp['accumulated_fund']['opening']) ?></td></tr>
                    <?php if ($fp['accumulated_fund']['surplus'] > 0): ?>
                    <tr><td>Add: Surplus for the Year</td><td class="text-end"><?= format_money($fp['accumulated_fund']['surplus']) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($fp['accumulated_fund']['deficit'] > 0): ?>
                    <tr><td>Less: Deficit for the Year</td><td class="text-end">(<?= format_money($fp['accumulated_fund']['deficit']) ?>)</td></tr>
                    <?php endif; ?>
                    <?php if ($fp['accumulated_fund']['capital_donations'] > 0): ?>
                    <tr><td>Add: Capital Donations</td><td class="text-end"><?= format_money($fp['accumulated_fund']['capital_donations']) ?></td></tr>
                    <?php endif; ?>
                    <tr class="fw-bold"><td>Closing Fund</td><td class="text-end"><?= format_money($fp['accumulated_fund']['closing']) ?></td></tr>
                </table>
                <p class="small mb-0 <?= $fp['balanced'] ? 'text-success' : 'text-danger' ?>">
                    Total Liabilities + Fund: <?= format_money($fp['total_liabilities_and_fund']) ?>
                    <?= $fp['balanced'] ? '(Balanced)' : '(Out of balance — review assets, liabilities, and fund)' ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Capital Movements (Balance Sheet Only)</div>
            <div class="card-body small">
                <p>Capital Receipts: <strong><?= format_money($report['capital_movements']['total_receipts']) ?></strong></p>
                <p>Capital Payments: <strong><?= format_money($report['capital_movements']['total_payments']) ?></strong></p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Depreciation Schedule</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Asset</th><th>Rate</th><th class="text-end">Depreciation</th><th class="text-end">Net Book</th></tr></thead>
                    <tbody>
                        <?php if (empty($report['depreciation_schedule']['lines'])): ?>
                        <tr><td colspan="4" class="text-muted text-center">No fixed assets for this year</td></tr>
                        <?php else: foreach ($report['depreciation_schedule']['lines'] as $d): ?>
                        <tr>
                            <td><?= e($d['name']) ?> <span class="text-muted">(<?= e($d['category']) ?>)</span></td>
                            <td><?= e($d['rate_percent']) ?>%</td>
                            <td class="text-end"><?= format_money($d['depreciation_amount']) ?></td>
                            <td class="text-end"><?= format_money($d['net_book_value']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="fw-bold">
                            <td colspan="2">Total</td>
                            <td class="text-end"><?= format_money($report['depreciation_schedule']['total_depreciation']) ?></td>
                            <td class="text-end"><?= format_money($report['depreciation_schedule']['total_net_book_value']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($report['flags'])): ?>
<div class="card mb-4 border-warning">
    <div class="card-header text-warning"><i class="bi bi-exclamation-triangle"></i> Transactions Flagged for Review</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Date</th><th>Category</th><th class="text-end">Amount</th><th>Reasons</th></tr></thead>
            <tbody>
                <?php foreach ($report['flags'] as $flag): ?>
                <tr>
                    <td><?= format_date($flag['date']) ?></td>
                    <td><?= e($flag['category']) ?></td>
                    <td class="text-end"><?= format_money($flag['amount']) ?></td>
                    <td class="small"><?= e(implode('; ', $flag['reasons'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($report['comparative'])): ?>
<div class="card mb-4">
    <div class="card-header">Comparative — <?= e($report['comparative']['financial_year']['name'] ?? 'Prior Year') ?></div>
    <div class="card-body row">
        <div class="col-md-4"><strong>Income:</strong> <?= format_money($report['comparative']['total_income']) ?></div>
        <div class="col-md-4"><strong>Expenditure:</strong> <?= format_money($report['comparative']['total_expenditure']) ?></div>
        <div class="col-md-4"><strong><?= e($report['comparative']['result_label']) ?>:</strong> <?= format_money($report['comparative']['result_amount']) ?></div>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('conversionSettingsForm')?.addEventListener('submit', function () {
    const rows = [];
    document.querySelectorAll('#accrualTable tbody tr').forEach(function (tr) {
        rows.push({
            category: tr.dataset.category,
            side: tr.dataset.side,
            outstanding_end: tr.querySelector('.acc-out-end')?.value || 0,
            outstanding_begin: tr.querySelector('.acc-out-begin')?.value || 0,
            prepaid_end: tr.querySelector('.acc-prep-end')?.value || 0,
            prepaid_begin: tr.querySelector('.acc-prep-begin')?.value || 0,
        });
    });
    document.getElementById('accrualsJson').value = JSON.stringify(rows);
});
</script>

<?php endif; ?>
