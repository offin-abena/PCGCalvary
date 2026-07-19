<div class="page-header">
    <h1>Liability Records</h1>
    <a href="<?= url('finance') ?>" class="btn btn-outline-secondary btn-sm">Back to Finance</a>
</div>
<?php $nextLiabilityNo = $nextLiabilityNo ?? null; include __DIR__ . '/_financial_year_banner.php'; ?>

<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-value text-primary"><?= format_money($summary['total_assets']) ?></div><div class="stat-label">Total Assets</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value text-danger"><?= format_money($summary['total_liabilities']) ?></div><div class="stat-label">Total Liabilities</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= format_money($summary['net_worth']) ?></div><div class="stat-label">Net Worth (Assets − Liabilities)</div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Record Liability</div>
            <div class="card-body">
                <form method="POST" action="<?= url('finance/liabilities/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-2">
                        <label class="form-label small">Category</label>
                        <select name="category" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Liability Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Bank Loan" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Amount (GH₵)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Creditor</label>
                        <input type="text" name="creditor" class="form-control" placeholder="Who is owed">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Record Date</label>
                        <input type="date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>"
                            <?= !empty($currentFinancialYear) ? 'min="'.$currentFinancialYear['start_date'].'" max="'.$currentFinancialYear['end_date'].'"' : '' ?>>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Due Date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                    <?php if (!empty($nextLiabilityNo)): ?>
                    <div class="mb-2">
                        <label class="form-label small">Reference No (auto)</label>
                        <input type="text" class="form-control" value="<?= e($nextLiabilityNo) ?>" readonly>
                    </div>
                    <?php endif; ?>
                    <div class="mb-2">
                        <label class="form-label small">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100" <?= empty($currentFinancialYear) ? 'disabled' : '' ?>>Record Liability</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Ref No</th>
                            <th>Category</th>
                            <th>Liability</th>
                            <th>Creditor</th>
                            <th>Amount</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records['data'])): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No liabilities recorded for this financial year</td></tr>
                        <?php else: foreach ($records['data'] as $r): ?>
                        <tr>
                            <td><?= format_date($r['record_date']) ?></td>
                            <td><code><?= e($r['reference_number']) ?></code></td>
                            <td><?= e($r['category']) ?></td>
                            <td><?= e($r['name']) ?></td>
                            <td><?= e($r['creditor'] ?? '—') ?></td>
                            <td class="text-danger fw-bold"><?= format_money($r['amount']) ?></td>
                            <td><?= $r['due_date'] ? format_date($r['due_date']) : '—' ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
