<?php
$perPage = $perPage ?? resolve_per_page();
$qs = transaction_filter_query_string($dateFilter ?? resolve_transaction_date_filter(), $perPage);
$baseQs = transaction_filter_query_string($dateFilter ?? resolve_transaction_date_filter(), $perPage);
?>
<div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
    <form method="GET" class="d-flex align-items-center gap-2">
        <?php if (!empty($dateFilter['filter_type'])): ?>
        <input type="hidden" name="filter_type" value="<?= e($dateFilter['filter_type']) ?>">
        <?php endif; ?>
        <?php if (!empty($dateFilter['filter_date'])): ?>
        <input type="hidden" name="filter_date" value="<?= e($dateFilter['filter_date']) ?>">
        <?php endif; ?>
        <?php if (!empty($dateFilter['filter_month'])): ?>
        <input type="hidden" name="filter_month" value="<?= e($dateFilter['filter_month']) ?>">
        <?php endif; ?>
        <label class="small text-muted mb-0">Rows per page</label>
        <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            <?php foreach (allowed_per_page_options() as $opt): ?>
            <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if (($transactions['total_pages'] ?? 1) > 1): ?>
    <div class="d-flex flex-wrap justify-content-center">
        <?php for ($i = 1; $i <= $transactions['total_pages']; $i++): ?>
        <a href="?page=<?= $i ?>&<?= $baseQs ?>"
           class="btn btn-sm <?= $i === $transactions['page'] ? 'btn-primary' : 'btn-outline-secondary' ?> mx-1 mb-1"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php else: ?>
    <span class="text-muted small">Page 1 of 1</span>
    <?php endif; ?>
</div>
