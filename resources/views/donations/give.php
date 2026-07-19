<div class="giving-hero">
    <h1>Give Online</h1>
    <p>Support the ministry through your generous giving</p>
</div>
<div class="container py-5" style="max-width: 600px;">
    <div class="card shadow"><div class="card-body p-4">
        <form method="POST" action="<?= url('donations/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3"><label class="form-label">Giving Type</label><select name="income_type_id" class="form-select" required><?php foreach ($incomeTypes as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
            <?php if (!empty($campaigns)): ?><div class="mb-3"><label class="form-label">Campaign (Optional)</label><select name="campaign_id" class="form-select"><option value="">General</option><?php foreach ($campaigns as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
            <div class="mb-3"><label class="form-label">Amount (<?= e($config['currency_symbol']) ?>)</label><input type="number" name="amount" class="form-control form-control-lg" step="0.01" min="1" required></div>
            <div class="mb-3"><label class="form-label">Payment Method</label><select name="payment_method" class="form-select"><option value="mobile_money">Mobile Money</option><option value="card">Credit/Debit Card</option><option value="bank_transfer">Bank Transfer</option></select></div>
            <div class="form-check mb-3"><input type="checkbox" name="is_recurring" value="1" class="form-check-input" id="recurring"><label for="recurring">Make this a recurring gift</label></div>
            <button type="submit" class="btn btn-accent btn-lg w-100"><i class="bi bi-heart"></i> Give Now</button>
        </form>
    </div></div>
</div>
