<div class="page-header"><h1>Bank Reconciliation</h1></div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card"><div class="card-header">New Reconciliation</div><div class="card-body">
            <p class="text-muted">Book Balance: <strong><?= format_money($summary['balance']) ?></strong></p>
            <form method="POST" action="<?= url('finance/reconciliation/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-2"><input type="date" name="reconciliation_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                <div class="mb-2"><input type="number" name="bank_balance" class="form-control" placeholder="Bank Statement Balance" step="0.01" required></div>
                <input type="hidden" name="book_balance" value="<?= $summary['balance'] ?>">
                <div class="mb-2"><textarea name="notes" class="form-control" placeholder="Notes" rows="2"></textarea></div>
                <button type="submit" class="btn btn-primary w-100">Reconcile</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="table-responsive"><table class="table">
            <thead><tr><th>Date</th><th>Bank</th><th>Book</th><th>Difference</th><th>By</th></tr></thead>
            <tbody><?php foreach ($reconciliations as $r): ?>
                <tr><td><?= format_date($r['reconciliation_date']) ?></td><td><?= format_money($r['bank_balance']) ?></td><td><?= format_money($r['book_balance']) ?></td><td class="<?= $r['difference'] != 0 ? 'text-danger' : 'text-success' ?>"><?= format_money($r['difference']) ?></td><td><?= e($r['reconciled_by_name'] ?? '-') ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div></div>
    </div>
</div>
