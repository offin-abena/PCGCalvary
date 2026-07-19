<div class="page-header"><h1>Pledge Management</h1></div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card"><div class="card-header">New Pledge</div><div class="card-body">
            <form method="POST" action="<?= url('finance/pledges/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-2"><select name="member_id" class="form-select" required><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'].' '.$m['last_name']) ?></option><?php endforeach; ?></select></div>
                <div class="mb-2"><input type="number" name="amount" class="form-control" placeholder="Pledge Amount" required></div>
                <div class="mb-2"><input type="date" name="pledge_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                <div class="mb-2"><input type="date" name="due_date" class="form-control" placeholder="Due Date"></div>
                <div class="mb-2"><input type="text" name="purpose" class="form-control" placeholder="Purpose"></div>
                <button type="submit" class="btn btn-primary w-100">Create Pledge</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="table-responsive"><table class="table">
            <thead><tr><th>Member</th><th>Amount</th><th>Paid</th><th>Status</th><th>Due</th></tr></thead>
            <tbody><?php foreach ($pledges['data'] as $p): ?>
                <tr><td><?= e($p['member_name']) ?></td><td><?= format_money($p['amount']) ?></td><td><?= format_money($p['amount_paid']) ?></td><td><?= status_badge($p['status']) ?></td><td><?= format_date($p['due_date']) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div></div>
    </div>
</div>
