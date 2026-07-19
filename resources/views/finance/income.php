<div class="page-header"><h1>Income Records</h1></div>
<?php $nextReceiptNo = $nextReceiptNo ?? null; include __DIR__ . '/_financial_year_banner.php'; ?>
<div class="row g-4">
    <div class="col-xl-3 col-lg-4">
        <div class="card"><div class="card-header">Record Income</div><div class="card-body">
            <form method="POST" action="<?= url('finance/store') ?>" id="incomeForm">
            <?= church_csrf_field() ?>
            <input type="hidden" name="transaction_type" value="income">
                <div class="mb-2">
                    <label class="form-label small">Income Type</label>
                    <select name="type_id" id="incomeType" class="form-select" required>
                        <?php foreach ($incomeTypes as $t): ?>
                            <option value="<?= $t['id'] ?>" data-name="<?= e(strtolower($t['name'])) ?>" data-code="<?= e(\App\Models\FinancialYear::deriveTypeCode($t['name'])) ?>"><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Member</label>
                    <select name="member_id" id="memberSelect" class="form-select">
                        <option value="">Member (optional)</option>
                    </select>
                    <small class="text-muted">Children Service members are excluded.</small>
                </div>
                <div class="mb-2"><input type="number" name="amount" class="form-control" placeholder="Amount" step="0.01" required></div>
                <div class="mb-2">
                    <label class="form-label small">Transaction Date</label>
                    <input type="date" name="transaction_date" id="transactionDate" class="form-control"
                        value="<?= date('Y-m-d') ?>"
                        <?= !empty($currentFinancialYear) ? 'min="'.$currentFinancialYear['start_date'].'" max="'.$currentFinancialYear['end_date'].'"' : '' ?>>
                </div>
                <?php if (!empty($nextReceiptNo)): ?>
                <div class="mb-2">
                    <label class="form-label small">Receipt No (auto)</label>
                    <input type="text" id="nextReceiptNo" class="form-control" value="<?= e($nextReceiptNo) ?>" readonly>
                    <small class="text-muted">Format: INC_TYPE_YY_MM_#### (e.g. INC_TH_26_06_0003)</small>
                </div>
                <?php endif; ?>
                <div class="mb-2">
                    <label class="form-label small">Payment Type</label>
                    <select name="payment_method" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="check">Cheque</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>
                <div class="mb-2"><input type="text" name="description" class="form-control" placeholder="Description"></div>
                <div class="form-check mb-3" id="smsOption">
                    <input type="checkbox" name="send_sms" value="1" class="form-check-input" id="sendSms" <?= $autoSendSms ? 'checked' : '' ?>>
                    <label class="form-check-label" for="sendSms">
                        <i class="bi bi-phone"></i> Send SMS receipt (tithe)
                    </label>
                    <small class="text-muted d-block" id="smsHint">SMS sent to member's phone on file</small>
                </div>
                <button type="submit" class="btn btn-success w-100" id="submitBtn" <?= empty($currentFinancialYear) ? 'disabled' : '' ?>>Record Income</button>
                <small class="text-muted d-block text-center mt-2" id="titheHint" style="display:none;">Tithe payments will generate a printable receipt after SMS</small>
            </form>
        </div></div>
        <?php
        $importType = 'income';
        $importCategories = array_column($incomeTypes, 'name');
        include __DIR__ . '/_import_upload.php';
        ?>
        <div class="card mt-3"><div class="card-body text-center">
            <a href="<?= url('finance/tithe-reminders') ?>" class="btn btn-outline-primary btn-sm w-100 mb-2"><i class="bi bi-bell"></i> Tithe SMS Reminders</a>
            <a href="<?= url('finance/sms-logs') ?>" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-chat-dots"></i> SMS Logs</a>
        </div></div>
    </div>
    <div class="col-xl-9 col-lg-8">
        <?php
        $clearPath = 'finance/income';
        $amountLabel = 'Total Income';
        $amountField = 'total_income';
        include __DIR__ . '/_transaction_filter.php';
        ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Income Transactions</span>
                <?php $exportType = 'income'; include __DIR__ . '/_export_buttons.php'; ?>
            </div>
            <div class="table-responsive"><table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Financial Year</th><th>Type</th><th>Member</th><th class="text-end">Amount</th><th>Payment</th><th>Receipt No</th></tr></thead>
            <tbody>
                <?php if (empty($transactions['data'])): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No income records found</td></tr>
                <?php else: foreach ($transactions['data'] as $t): ?>
                <tr>
                    <td><?= format_date($t['transaction_date']) ?></td>
                    <td><?= e($t['financial_year_name'] ?? '—') ?></td>
                    <td><?= e($t['type_name']) ?></td>
                    <td><?= e($t['member_name'] ?? 'Anonymous') ?></td>
                    <td class="text-success fw-bold text-end"><?= format_money($t['amount']) ?></td>
                    <td><?= e(format_payment_method($t['payment_method'])) ?></td>
                    <td><a href="<?= url('finance/receipt/'.$t['id']) ?>"><?= e($t['receipt_number'] ?? '—') ?></a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($transactions['data'])): ?>
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">Total Amount<?= ($dateFilter['active'] ?? false) ? ' (' . e($dateFilter['label']) . ')' : '' ?></td>
                    <td class="text-success text-end"><?= format_money($tableTotal ?? 0) ?></td>
                    <td colspan="2"><span class="text-muted small fw-normal"><?= (int) $transactions['total'] ?> record(s)</span></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table></div>
        <?php include __DIR__ . '/_transaction_pagination.php'; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.getElementById('incomeType');
    var memberSelect = document.getElementById('memberSelect');
    var smsOption = document.getElementById('smsOption');
    var smsHint = document.getElementById('smsHint');
    var titheHint = document.getElementById('titheHint');
    var titheMemberNote = document.getElementById('titheMemberNote');
    var submitBtn = document.getElementById('submitBtn');
    var receiptPreview = document.getElementById('nextReceiptNo');
    var transactionDate = document.getElementById('transactionDate');
    var nextReceiptNumbers = <?= json_encode($nextReceiptNumbers ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var allMembers = <?= json_encode($members, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var titheMembers = <?= json_encode($titheMembers ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    function isTitheType() {
        return typeSelect.options[typeSelect.selectedIndex].dataset.name.includes('tithe');
    }

    function renderMemberOptions(list) {
        var selected = memberSelect.value;
        memberSelect.innerHTML = '<option value="">Member (optional)</option>';
        list.forEach(function(m) {
            var opt = document.createElement('option');
            opt.value = m.id;
            opt.dataset.phone = m.phone || '';
            var label = m.first_name + ' ' + m.last_name;
            if (m.phone) {
                label += ' (' + m.phone + ')';
            }
            opt.textContent = label;
            memberSelect.appendChild(opt);
        });

        if (selected && Array.from(memberSelect.options).some(function(o) { return o.value === selected; })) {
            memberSelect.value = selected;
        } else {
            memberSelect.value = '';
        }
    }

    function updateSmsVisibility() {
        var isTithe = isTitheType();
        var hasMember = memberSelect.value !== '';
        smsOption.style.display = (isTithe && hasMember) ? 'block' : 'none';
        titheHint.style.display = isTithe ? 'block' : 'none';
        titheMemberNote.classList.toggle('d-none', !isTithe);
        submitBtn.textContent = isTithe ? 'Record Tithe & Generate Receipt' : 'Record Income';

        if (hasMember) {
            var phone = memberSelect.options[memberSelect.selectedIndex].dataset.phone;
            smsHint.textContent = phone ? 'SMS will be sent to ' + phone + ', then receipt opens' : 'No phone on file — receipt will still be generated';
        }
    }

    function updateReceiptPreview() {
        if (!receiptPreview || !transactionDate) {
            return;
        }

        var typeId = typeSelect.value;
        var base = nextReceiptNumbers[typeId] || '';
        var dateValue = transactionDate.value;
        if (!base || !dateValue) {
            return;
        }

        var parts = base.split('_');
        if (parts.length !== 5) {
            return;
        }

        var selected = typeSelect.options[typeSelect.selectedIndex];
        parts[0] = 'INC';
        parts[1] = selected.dataset.code || parts[1];
        var d = new Date(dateValue + 'T00:00:00');
        parts[2] = String(d.getFullYear()).slice(-2);
        parts[3] = String(d.getMonth() + 1).padStart(2, '0');
        receiptPreview.value = parts.join('_');
    }

    function updateMemberList() {
        renderMemberOptions(isTitheType() ? titheMembers : allMembers);
        updateSmsVisibility();
        updateReceiptPreview();
    }

    typeSelect.addEventListener('change', updateMemberList);
    memberSelect.addEventListener('change', updateSmsVisibility);
    if (transactionDate) {
        transactionDate.addEventListener('change', updateReceiptPreview);
    }
    updateMemberList();
});
</script>
