<div class="page-header"><h1>Upload Document</h1></div>
<div class="card" style="max-width: 500px;"><div class="card-body">
    <form method="POST" action="<?= url('documents/store') ?>" enctype="multipart/form-data">
            <?= church_csrf_field() ?>
            <div class="mb-3"><input type="text" name="title" class="form-control" placeholder="Title *" required></div>
        <div class="mb-3"><select name="document_type" class="form-select"><option value="sermon">Sermon</option><option value="certificate">Certificate</option><option value="minutes">Meeting Minutes</option><option value="member_doc">Member Document</option><option value="policy">Policy</option><option value="other">Other</option></select></div>
        <div class="mb-3"><textarea name="description" class="form-control" placeholder="Description" rows="2"></textarea></div>
        <div class="mb-3"><input type="file" name="document" class="form-control" required></div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
</div></div>
