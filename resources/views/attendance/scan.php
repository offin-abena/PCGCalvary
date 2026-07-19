<div class="page-header">
    <h1>QR Code Check-in</h1>
    <a href="<?= url($listUrl ?? 'attendance/adults') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<div class="qr-scanner">
    <div class="card">
        <div class="card-body p-4">
            <i class="bi bi-qr-code-scan display-1 text-primary mb-3 d-block"></i>
            <p class="text-muted">Scan or enter member QR code for <strong><?= e($ageGroupLabel ?? 'General') ?></strong></p>
            <select id="sessionId" class="form-select mb-3">
                <?php if (empty($todaySessions)): ?>
                <option value="">No sessions today for this group</option>
                <?php else: foreach ($todaySessions as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= e($s['name']) ?><?= $s['title'] ? ' - ' . e($s['title']) : '' ?></option>
                <?php endforeach; endif; ?>
            </select>
            <input type="text" id="qrCodeInput" class="form-control qr-input mb-3" placeholder="Scan QR code..." autofocus>
            <div id="qrResult" class="alert d-none"></div>
            <?php if (empty($todaySessions)): ?>
            <a href="<?= url('attendance/create?group=' . e($ageGroup ?? 'adults')) ?>" class="btn btn-primary btn-sm">Create today's session</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>window.APP_URL = '<?= rtrim((config('church'))['url'], '/') ?>';</script>
