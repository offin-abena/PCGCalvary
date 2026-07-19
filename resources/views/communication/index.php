<div class="page-header"><h1>Communication</h1></div>
<div class="row g-4 mb-4">
    <div class="col-md-4"><a href="<?= url('communication/announcements') ?>" class="card text-decoration-none"><div class="card-body text-center"><i class="bi bi-megaphone fs-1 text-primary"></i><h5 class="mt-2">Announcements</h5></div></a></div>
    <div class="col-md-4"><a href="<?= url('communication/messages') ?>" class="card text-decoration-none"><div class="card-body text-center"><i class="bi bi-envelope fs-1 text-success"></i><h5 class="mt-2">Send Messages</h5></div></a></div>
    <div class="col-md-4"><a href="<?= url('communication/prayer') ?>" class="card text-decoration-none"><div class="card-body text-center"><i class="bi bi-heart fs-1 text-danger"></i><h5 class="mt-2">Prayer Requests <span class="badge bg-danger"><?= $pendingPrayers ?></span></h5></div></a></div>
</div>
