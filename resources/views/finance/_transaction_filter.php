<?php
$dateFilter = $dateFilter ?? resolve_transaction_date_filter();
$filterType = $dateFilter['filter_type'] ?? '';
$filterDate = $dateFilter['filter_date'] ?? date('Y-m-d');
$filterMonth = $dateFilter['filter_month'] ?? date('Y-m');
$clearUrl = url($clearPath ?? 'finance/income');
$amountLabel = $amountLabel ?? 'Total';
$amountField = $amountField ?? 'total_income';
?>
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="per_page" value="<?= (int) ($perPage ?? resolve_per_page()) ?>">
            <div class="col-md-3">
                <label class="form-label small mb-1">Search by</label>
                <select name="filter_type" id="filterType" class="form-select form-select-sm">
                    <option value="">All records</option>
                    <option value="day" <?= $filterType === 'day' ? 'selected' : '' ?>>Specific day</option>
                    <option value="month" <?= $filterType === 'month' ? 'selected' : '' ?>>Month</option>
                </select>
            </div>
            <div class="col-md-3" id="dayFilter" style="<?= $filterType === 'day' ? '' : 'display:none' ?>">
                <label class="form-label small mb-1">Date</label>
                <input type="date" name="filter_date" class="form-control form-control-sm" value="<?= e($filterDate) ?>">
            </div>
            <div class="col-md-3" id="monthFilter" style="<?= $filterType === 'month' ? '' : 'display:none' ?>">
                <label class="form-label small mb-1">Month</label>
                <input type="month" name="filter_month" class="form-control form-control-sm" value="<?= e($filterMonth) ?>">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Filter</button>
                <?php if ($dateFilter['active'] ?? false): ?>
                <a href="<?= $clearUrl ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
                <?php endif; ?>
            </div>
            <?php if ($dateFilter['active'] ?? false): ?>
            <div class="col-12">
                <span class="badge bg-info"><?= e($dateFilter['label']) ?></span>
                <span class="text-muted small ms-2"><?= (int) ($transactions['total'] ?? 0) ?> record(s)</span>
                <?php if (!empty($filterSummary)): ?>
                <span class="fw-semibold ms-2"><?= e($amountLabel) ?>: <?= format_money($filterSummary[$amountField] ?? 0) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<script>
document.getElementById('filterType')?.addEventListener('change', function() {
    document.getElementById('dayFilter').style.display = this.value === 'day' ? '' : 'none';
    document.getElementById('monthFilter').style.display = this.value === 'month' ? '' : 'none';
});
</script>
