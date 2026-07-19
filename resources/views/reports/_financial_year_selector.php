<?php
$financialYear = $financialYear ?? resolve_report_financial_year();
$financialYears = $financialYears ?? (new \App\Models\FinancialYear())->getAll();
$reportPath = $reportPath ?? 'reports/financial';
?>
<form method="GET" action="<?= url($reportPath) ?>" class="search-bar row g-2 align-items-end mb-4">
    <div class="col-md-4">
        <label class="form-label small">Financial Year</label>
        <select name="financial_year_id" class="form-select" onchange="this.form.submit()">
            <?php if (empty($financialYears)): ?>
            <option value="">No financial years configured</option>
            <?php else: foreach ($financialYears as $fy): ?>
            <option value="<?= $fy['id'] ?>" <?= ($financialYear['id'] ?? 0) == $fy['id'] ? 'selected' : '' ?>>
                <?= e($fy['name']) ?> (<?= format_date($fy['start_date']) ?> — <?= format_date($fy['end_date']) ?>)
                <?= $fy['status'] === 'closed' ? ' [Closed]' : '' ?>
            </option>
            <?php endforeach; endif; ?>
        </select>
    </div>
    <div class="col-md-auto">
        <button type="submit" class="btn btn-primary">Generate Report</button>
    </div>
    <?php if (!empty($financialYear)): ?>
    <div class="col-12">
        <span class="badge bg-<?= $financialYear['status'] === 'active' ? 'success' : 'secondary' ?>">
            <?= e(ucfirst($financialYear['status'])) ?>
        </span>
        <?php if ($financialYear['is_current']): ?>
        <span class="badge bg-primary">Current Year</span>
        <?php endif; ?>
        <span class="text-muted small ms-2">Year label: <?= e($financialYear['year_label']) ?></span>
    </div>
    <?php endif; ?>
</form>
