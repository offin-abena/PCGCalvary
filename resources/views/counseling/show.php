<div class="page-header"><h1>Counseling Session</h1><?= status_badge($appointment['status']) ?></div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <p><strong>Member:</strong> <?= e($appointment['member_name']) ?></p>
            <p><strong>Counselor:</strong> <?= e($appointment['counselor_name'] ?? 'TBD') ?></p>
            <p><strong>Date:</strong> <?= format_date($appointment['appointment_date'], 'M d, Y h:i A') ?></p>
            <p><strong>Topic:</strong> <?= e($appointment['topic'] ?? '-') ?></p>
        </div></div>
        <div class="card"><div class="card-header"><i class="bi bi-lock"></i> Confidential Notes</div><div class="card-body">
            <form method="POST" action="<?= url('counseling/'.$appointment['id'].'/notes') ?>">
            <?= church_csrf_field() ?>
            <input type="hidden" name="counselor_id" value="<?= $appointment['counselor_id'] ?? '' ?>">
                <textarea name="notes" class="form-control mb-2" rows="4" placeholder="Session notes..." required></textarea>
                <div class="form-check mb-2"><input type="checkbox" name="follow_up_required" value="1" class="form-check-input" id="fu"><label for="fu">Follow-up required</label></div>
                <input type="date" name="follow_up_date" class="form-control mb-2">
                <button type="submit" class="btn btn-primary w-100">Save Notes</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <?php foreach ($notes as $n): ?>
        <div class="card mb-3"><div class="card-body">
            <small class="text-muted"><?= format_date($n['created_at']) ?> - <?= e($n['counselor_name']) ?></small>
            <p class="mt-2"><?= nl2br(e($n['notes'])) ?></p>
        </div></div>
        <?php endforeach; ?>
    </div>
</div>
