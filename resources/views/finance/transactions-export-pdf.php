<div class="no-print text-center mb-3">
    <button onclick="window.print()" class="btn btn-primary btn-lg">
        <i class="bi bi-printer"></i> Print / Save as PDF
    </button>
    <a href="<?= url('finance/' . $type) ?>" class="btn btn-outline-secondary btn-lg ms-2">Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="text-center mb-4">
            <h3 class="mb-1"><?= e($config['church_name']) ?></h3>
            <h4 class="mb-1"><?= e($title) ?></h4>
            <p class="text-muted mb-0">
                Generated: <?= date('F j, Y g:i A') ?>
                <?php if ($dateFilter['active'] ?? false): ?>
                · Filter: <?= e($dateFilter['label']) ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <?php if ($type === 'income'): ?>
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Financial Year</th>
                        <th>Category</th>
                        <th>Member</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th>Payment</th>
                        <th>Receipt No</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted">No records found</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= format_date($r['transaction_date']) ?></td>
                        <td><?= e($r['financial_year_name'] ?? '—') ?></td>
                        <td><?= e($r['type_name']) ?></td>
                        <td><?= e($r['member_name'] ?? 'Anonymous') ?></td>
                        <td><?= e($r['description'] ?? '—') ?></td>
                        <td class="text-end"><?= format_money($r['amount']) ?></td>
                        <td><?= e(format_payment_method($r['payment_method'])) ?></td>
                        <td><?= e($r['receipt_number'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="5" class="text-end">Total Amount</td>
                        <td class="text-end text-success"><?= format_money($tableTotal) ?></td>
                        <td colspan="2"><?= count($rows) ?> record(s)</td>
                    </tr>
                </tfoot>
                <?php else: ?>
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Financial Year</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th>Payment</th>
                        <th>Voucher No</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No records found</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= format_date($r['transaction_date']) ?></td>
                        <td><?= e($r['financial_year_name'] ?? '—') ?></td>
                        <td><?= e($r['type_name']) ?></td>
                        <td><?= e($r['description'] ?? '—') ?></td>
                        <td class="text-end"><?= format_money($r['amount']) ?></td>
                        <td><?= e(format_payment_method($r['payment_method'])) ?></td>
                        <td><?= e($r['voucher_number'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="4" class="text-end">Total Amount</td>
                        <td class="text-end text-danger"><?= format_money($tableTotal) ?></td>
                        <td colspan="2"><?= count($rows) ?> record(s)</td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    if (window.location.search.includes('auto_print=1')) {
        window.print();
    }
});
</script>
