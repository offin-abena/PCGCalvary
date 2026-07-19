<?php
$importType = $importType ?? 'income';
$templateUrl = url('finance/' . $importType . '/template');
$importUrl = url('finance/' . $importType . '/import');
$docLabel = $importType === 'income' ? 'Receipt No' : 'Voucher No';
$categories = $importCategories ?? [];
?>
<div class="card mt-3">
    <div class="card-header"><i class="bi bi-upload"></i> Import from Excel</div>
    <div class="card-body">
        <p class="text-muted small mb-2">
            Upload a <strong>.xlsx</strong> or <strong>.csv</strong> file with columns:
            <code>Date</code>, <code><?= e($docLabel) ?></code>, <code>Category</code>, <code>Description</code>, <code>Amount</code>, <code>Payment</code>
        </p>
        <?php if (!empty($categories)): ?>
        <p class="small mb-2"><strong>Categories:</strong> <?= e(implode(', ', $categories)) ?></p>
        <?php endif; ?>
        <p class="small text-muted mb-3">
            Payment values: Cash, Cheque, Mobile Money<?= $importType === 'income' ? ', Bank Transfer' : '' ?>.
            Leave <?= e($docLabel) ?> blank to auto-generate.
        </p>
        <form method="POST" action="<?= $importUrl ?>
            <?= church_csrf_field() ?>" enctype="multipart/form-data">
            <div class="mb-2">
                <input type="file" name="import_file" class="form-control form-control-sm" accept=".csv,.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1" <?= empty($currentFinancialYear) ? 'disabled' : '' ?>>
                    <i class="bi bi-file-earmark-spreadsheet"></i> Upload & Import
                </button>
                <a href="<?= $templateUrl ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-download"></i> Template
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($_SESSION['import_errors'])): ?>
<div class="alert alert-warning mt-3 py-2 small">
    <strong>Import errors:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($_SESSION['import_errors'] as $err): ?>
        <li><?= e($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php unset($_SESSION['import_errors']); endif; ?>
