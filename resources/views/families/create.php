<div class="page-header"><h1>Add Generational Group</h1></div>
<div class="card" style="max-width: 500px;"><div class="card-body">
    <form method="POST" action="<?= url('families/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3"><input type="text" name="family_name" class="form-control" placeholder="Generational Group Name *" required></div>
        <div class="mb-3"><select name="head_member_id" class="form-select"><option value="">Group Head (optional)</option><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'].' '.$m['last_name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><input type="text" name="address" class="form-control" placeholder="Address"></div>
        <div class="mb-3"><input type="text" name="city" class="form-control" placeholder="City"></div>
        <div class="mb-3"><input type="text" name="phone" class="form-control" placeholder="Phone"></div>
        <button type="submit" class="btn btn-primary">Create Generational Group</button>
    </form>
</div></div>
