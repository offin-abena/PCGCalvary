<?php if (!empty($currentFinancialYear)): ?>
<div class="alert alert-primary py-2 d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <i class="bi bi-calendar-range"></i>
        <strong>Financial Year:</strong> <?= e($currentFinancialYear['name']) ?>
        (<?= format_date($currentFinancialYear['start_date']) ?> — <?= format_date($currentFinancialYear['end_date']) ?>)
    </div>
    <?php if (!empty($nextReceiptNo)): ?>
    <small class="text-muted">Next Receipt No: <code><?= e($nextReceiptNo) ?></code></small>
    <?php elseif (!empty($nextVoucherNo)): ?>
    <small class="text-muted">Next Voucher No: <code><?= e($nextVoucherNo) ?></code></small>
    <?php elseif (!empty($nextAssetNo)): ?>
    <small class="text-muted">Next Asset Ref: <code><?= e($nextAssetNo) ?></code></small>
    <?php elseif (!empty($nextLiabilityNo)): ?>
    <small class="text-muted">Next Liability Ref: <code><?= e($nextLiabilityNo) ?></code></small>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="alert alert-warning py-2 mb-4">
    <i class="bi bi-exclamation-triangle"></i>
    No active financial year is configured.
    <?php if (\App\Core\Auth::canManageFinancialYear()): ?>
    <a href="<?= url('settings#financial-years') ?>">Set up a financial year in Settings</a>.
    <?php else: ?>
    Please contact the Super Admin or Pastor to set up the financial year.
    <?php endif; ?>
</div>
<?php endif; ?>
