<?php
$exportBase = $exportBase ?? 'reports/financial';
$fyId = $financialYear['id'] ?? '';
$qs = $fyId ? '?financial_year_id=' . (int) $fyId : '';
?>
<div class="d-flex gap-2">
    <a href="<?= url($exportBase . '/export/excel' . $qs) ?>" class="btn btn-success btn-sm">
        <i class="bi bi-file-earmark-spreadsheet"></i> Excel
    </a>
    <a href="<?= url($exportBase . '/export/pdf' . $qs) ?>" class="btn btn-danger btn-sm" target="_blank">
        <i class="bi bi-file-earmark-pdf"></i> PDF
    </a>
</div>
