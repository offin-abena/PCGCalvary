<div class="no-print text-center mb-3">
    <button onclick="window.print()" class="btn btn-primary btn-lg">
        <i class="bi bi-printer"></i> Print / Save as PDF
    </button>
    <a href="<?= url($backUrl) ?>" class="btn btn-outline-secondary btn-lg ms-2">Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="text-center mb-4">
            <h3 class="mb-1"><?= e($config['church_name']) ?></h3>
            <h4 class="mb-1"><?= e($title) ?></h4>
            <p class="text-muted mb-0">
                <?= e($financialYear['name']) ?> ·
                <?= format_date($financialYear['start_date']) ?> — <?= format_date($financialYear['end_date']) ?>
            </p>
            <p class="text-muted small">Generated: <?= date('F j, Y g:i A') ?></p>
        </div>

        <?php if ($reportType === 'income-expenditure'): ?>
        <div class="row mb-4">
            <div class="col-4 text-center"><div class="fw-bold text-success"><?= format_money($summary['total_income']) ?></div><small>Total Income</small></div>
            <div class="col-4 text-center"><div class="fw-bold text-danger"><?= format_money($summary['total_expense']) ?></div><small>Total Expenditure</small></div>
            <div class="col-4 text-center"><div class="fw-bold"><?= format_money($summary['balance']) ?></div><small><?= $summary['balance'] >= 0 ? 'Surplus' : 'Deficit' ?></small></div>
        </div>
        <h6 class="text-success">Income</h6>
        <table class="table table-bordered table-sm mb-4">
            <thead><tr><th>Category</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                <?php foreach ($incomeByType as $row): if ($row['total'] <= 0) continue; ?>
                <tr><td><?= e($row['name']) ?></td><td class="text-end"><?= format_money($row['total']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="fw-bold"><td>Total Income</td><td class="text-end"><?= format_money($summary['total_income']) ?></td></tr></tfoot>
        </table>
        <h6 class="text-danger">Expenditure</h6>
        <table class="table table-bordered table-sm">
            <thead><tr><th>Category</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                <?php foreach ($expenseByType as $row): if ($row['total'] <= 0) continue; ?>
                <tr><td><?= e($row['name']) ?></td><td class="text-end"><?= format_money($row['total']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="fw-bold"><td>Total Expenditure</td><td class="text-end"><?= format_money($summary['total_expense']) ?></td></tr></tfoot>
        </table>
        <?php else: ?>
        <div class="row mb-4">
            <div class="col-4 text-center"><div class="fw-bold text-success"><?= format_money($summary['total_income']) ?></div><small>Total Receipts</small></div>
            <div class="col-4 text-center"><div class="fw-bold text-danger"><?= format_money($summary['total_expense']) ?></div><small>Total Payments</small></div>
            <div class="col-4 text-center"><div class="fw-bold"><?= format_money($summary['balance']) ?></div><small>Net</small></div>
        </div>
        <h6 class="text-success">Receipts</h6>
        <table class="table table-bordered table-sm mb-4">
            <thead><tr><th>Category</th><th class="text-end">Records</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                <?php foreach ($incomeByType as $row): if ($row['total'] <= 0) continue; ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td class="text-end"><?= (int) $row['record_count'] ?></td>
                    <td class="text-end"><?= format_money($row['total']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="fw-bold"><td>Total Receipts</td><td></td><td class="text-end"><?= format_money($summary['total_income']) ?></td></tr></tfoot>
        </table>
        <h6 class="text-danger">Payments</h6>
        <table class="table table-bordered table-sm">
            <thead><tr><th>Category</th><th class="text-end">Records</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                <?php foreach ($expenseByType as $row): if ($row['total'] <= 0) continue; ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td class="text-end"><?= (int) $row['record_count'] ?></td>
                    <td class="text-end"><?= format_money($row['total']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="fw-bold"><td>Total Payments</td><td></td><td class="text-end"><?= format_money($summary['total_expense']) ?></td></tr></tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>
