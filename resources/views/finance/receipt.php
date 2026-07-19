<?php
$isTithe = stripos($transaction['type_name'] ?? '', 'tithe') !== false;
$smsSent = $receiptMeta['sms']['sent'] ?? ($smsLog && $smsLog['status'] === 'sent');
$smsPhone = $receiptMeta['sms']['phone'] ?? ($smsLog['phone'] ?? null);
$flash = flash_message();

$memberId = $transaction['membership_id'] ?: ($transaction['member_code'] ?? '—');
$memberName = strtoupper($transaction['member_name'] ?? 'ANONYMOUS');
$amount = (float) $transaction['amount'];
$amountFigures = number_format($amount, 2, '.', '');
$paymentMonth = strtoupper(date('M', strtotime($transaction['transaction_date'])));
$paymentMode = strtoupper(format_payment_method($transaction['payment_method']));
$isCheque = stripos($transaction['payment_method'] ?? '', 'cheque') !== false
    || stripos($transaction['payment_method'] ?? '', 'check') !== false;
$chequeNo = $isCheque ? ($transaction['reference_number'] ?: '—') : '';
$receiptDate = format_date($transaction['transaction_date'], 'j M Y');
$serialNo = $transaction['receipt_number'] ?? '';
$amountWordsWithFigures = strtoupper(amount_in_words($amount)) . ' (' . $amountFigures . ')';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?> no-print text-center mb-3"><?= e($flash['message']) ?></div>
<?php endif; ?>

<?php if ($isTithe && $smsSent): ?>
<div class="receipt-sms-badge no-print">
    <i class="bi bi-check-circle-fill"></i> SMS confirmation sent to <?= e($smsPhone) ?>
</div>
<?php elseif ($isTithe && isset($receiptMeta['sms']) && !$receiptMeta['sms']['sent']): ?>
<div class="receipt-sms-badge receipt-sms-failed no-print">
    <i class="bi bi-exclamation-circle"></i> SMS not sent: <?= e($receiptMeta['sms']['error'] ?? 'Failed') ?>
</div>
<?php endif; ?>

<div class="receipt-slip receipt-slip--landscape <?= $isTithe ? 'receipt-slip--tithe' : 'receipt-slip--official' ?>">
    <div class="receipt-slip__top">
        <header class="receipt-slip__header">
            <div class="receipt-slip__logo">
                <img src="<?= church_logo() ?>" alt="Church logo">
            </div>
            <div class="receipt-slip__brand">
                <h1 class="receipt-slip__church-name"><?= e(strtoupper($config['church_name'])) ?></h1>
                <p class="receipt-slip__address"><?= e(strtoupper($config['church_address'])) ?></p>
            </div>
        </header>
        <h2 class="receipt-slip__title"><?= $isTithe ? 'TITHE RECEIPT' : 'OFFICIAL RECEIPT' ?></h2>
    </div>

    <div class="receipt-slip__content">
        <div class="receipt-slip__column">
            <div class="receipt-slip__row">
                <span class="receipt-slip__label">ID</span>
                <span class="receipt-slip__dots"></span>
                <span class="receipt-slip__value"><?= e($memberId) ?></span>
            </div>
            <div class="receipt-slip__row">
                <span class="receipt-slip__label">Received From</span>
                <span class="receipt-slip__dots"></span>
                <span class="receipt-slip__value receipt-slip__value--name"><?= e($memberName) ?></span>
            </div>
            <div class="receipt-slip__row">
                <span class="receipt-slip__label">The Sum of</span>
                <span class="receipt-slip__dots"></span>
                <span class="receipt-slip__value receipt-slip__value--words"><?= e($amountWordsWithFigures) ?></span>
            </div>
            <div class="receipt-slip__row">
                <span class="receipt-slip__label">Details of Payment</span>
                <span class="receipt-slip__dots"></span>
                <span class="receipt-slip__value"><?= e($paymentMonth) ?></span>
            </div>
        </div>

        <div class="receipt-slip__column">
            <?php if ($isTithe): ?>
            <div class="receipt-slip__row">
                <span class="receipt-slip__label">Speaking(I.W.)</span>
                <span class="receipt-slip__dots"></span>
                <span class="receipt-slip__value receipt-slip__value--amount">0.00</span>
            </div>
            <?php else: ?>
            <div class="receipt-slip__row">
                <span class="receipt-slip__label"><?= e($transaction['type_name']) ?></span>
                <span class="receipt-slip__dots"></span>
                <span class="receipt-slip__value receipt-slip__value--amount"><?= e($amountFigures) ?></span>
            </div>
            <?php endif; ?>
            <div class="receipt-slip__row">
                <span class="receipt-slip__label">Payment Mode</span>
                <span class="receipt-slip__dots"></span>
                <span class="receipt-slip__value"><?= e($paymentMode) ?></span>
            </div>
            <div class="receipt-slip__row">
                <span class="receipt-slip__label">Chq No</span>
                <span class="receipt-slip__dots"></span>
                <span class="receipt-slip__value"><?= e($chequeNo) ?></span>
            </div>
            <div class="receipt-slip__row">
                <span class="receipt-slip__label">Receipt No</span>
                <span class="receipt-slip__dots"></span>
                <span class="receipt-slip__value"><?= e($serialNo) ?></span>
            </div>
            <div class="receipt-slip__row">
                <span class="receipt-slip__label">Date</span>
                <span class="receipt-slip__dots"></span>
                <span class="receipt-slip__value"><?= e($receiptDate) ?></span>
            </div>
        </div>
    </div>

    <footer class="receipt-slip__footer">
        <div class="receipt-slip__total-row">
            <span class="receipt-slip__total-label">Total Amount</span>
            <span class="receipt-slip__total-value">GHC <?= e($amountFigures) ?></span>
        </div>
        <div class="receipt-slip__signature">
            <div class="receipt-slip__signature-line"></div>
            <span class="receipt-slip__signature-label">Signature :</span>
        </div>
    </footer>

    <?php if ($smsLog): ?>
    <p class="receipt-slip__sms-ref text-muted small no-print">SMS ref: <?= e($smsLog['provider_ref'] ?? 'N/A') ?></p>
    <?php endif; ?>
</div>

<div class="receipt-actions no-print text-center mt-4">
    <button onclick="window.print()" class="btn btn-primary btn-lg me-2">
        <i class="bi bi-printer"></i> Print Receipt
    </button>
    <?php if ($isTithe): ?>
    <a href="<?= url('finance/income') ?>" class="btn btn-success btn-lg me-2">
        <i class="bi bi-plus-circle"></i> Record Another Tithe
    </a>
    <?php endif; ?>
    <a href="<?= url('finance') ?>" class="btn btn-outline-secondary">Back to Finance</a>
</div>
