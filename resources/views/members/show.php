<div class="page-header">
    <h1><?= e($member['first_name'] . ' ' . $member['last_name']) ?></h1>
    <div>
        <a href="<?= url('members/' . $member['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm">Edit</a>
        <a href="<?= url($backUrl ?? 'members/adults') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card text-center">
            <div class="card-body py-4">
                <?php if ($member['photo']): ?>
                    <img src="<?= upload_url($member['photo']) ?>" class="member-avatar-lg mb-3" alt="">
                <?php else: ?>
                    <div class="member-avatar-lg mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary text-white fs-1">
                        <?= strtoupper(substr($member['first_name'],0,1)) ?>
                    </div>
                <?php endif; ?>
                <h4><?= e(trim($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name'])) ?></h4>
                <p class="text-muted">
                    <?php if ($member['membership_id']): ?><code><?= e($member['membership_id']) ?></code><?php else: ?><code><?= e($member['member_code']) ?></code><?php endif; ?>
                </p>
                <?= status_badge($member['membership_status']) ?>
                <?php if ($member['is_new_convert']): ?><span class="badge bg-warning ms-1">New Convert</span><?php endif; ?>
                <hr>
                <div class="text-start">
                    <p><i class="bi bi-telephone"></i> <?= e($member['phone'] ?? 'N/A') ?></p>
                    <p><i class="bi bi-envelope"></i> <?= e($member['email'] ?? 'N/A') ?></p>
                    <p><i class="bi bi-geo-alt"></i> <?= e($member['address'] ?? 'N/A') ?><?= $member['city'] ? ', ' . e($member['city']) : '' ?></p>
                    <?php if ($member['hometown']): ?><p><i class="bi bi-pin-map"></i> Hometown: <?= e($member['hometown']) ?></p><?php endif; ?>
                    <?php if ($member['family_name']): ?><p><i class="bi bi-people"></i> Generational Group: <strong><?= e($member['family_name']) ?></strong></p><?php endif; ?>
                    <?php if (!empty($member['youth_ministry'])): ?>
                    <p><i class="bi bi-emoji-smile"></i> Youth Ministry: <strong><?= e($member['youth_ministry']['name']) ?></strong>
                        <span class="badge bg-info"><?= e(str_replace('_', ' ', ucfirst($member['youth_ministry']['ministry_type']))) ?></span>
                    </p>
                    <?php endif; ?>
                    <?php if ($member['current_church_position']): ?><p><i class="bi bi-briefcase"></i> <?= e($member['current_church_position']) ?></p><?php endif; ?>
                </div>
                <hr>
                <small class="text-muted">QR Code: <code><?= e($member['qr_code']) ?></code></small>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Emergency Contact</div>
            <div class="card-body">
                <p class="mb-1"><strong><?= e($member['emergency_contact_name'] ?? 'Not set') ?></strong></p>
                <p class="mb-1"><?= e($member['emergency_contact_phone'] ?? '') ?></p>
                <small class="text-muted"><?= e($member['emergency_contact_relation'] ?? '') ?></small>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Baptism</small><div class="fw-bold"><?= format_date($member['baptism_date']) ?></div></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Confirmation</small><div class="fw-bold"><?= format_date($member['confirmation_date']) ?></div></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Member Since</small><div class="fw-bold"><?= format_date($member['membership_date']) ?></div></div></div></div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Recent Attendance</div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Service</th><th>Check-in</th></tr></thead>
                    <tbody>
                        <?php if (empty($attendance)): ?><tr><td colspan="3" class="text-muted text-center">No attendance records</td></tr>
                        <?php else: foreach ($attendance as $a): ?>
                            <tr><td><?= format_date($a['session_date']) ?></td><td><?= e($a['service_name']) ?></td><td><?= format_date($a['check_in_time'], 'h:i A') ?></td></tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Giving History</div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Amount</th><th>Receipt</th></tr></thead>
                    <tbody>
                        <?php if (empty($donations)): ?><tr><td colspan="3" class="text-muted text-center">No giving records</td></tr>
                        <?php else: foreach ($donations as $d): ?>
                            <tr><td><?= format_date($d['transaction_date']) ?></td><td><?= format_money($d['amount']) ?></td><td><code><?= e($d['receipt_number']) ?></code></td></tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
