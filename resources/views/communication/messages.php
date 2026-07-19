<div class="page-header"><h1>Send Messages</h1></div>
<div class="row g-4">
    <div class="col-lg-4"><div class="card"><div class="card-header">Compose Message</div><div class="card-body">
        <form method="POST" action="<?= url('communication/messages/send') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-2"><select name="message_type" class="form-select"><option value="sms">SMS</option><option value="email">Email</option><option value="push">Push Notification</option></select></div>
            <div class="mb-2"><select name="recipient_type" class="form-select"><option value="all">All Members</option><option value="individual">Individual</option></select></div>
            <div class="mb-2"><select name="member_id" class="form-select"><option value="">Select member...</option><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['first_name'].' '.$m['last_name']) ?></option><?php endforeach; ?></select></div>
            <div class="mb-2"><input type="text" name="subject" class="form-control" placeholder="Subject"></div>
            <div class="mb-2"><textarea name="content" class="form-control" placeholder="Message" rows="4" required></textarea></div>
            <button type="submit" class="btn btn-primary w-100">Send</button>
        </form>
    </div></div>
    <div class="col-lg-8"><div class="card"><div class="card-header">Sent Messages</div><div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>Type</th><th>Subject</th><th>Recipients</th><th>Date</th><th>Status</th></tr></thead>
        <tbody><?php foreach ($messages as $m): ?>
            <tr><td><span class="badge bg-secondary"><?= e($m['message_type']) ?></span></td><td><?= e($m['subject'] ?? '-') ?></td><td><?= e($m['recipient_type']) ?></td><td><?= format_date($m['sent_at'] ?? $m['created_at']) ?></td><td><?= status_badge($m['status']) ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div></div></div>
</div>
