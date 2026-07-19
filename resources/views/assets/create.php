<div class="page-header"><h1>Register Asset</h1></div>
<div class="card" style="max-width: 600px;"><div class="card-body">
    <form method="POST" action="<?= url('assets/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3"><input type="text" name="name" class="form-control" placeholder="Asset Name *" required></div>
        <div class="row g-3">
            <div class="col-md-6"><input type="text" name="category" class="form-control" placeholder="Category"></div>
            <div class="col-md-6"><input type="text" name="location" class="form-control" placeholder="Location"></div>
            <div class="col-md-6"><input type="date" name="purchase_date" class="form-control"></div>
            <div class="col-md-6"><input type="number" name="purchase_cost" class="form-control" placeholder="Purchase Cost" step="0.01"></div>
            <div class="col-md-6"><select name="condition_status" class="form-select"><option value="excellent">Excellent</option><option value="good" selected>Good</option><option value="fair">Fair</option><option value="poor">Poor</option></select></div>
            <div class="col-md-6"><select name="assigned_to" class="form-select"><option value="">Unassigned</option><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'].' '.$m['last_name']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="mb-3 mt-3"><textarea name="description" class="form-control" placeholder="Description" rows="2"></textarea></div>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div></div>
