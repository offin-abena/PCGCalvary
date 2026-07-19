<div class="page-header"><h1>Prayer Requests</h1></div>
<div class="row g-4">
    <div class="col-lg-4"><div class="card"><div class="card-header">Submit Request</div><div class="card-body">
        <form method="POST" action="<?= url('communication/prayer/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-2"><input type="text" name="requester_name" class="form-control" placeholder="Your Name"></div>
            <div class="mb-2"><textarea name="request_text" class="form-control" placeholder="Prayer request..." rows="4" required></textarea></div>
            <div class="form-check mb-2"><input type="checkbox" name="is_anonymous" value="1" class="form-check-input" id="anon"><label for="anon">Anonymous</label></div>
            <button type="submit" class="btn btn-primary w-100">Submit</button>
        </form>
    </div></div>
    <div class="col-lg-8"><?php foreach ($requests as $r): ?>
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between"><?= status_badge($r['status']) ?><small class="text-muted"><?= format_date($r['created_at']) ?></small></div>
            <p class="mt-2"><?= $r['is_confidential'] ? '<em>Confidential request</em>' : nl2br(e($r['request_text'])) ?></p>
            <small class="text-muted"><?= $r['is_anonymous'] ? 'Anonymous' : e($r['member_name'] ?? $r['requester_name']) ?></small>
        </div></div>
    <?php endforeach; ?></div>
</div>
