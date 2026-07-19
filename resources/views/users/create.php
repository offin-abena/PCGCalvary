<div class="page-header"><h1>Create User</h1></div>
<div class="card" style="max-width: 500px;"><div class="card-body">
    <form method="POST" action="<?= url('users/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Username *" required></div>
        <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email *" required></div>
        <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password *" required></div>
        <div class="mb-3"><select name="role_id" class="form-select" required><?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><select name="member_id" class="form-select"><option value="">Link to Member (optional)</option><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'].' '.$m['last_name']) ?></option><?php endforeach; ?></select></div>
        <button type="submit" class="btn btn-primary">Create User</button>
    </form>
</div></div>
