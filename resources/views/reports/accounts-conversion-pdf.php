<?php $ie = $report['income_expenditure']; $fp = $report['financial_position']; ?>

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

        <div class="row mb-4">
            <div class="col-3 text-center">
                <div class="fw-bold text-success"><?= format_money($ie['total_income']) ?></div>
                <small>Total Income (Adjusted)</small>
            </div>
            <div class="col-3 text-center">
                <div class="fw-bold text-danger"><?= format_money($ie['total_expenditure']) ?></div>
                <small>Total Expenditure (Adjusted)</small>
            </div>
            <div class="col-3 text-center">
                <div class="fw-bold"><?= format_money($ie['result_amount']) ?></div>
                <small><?= e($ie['result_label']) ?></small>
            </div>
            <div class="col-3 text-center">
                <div class="fw-bold"><?= format_money($fp['accumulated_fund']['closing']) ?></div>
                <small>Accumulated Fund</small>
            </div>
        </div>

        <h6 class="text-success">Income &amp; Expenditure — Income</h6>
        <table class="table table-bordered table-sm mb-4">
            <thead><tr><th>Category</th><th class="text-end">Cash</th><th class="text-end">Adjusted</th></tr></thead>
            <tbody>
                <?php foreach ($ie['income']['lines'] as $line): ?>
                <tr>
                    <td><?= e($line['category']) ?></td>
                    <td class="text-end"><?= format_money($line['cash_amount']) ?></td>
                    <td class="text-end"><?= format_money($line['adjusted_amount']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="fw-bold"><td>Total Income</td><td></td><td class="text-end"><?= format_money($ie['total_income']) ?></td></tr></tfoot>
        </table>

        <h6 class="text-danger">Income &amp; Expenditure — Expenditure</h6>
        <table class="table table-bordered table-sm mb-4">
            <thead><tr><th>Category</th><th class="text-end">Cash</th><th class="text-end">Adjusted</th></tr></thead>
            <tbody>
                <?php foreach ($ie['expenditure']['lines'] as $line): ?>
                <tr>
                    <td><?= e($line['category']) ?></td>
                    <td class="text-end"><?= format_money($line['cash_amount'] ?? 0) ?></td>
                    <td class="text-end"><?= format_money($line['adjusted_amount']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="fw-bold"><td>Total Expenditure</td><td></td><td class="text-end"><?= format_money($ie['total_expenditure']) ?></td></tr></tfoot>
        </table>

        <p class="fw-bold mb-4"><?= e($ie['result_label']) ?>: <?= format_money($ie['result_amount']) ?></p>

        <h6>Statement of Financial Position</h6>
        <div class="row mb-4">
            <div class="col-6">
                <table class="table table-bordered table-sm">
                    <thead><tr><th colspan="2">Assets</th></tr></thead>
                    <tbody>
                        <?php foreach ($fp['assets'] as $a): ?>
                        <tr><td><?= e($a['label']) ?></td><td class="text-end"><?= format_money($a['amount']) ?></td></tr>
                        <?php endforeach; ?>
                        <tr class="fw-bold"><td>Total Assets</td><td class="text-end"><?= format_money($fp['total_assets']) ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-6">
                <table class="table table-bordered table-sm mb-3">
                    <thead><tr><th colspan="2">Liabilities</th></tr></thead>
                    <tbody>
                        <?php if (empty($fp['liabilities'])): ?>
                        <tr><td colspan="2" class="text-muted">None recorded</td></tr>
                        <?php else: foreach ($fp['liabilities'] as $l): ?>
                        <tr><td><?= e($l['label']) ?></td><td class="text-end"><?= format_money($l['amount']) ?></td></tr>
                        <?php endforeach; endif; ?>
                        <tr class="fw-bold"><td>Total Liabilities</td><td class="text-end"><?= format_money($fp['total_liabilities']) ?></td></tr>
                    </tbody>
                </table>
                <table class="table table-bordered table-sm">
                    <thead><tr><th colspan="2">Accumulated Fund</th></tr></thead>
                    <tbody>
                        <tr><td>Opening</td><td class="text-end"><?= format_money($fp['accumulated_fund']['opening']) ?></td></tr>
                        <?php if ($fp['accumulated_fund']['surplus'] > 0): ?>
                        <tr><td>Surplus for the Year</td><td class="text-end"><?= format_money($fp['accumulated_fund']['surplus']) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($fp['accumulated_fund']['deficit'] > 0): ?>
                        <tr><td>Deficit for the Year</td><td class="text-end">(<?= format_money($fp['accumulated_fund']['deficit']) ?>)</td></tr>
                        <?php endif; ?>
                        <tr class="fw-bold"><td>Closing Fund</td><td class="text-end"><?= format_money($fp['accumulated_fund']['closing']) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($report['depreciation_schedule']['lines'])): ?>
        <h6>Depreciation Schedule</h6>
        <table class="table table-bordered table-sm mb-4">
            <thead><tr><th>Asset</th><th>Rate</th><th class="text-end">Depreciation</th><th class="text-end">Net Book</th></tr></thead>
            <tbody>
                <?php foreach ($report['depreciation_schedule']['lines'] as $d): ?>
                <tr>
                    <td><?= e($d['name']) ?></td>
                    <td><?= e($d['rate_percent']) ?>%</td>
                    <td class="text-end"><?= format_money($d['depreciation_amount']) ?></td>
                    <td class="text-end"><?= format_money($d['net_book_value']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <h6>Validation Summary</h6>
        <ul class="small mb-0">
            <?php foreach ($report['validations'] as $v): ?>
            <li><?= $v['passed'] ? '✓' : '✗' ?> <?= e($v['rule']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
