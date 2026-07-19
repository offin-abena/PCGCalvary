<div class="page-header"><h1>Expense Records</h1></div>
<?php $nextVoucherNo = $nextVoucherNo ?? null; include __DIR__ . '/_financial_year_banner.php'; ?>
<div class="row g-4">
    <div class="col-xl-3 col-lg-4">
        <div class="card"><div class="card-header">Record Expense</div><div class="card-body">
            <form method="POST" action="<?= url('finance/store') ?>" id="expenseForm">
            <?= church_csrf_field() ?>
            <input type="hidden" name="transaction_type" value="expense">
                <div class="mb-2">
                    <label class="form-label small">Expense Type</label>
                    <select name="type_id" id="expenseType" class="form-select" required>
                        <?php foreach ($expenseTypes as $t): ?>
                            <option value="<?= $t['id'] ?>" data-code="<?= e(\App\Models\FinancialYear::deriveTypeCode($t['name'])) ?>"><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2"><input type="number" name="amount" class="form-control" placeholder="Amount" step="0.01" required></div>
                <div class="mb-2">
                    <label class="form-label small">Transaction Date</label>
                    <input type="date" name="transaction_date" id="expenseTransactionDate" class="form-control"
                        value="<?= date('Y-m-d') ?>"
                        <?= !empty($currentFinancialYear) ? 'min="'.$currentFinancialYear['start_date'].'" max="'.$currentFinancialYear['end_date'].'"' : '' ?>>
                </div>
                <?php if (!empty($nextVoucherNo)): ?>
                <div class="mb-2">
                    <label class="form-label small">Voucher No (auto)</label>
                    <input type="text" id="nextVoucherNo" class="form-control" value="<?= e($nextVoucherNo) ?>" readonly>
                    <small class="text-muted">Format: EXP_TYPE_YY_MM_#### (e.g. EXP_UT_26_06_0001)</small>
                </div>
                <?php endif; ?>
                <div class="mb-2">
                    <label class="form-label small">Payment Type</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash">Cash</option>
                        <option value="check">Cheque</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>
                <div class="mb-2"><input type="text" name="description" class="form-control" placeholder="Description"></div>
                <button type="submit" class="btn btn-danger w-100" <?= empty($currentFinancialYear) ? 'disabled' : '' ?>>Record Expense</button>
            </form>
        </div></div>
        <?php
        $importType = 'expense';
        $importCategories = array_column($expenseTypes, 'name');
        include __DIR__ . '/_import_upload.php';
        ?>
    </div>
    <div class="col-xl-9 col-lg-8">
        <?php
        $clearPath = 'finance/expense';
        $amountLabel = 'Total Expense';
        $amountField = 'total_expense';
        include __DIR__ . '/_transaction_filter.php';
        ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Expense Transactions</span>
                <?php $exportType = 'expense'; include __DIR__ . '/_export_buttons.php'; ?>
            </div>
            <div class="table-responsive"><table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Financial Year</th><th>Type</th><th class="text-end">Amount</th><th>Payment Type</th><th>Voucher No</th><th>Description</th></tr></thead>
            <tbody>
                <?php if (empty($transactions['data'])): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No expense records found</td></tr>
                <?php else: foreach ($transactions['data'] as $t): ?>
                <tr>
                    <td><?= format_date($t['transaction_date']) ?></td>
                    <td><?= e($t['financial_year_name'] ?? '—') ?></td>
                    <td><?= e($t['type_name']) ?></td>
                    <td class="text-danger fw-bold text-end"><?= format_money($t['amount']) ?></td>
                    <td><?= e(format_payment_method($t['payment_method'])) ?></td>
                    <td><code><?= e($t['voucher_number'] ?? '—') ?></code></td>
                    <td><?= e($t['description'] ?? '-') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($transactions['data'])): ?>
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">Total Amount<?= ($dateFilter['active'] ?? false) ? ' (' . e($dateFilter['label']) . ')' : '' ?></td>
                    <td class="text-danger text-end"><?= format_money($tableTotal ?? 0) ?></td>
                    <td colspan="3"><span class="text-muted small fw-normal"><?= (int) $transactions['total'] ?> record(s)</span></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table></div>
        <?php include __DIR__ . '/_transaction_pagination.php'; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.getElementById('expenseType');
    var transactionDate = document.getElementById('expenseTransactionDate');
    var voucherPreview = document.getElementById('nextVoucherNo');
    var nextVoucherNumbers = <?= json_encode($nextVoucherNumbers ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    function updateVoucherPreview() {
        if (!voucherPreview || !typeSelect || !transactionDate) {
            return;
        }

        var typeId = typeSelect.value;
        var base = nextVoucherNumbers[typeId] || '';
        var dateValue = transactionDate.value;
        if (!base || !dateValue) {
            return;
        }

        var parts = base.split('_');
        if (parts.length !== 5) {
            return;
        }

        var selected = typeSelect.options[typeSelect.selectedIndex];
        parts[0] = 'EXP';
        parts[1] = selected.dataset.code || parts[1];
        var d = new Date(dateValue + 'T00:00:00');
        parts[2] = String(d.getFullYear()).slice(-2);
        parts[3] = String(d.getMonth() + 1).padStart(2, '0');
        voucherPreview.value = parts.join('_');
    }

    typeSelect.addEventListener('change', updateVoucherPreview);
    transactionDate.addEventListener('change', updateVoucherPreview);
    updateVoucherPreview();
});
</script>
