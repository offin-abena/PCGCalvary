<div class="page-header"><h1>Register Visitor</h1></div>
<div class="card" style="max-width: 500px;"><div class="card-body">
    <form method="POST" action="<?= url('visitors/store') ?>">
            <?= church_csrf_field() ?>
            <div class="row g-3">
            <div class="col-md-6"><input type="text" name="first_name" class="form-control" placeholder="First Name *" required></div>
            <div class="col-md-6"><input type="text" name="last_name" class="form-control" placeholder="Last Name *" required></div>
            <div class="col-md-6"><input type="text" name="phone" class="form-control" placeholder="Phone"></div>
            <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="Email"></div>
            <div class="col-12"><input type="date" name="visit_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-12"><select name="invited_by" class="form-select"><option value="">Invited by (optional)</option><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'].' '.$m['last_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><textarea name="notes" class="form-control" placeholder="Notes" rows="2"></textarea></div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Register</button>
    </form>
</div></div>
