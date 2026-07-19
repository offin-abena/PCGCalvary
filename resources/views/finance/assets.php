<div class="page-header">
    <h1>Asset Records</h1>
    <a href="<?= url('finance') ?>" class="btn btn-outline-secondary btn-sm">Back to Finance</a>
</div>
<?php $nextAssetNo = $nextAssetNo ?? null; include __DIR__ . '/_financial_year_banner.php'; ?>

<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-value text-primary"><?= format_money($summary['total_assets']) ?></div><div class="stat-label">Total Assets</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value text-danger"><?= format_money($summary['total_liabilities']) ?></div><div class="stat-label">Total Liabilities</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= format_money($summary['net_worth']) ?></div><div class="stat-label">Net Worth (Assets − Liabilities)</div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Record Asset</div>
            <div class="card-body">
                <form method="POST" action="<?= url('finance/assets/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-2">
                        <label class="form-label small">Category</label>
                        <select name="category" class="form-select" required id="assetCategorySelect">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" data-hint="<?= e($categoryHints[$cat] ?? '') ?>">
                                <?= e($cat) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" id="assetCategoryHint"><?= e($categoryHints[$categories[0]] ?? '') ?></small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Asset Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Main Church Building" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Value (GH₵)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Record Date</label>
                        <input type="date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>"
                            <?= !empty($currentFinancialYear) ? 'min="'.$currentFinancialYear['start_date'].'" max="'.$currentFinancialYear['end_date'].'"' : '' ?>>
                    </div>
                    <?php if (!empty($nextAssetNo)): ?>
                    <div class="mb-2">
                        <label class="form-label small">Reference No (auto)</label>
                        <input type="text" class="form-control" value="<?= e($nextAssetNo) ?>" readonly>
                    </div>
                    <?php endif; ?>
                    <div class="mb-2">
                        <label class="form-label small">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" <?= empty($currentFinancialYear) ? 'disabled' : '' ?>>Record Asset</button>
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
                            <th>Asset</th>
                            <th>Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records['data'])): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No assets recorded for this financial year</td></tr>
                        <?php else: foreach ($records['data'] as $r): ?>
                        <tr>
                            <td><?= format_date($r['record_date']) ?></td>
                            <td><code><?= e($r['reference_number']) ?></code></td>
                            <td><?= e($r['category']) ?></td>
                            <td><?= e($r['name']) ?></td>
                            <td class="text-primary fw-bold"><?= format_money($r['amount']) ?></td>
                            <td><?= e($r['description'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('assetCategorySelect')?.addEventListener('change', function () {
    const hint = this.options[this.selectedIndex]?.dataset.hint || '';
    const el = document.getElementById('assetCategoryHint');
    if (el) el.textContent = hint;
});
</script>
