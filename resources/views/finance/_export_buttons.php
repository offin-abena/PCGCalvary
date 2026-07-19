<?php
$exportType = $exportType ?? 'income';
$filterQs = transaction_filter_query_string($dateFilter ?? resolve_transaction_date_filter());
$excelUrl = url('finance/' . $exportType . '/export/excel') . ($filterQs ? '?' . $filterQs : '');
$pdfUrl = url('finance/' . $exportType . '/export/pdf') . ($filterQs ? '?' . $filterQs : '');
?>
<div class="d-flex gap-2">
    <a href="<?= $excelUrl ?>" class="btn btn-sm btn-success" title="Export to Excel">
        <i class="bi bi-file-earmark-spreadsheet"></i> Excel
    </a>
    <a href="<?= $pdfUrl ?>" class="btn btn-sm btn-danger" target="_blank" title="Export to PDF">
        <i class="bi bi-file-earmark-pdf"></i> PDF
    </a>
</div>
