<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Http\Controllers\Controller;
use App\Models\Finance;
use App\Models\FinancialYear;
use App\Models\Member;
use App\Services\AgeGroupService;
use App\Services\FinanceImportService;
use App\Services\TitheSmsService;

class FinanceController extends Controller
{
    private Finance $model;

    public function __construct()
    {
        $this->model = new Finance();
    }

    public function index(): void
    {
        $fyModel = new FinancialYear();
        $currentFy = $fyModel->getCurrent();
        $startDate = $this->input('start_date', $currentFy['start_date'] ?? date('Y-m-01'));
        $endDate = $this->input('end_date', $currentFy['end_date'] ?? date('Y-m-t'));

        $fyId = $currentFy['id'] ?? null;

        $this->render('finance/index', [
            'title' => 'Financial Management',
            'summary' => $this->model->getSummary($startDate, $endDate),
            'incomeByType' => $this->model->getIncomeByType($startDate, $endDate),
            'recentTransactions' => $this->model->getTransactions('income', 1, $startDate, $endDate),
            'assetsLiabilities' => $this->model->getAssetsLiabilitiesSummary($fyId),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currentFinancialYear' => $currentFy,
        ]);
    }

    public function income(): void
    {
        $smsConfig = config('sms');
        $titheSms = new TitheSmsService();
        $fyModel = new FinancialYear();
        $currentFy = $fyModel->getCurrent();
        $dateFilter = resolve_transaction_date_filter();
        $page = (int) $this->input('page', 1);
        $perPage = resolve_per_page();

        $this->render('finance/income', [
            'title' => 'Income Records',
            'transactions' => $this->model->getTransactions(
                'income',
                $page,
                $dateFilter['start_date'],
                $dateFilter['end_date'],
                $perPage
            ),
            'perPage' => $perPage,
            'filterSummary' => $dateFilter['active']
                ? $this->model->getSummary($dateFilter['start_date'], $dateFilter['end_date'])
                : null,
            'tableTotal' => $this->model->getTransactionTotal('income', $dateFilter['start_date'], $dateFilter['end_date']),
            'dateFilter' => $dateFilter,
            'incomeTypes' => $incomeTypes = $this->model->getIncomeTypes(),
            'members' => (new Member())->getActiveExcludingAgeGroup('children_service'),
            'smsEnabled' => $titheSms->getSmsStats() !== null,
            'autoSendSms' => $smsConfig['auto_send_tithe_receipt'],
            'currentFinancialYear' => $currentFy,
            'nextReceiptNumbers' => $currentFy
                ? $fyModel->peekNextNumbersForTypes($currentFy, 'income', date('Y-m-d'), $incomeTypes)
                : [],
            'nextReceiptNo' => $currentFy && !empty($incomeTypes)
                ? $fyModel->peekNextNumber($currentFy, 'income', $incomeTypes[0]['name'], date('Y-m-d'))
                : null,
        ]);
    }

    public function expense(): void
    {
        $fyModel = new FinancialYear();
        $currentFy = $fyModel->getCurrent();
        $dateFilter = resolve_transaction_date_filter();
        $page = (int) $this->input('page', 1);
        $perPage = resolve_per_page();

        $this->render('finance/expense', [
            'title' => 'Expense Records',
            'transactions' => $this->model->getTransactions(
                'expense',
                $page,
                $dateFilter['start_date'],
                $dateFilter['end_date'],
                $perPage
            ),
            'perPage' => $perPage,
            'filterSummary' => $dateFilter['active']
                ? $this->model->getSummary($dateFilter['start_date'], $dateFilter['end_date'])
                : null,
            'tableTotal' => $this->model->getTransactionTotal('expense', $dateFilter['start_date'], $dateFilter['end_date']),
            'dateFilter' => $dateFilter,
            'expenseTypes' => $expenseTypes = $this->model->getExpenseTypes(),
            'currentFinancialYear' => $currentFy,
            'nextVoucherNumbers' => $currentFy
                ? $fyModel->peekNextNumbersForTypes($currentFy, 'expense', date('Y-m-d'), $expenseTypes)
                : [],
            'nextVoucherNo' => $currentFy && !empty($expenseTypes)
                ? $fyModel->peekNextNumber($currentFy, 'expense', $expenseTypes[0]['name'], date('Y-m-d'))
                : null,
        ]);
    }

    public function assets(): void
    {
        $fyModel = new FinancialYear();
        $currentFy = $fyModel->getCurrent();
        $categories = config('finance_categories');

        $this->render('finance/assets', [
            'title' => 'Asset Records',
            'records' => $this->model->getAssetRecords((int) $this->input('page', 1), $currentFy['id'] ?? null),
            'summary' => $this->model->getAssetsLiabilitiesSummary($currentFy['id'] ?? null),
            'categories' => $categories['asset_categories'],
            'categoryHints' => $categories['asset_category_hints'] ?? [],
            'currentFinancialYear' => $currentFy,
            'nextAssetNo' => $currentFy ? $fyModel->peekNextNumber($currentFy, 'asset') : null,
        ]);
    }

    public function liabilities(): void
    {
        $fyModel = new FinancialYear();
        $currentFy = $fyModel->getCurrent();
        $categories = config('finance_categories');

        $this->render('finance/liabilities', [
            'title' => 'Liability Records',
            'records' => $this->model->getLiabilityRecords((int) $this->input('page', 1), $currentFy['id'] ?? null),
            'summary' => $this->model->getAssetsLiabilitiesSummary($currentFy['id'] ?? null),
            'categories' => $categories['liability_categories'],
            'currentFinancialYear' => $currentFy,
            'nextLiabilityNo' => $currentFy ? $fyModel->peekNextNumber($currentFy, 'liability') : null,
        ]);
    }

    public function storeAsset(): void
    {
        $this->storeBalanceSheetRecord('asset', 'finance/assets');
    }

    public function storeLiability(): void
    {
        $this->storeBalanceSheetRecord('liability', 'finance/liabilities');
    }

    private function storeBalanceSheetRecord(string $type, string $redirectPath): void
    {
        $errors = $this->validate([
            'category' => 'required',
            'name' => 'required',
            'amount' => 'required',
            'record_date' => 'required',
        ]);

        if ($errors) {
            $this->flash('danger', reset($errors));
            $this->redirect($redirectPath);
        }

        $recordDate = $this->input('record_date', date('Y-m-d'));
        $fyModel = new FinancialYear();
        $currentFy = $fyModel->getCurrent();

        if (!$currentFy) {
            $this->flash('danger', 'No active financial year is set. Please ask the Super Admin or Pastor to configure one in Settings.');
            $this->redirect($redirectPath);
        }

        if (!$fyModel->isDateInYear($currentFy, $recordDate)) {
            $this->flash('danger', 'Record date must fall within the current financial year (' . format_date($currentFy['start_date']) . ' — ' . format_date($currentFy['end_date']) . ').');
            $this->redirect($redirectPath);
        }

        try {
            $referenceNumber = $fyModel->generateTransactionNumber((int) $currentFy['id'], $type);
        } catch (\RuntimeException $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect($redirectPath);
        }

        if ($type === 'asset') {
            $recordModel = new class extends \App\Core\Model { protected string $table = 'financial_asset_records'; };
        } else {
            $recordModel = new class extends \App\Core\Model { protected string $table = 'financial_liability_records'; };
        }

        $data = [
            'financial_year_id' => $currentFy['id'],
            'reference_number' => $referenceNumber,
            'category' => trim($this->input('category')),
            'name' => trim($this->input('name')),
            'amount' => (float) $this->input('amount'),
            'record_date' => $recordDate,
            'description' => trim($this->input('description', '')),
            'recorded_by' => Auth::id(),
        ];

        if ($type === 'liability') {
            $data['creditor'] = trim($this->input('creditor', '')) ?: null;
            $data['due_date'] = $this->input('due_date') ?: null;
        }

        $id = $recordModel->create($data);

        AuditLog::log('create', $type === 'asset' ? 'financial_assets' : 'financial_liabilities', $id);
        $this->flash('success', ucfirst($type) . ' recorded successfully (' . $referenceNumber . ').');
        $this->redirect($redirectPath);
    }

    public function exportIncomeExcel(): void
    {
        $this->exportTransactionsExcel('income');
    }

    public function exportIncomePdf(): void
    {
        $this->exportTransactionsPdf('income');
    }

    public function exportExpenseExcel(): void
    {
        $this->exportTransactionsExcel('expense');
    }

    public function exportExpensePdf(): void
    {
        $this->exportTransactionsPdf('expense');
    }

    private function exportTransactionsExcel(string $type): void
    {
        $dateFilter = resolve_transaction_date_filter();
        $rows = $this->model->getTransactionsForExport($type, $dateFilter['start_date'], $dateFilter['end_date']);
        $total = $this->model->getTransactionTotal($type, $dateFilter['start_date'], $dateFilter['end_date']);
        $filename = $type . '_records_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');

        if ($type === 'income') {
            fputcsv($out, ['Date', 'Financial Year', 'Category', 'Member', 'Description', 'Amount', 'Payment', 'Receipt No']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['transaction_date'],
                    $r['financial_year_name'] ?? '',
                    $r['type_name'],
                    $r['member_name'] ?? 'Anonymous',
                    $r['description'] ?? '',
                    number_format((float) $r['amount'], 2, '.', ''),
                    format_payment_method($r['payment_method']),
                    $r['receipt_number'] ?? '',
                ]);
            }
            fputcsv($out, ['', '', '', '', 'TOTAL', number_format($total, 2, '.', ''), '', '']);
        } else {
            fputcsv($out, ['Date', 'Financial Year', 'Category', 'Description', 'Amount', 'Payment', 'Voucher No']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['transaction_date'],
                    $r['financial_year_name'] ?? '',
                    $r['type_name'],
                    $r['description'] ?? '',
                    number_format((float) $r['amount'], 2, '.', ''),
                    format_payment_method($r['payment_method']),
                    $r['voucher_number'] ?? '',
                ]);
            }
            fputcsv($out, ['', '', '', 'TOTAL', number_format($total, 2, '.', ''), '', '']);
        }

        fclose($out);
        AuditLog::log('export_excel', 'finance', null, ['type' => $type, 'rows' => count($rows)]);
        exit;
    }

    private function exportTransactionsPdf(string $type): void
    {
        $config = config('church');
        $dateFilter = resolve_transaction_date_filter();
        $rows = $this->model->getTransactionsForExport($type, $dateFilter['start_date'], $dateFilter['end_date']);
        $total = $this->model->getTransactionTotal($type, $dateFilter['start_date'], $dateFilter['end_date']);

        AuditLog::log('export_pdf', 'finance', null, ['type' => $type, 'rows' => count($rows)]);

        $this->render('finance/transactions-export-pdf', [
            'title' => ucfirst($type) . ' Records',
            'type' => $type,
            'rows' => $rows,
            'tableTotal' => $total,
            'dateFilter' => $dateFilter,
            'config' => $config,
            'layout' => 'layouts/print',
        ]);
    }

    public function importIncome(): void
    {
        $this->handleSpreadsheetImport('income', 'finance/income');
    }

    public function importExpense(): void
    {
        $this->handleSpreadsheetImport('expense', 'finance/expense');
    }

    public function downloadIncomeTemplate(): void
    {
        $this->downloadImportTemplate('income');
    }

    public function downloadExpenseTemplate(): void
    {
        $this->downloadImportTemplate('expense');
    }

    private function handleSpreadsheetImport(string $type, string $redirectPath): void
    {
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('danger', 'Please select a valid Excel or CSV file to upload.');
            $this->redirect($redirectPath);
        }

        $config = config('church');
        $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx'], true)) {
            $this->flash('danger', 'Only .csv and .xlsx files are supported.');
            $this->redirect($redirectPath);
        }

        if ($_FILES['import_file']['size'] > $config['max_upload_size']) {
            $this->flash('danger', 'File is too large. Maximum size is 5MB.');
            $this->redirect($redirectPath);
        }

        $importDir = $config['upload_path'] . 'imports/';
        if (!is_dir($importDir)) {
            mkdir($importDir, 0755, true);
        }

        $tempPath = $importDir . uniqid('import_') . '.' . $ext;
        if (!move_uploaded_file($_FILES['import_file']['tmp_name'], $tempPath)) {
            $this->flash('danger', 'Failed to upload file.');
            $this->redirect($redirectPath);
        }

        try {
            $service = new FinanceImportService();
            $result = $service->import($tempPath, $type);
            @unlink($tempPath);

            AuditLog::log('import', 'finance', null, [
                'type' => $type,
                'imported' => $result['imported'],
                'failed' => $result['failed'],
            ]);

            $message = "Import complete: {$result['imported']} of {$result['total_rows']} row(s) imported.";
            if ($result['failed'] > 0) {
                $message .= ' ' . $result['failed'] . ' row(s) failed.';
                $_SESSION['import_errors'] = array_slice($result['errors'], 0, 10);
            }

            $this->flash($result['imported'] > 0 ? 'success' : 'warning', $message);
        } catch (\Throwable $e) {
            @unlink($tempPath);
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect($redirectPath);
    }

    private function downloadImportTemplate(string $type): void
    {
        $service = new FinanceImportService();
        $headers = $service->getTemplateHeaders($type);
        $sample = $service->getTemplateSampleRow($type);
        $filename = $type . '_import_template.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        fputcsv($out, $sample);
        fclose($out);
        exit;
    }

    public function store(): void
    {
        $type = $this->input('transaction_type');
        $memberId = $this->input('member_id') ?: null;
        $amount = (float) $this->input('amount');
        $transactionDate = $this->input('transaction_date', date('Y-m-d'));
        $incomeTypeId = (int) $this->input('type_id');

        $fyModel = new FinancialYear();
        $currentFy = $fyModel->getCurrent();

        if (!$currentFy) {
            $this->flash('danger', 'No active financial year is set. Please ask the Super Admin or Pastor to configure one in Settings.');
            $this->redirect('finance/' . $type);
        }

        if (!$fyModel->isDateInYear($currentFy, $transactionDate)) {
            $this->flash('danger', 'Transaction date must fall within the current financial year (' . format_date($currentFy['start_date']) . ' — ' . format_date($currentFy['end_date']) . ').');
            $this->redirect('finance/' . $type);
        }

        if ($type === 'income' && $memberId) {
            $member = (new Member())->getWithFamily((int) $memberId);
            if ($member && (new AgeGroupService())->memberBelongsToGroup($member, 'children_service')) {
                $this->flash('danger', 'Children Service members cannot be linked to income records.');
                $this->redirect('finance/income');
            }
        }

        $titheSms = new TitheSmsService();
        $isTithe = $type === 'income' && $titheSms->isTitheType($incomeTypeId);

        try {
            $typeLabel = $this->resolveTransactionTypeLabel($type, $incomeTypeId);
            $documentNumber = $fyModel->generateTransactionNumber(
                (int) $currentFy['id'],
                $type,
                $typeLabel,
                $transactionDate
            );
        } catch (\RuntimeException $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect('finance/' . $type);
        }

        $paymentMethod = $this->input('payment_method', 'cash');
        if ($type === 'expense' && !in_array($paymentMethod, ['cash', 'check', 'mobile_money'], true)) {
            $this->flash('danger', 'Invalid payment type for expense.');
            $this->redirect('finance/expense');
        }

        $data = [
            'transaction_type' => $type,
            'type_id' => $incomeTypeId,
            'member_id' => $memberId,
            'amount' => $amount,
            'transaction_date' => $transactionDate,
            'financial_year_id' => $currentFy['id'],
            'payment_method' => $paymentMethod,
            'reference_number' => $this->input('reference_number'),
            'description' => $this->input('description'),
            'recorded_by' => Auth::id(),
        ];

        if ($type === 'income') {
            $data['receipt_number'] = $documentNumber;
        } else {
            $data['voucher_number'] = $documentNumber;
        }

        $id = $this->model->create($data);
        $receiptNumber = $documentNumber;

        $flashMessage = ucfirst($type) . ' recorded successfully.';
        $smsStatus = null;

        // Send SMS then generate receipt for tithe payments
        if ($isTithe && $memberId && $this->input('send_sms')) {
            $member = (new Member())->find((int) $memberId);
            if ($member) {
                $smsResult = $titheSms->sendTitheReceipt($member, $amount, $receiptNumber, $transactionDate);
                if ($smsResult && $smsResult['success']) {
                    $smsStatus = ['sent' => true, 'phone' => $member['phone']];
                    $flashMessage .= ' SMS sent to ' . $member['phone'] . '.';
                } elseif ($smsResult && !$smsResult['success']) {
                    $smsStatus = ['sent' => false, 'phone' => $member['phone'], 'error' => $smsResult['message'] ?? 'Send failed'];
                    $flashMessage .= ' SMS failed: ' . ($smsStatus['error']) . '.';
                } elseif (empty($member['phone'])) {
                    $smsStatus = ['sent' => false, 'error' => 'No phone number on file'];
                    $flashMessage .= ' No phone number on file for SMS.';
                }
            }
        }

        AuditLog::log('create', 'finance', $id, $smsStatus ? ['sms' => $smsStatus] : null);

        if ($isTithe) {
            $_SESSION['receipt_meta'] = [
                'sms' => $smsStatus,
                'generated_at' => date('Y-m-d H:i:s'),
            ];
            $this->flash('success', $flashMessage . ' Receipt generated.');
            $this->redirect('finance/receipt/' . $id);
        }

        $this->flash('success', $flashMessage);
        $this->redirect('finance/' . $type);
    }

    public function titheReminders(): void
    {
        $titheSms = new TitheSmsService();
        $smsConfig = config('sms');

        $this->render('finance/tithe-reminders', [
            'title' => 'Tithe SMS Reminders',
            'smsStats' => $titheSms->getSmsStats(),
            'smsConfig' => $smsConfig,
            'pendingMembers' => (new Member())->getTithePendingMembers(),
        ]);
    }

    public function sendTitheReminders(): void
    {
        $smsConfig = config('sms');
        $titheSms = new TitheSmsService();
        $onlyWithoutTithe = $this->input('only_without_tithe', '1') === '1';

        $results = $titheSms->sendTitheReminders($onlyWithoutTithe);

        AuditLog::log('send_tithe_reminders', 'finance', null, $results);
        $this->flash('success', "Tithe reminders sent: {$results['sent']} delivered, {$results['failed']} failed, {$results['skipped']} skipped.");
        $this->redirect('finance/tithe-reminders');
    }

    public function smsLogs(): void
    {
        $titheSms = new TitheSmsService();
        $this->render('finance/sms-logs', [
            'title' => 'SMS Logs',
            'smsStats' => $titheSms->getSmsStats(),
        ]);
    }

    public function pledges(): void
    {
        $this->render('finance/pledges', [
            'title' => 'Pledge Management',
            'pledges' => $this->model->getPledges((int) $this->input('page', 1)),
            'members' => (new Member())->query("SELECT id, first_name, last_name FROM members ORDER BY last_name"),
        ]);
    }

    public function storePledge(): void
    {
        $pledgeModel = new class extends \App\Core\Model { protected string $table = 'pledges'; };

        $pledgeId = $pledgeModel->create([
            'member_id' => $this->input('member_id'),
            'amount' => $this->input('amount'),
            'pledge_date' => $this->input('pledge_date', date('Y-m-d')),
            'due_date' => $this->input('due_date') ?: null,
            'purpose' => $this->input('purpose'),
            'notes' => $this->input('notes'),
        ]);

        AuditLog::log('create', 'pledges', $pledgeId);
        $this->flash('success', 'Pledge recorded successfully.');
        $this->redirect('finance/pledges');
    }

    public function budgets(): void
    {
        $this->render('finance/budgets', [
            'title' => 'Budget Management',
            'budgets' => $this->model->getBudgets(),
            'expenseTypes' => $this->model->getExpenseTypes(),
        ]);
    }

    public function payroll(): void
    {
        $payroll = $this->model->query(
            "SELECT p.*, CONCAT(m.first_name, ' ', m.last_name) as member_name 
             FROM payroll p JOIN members m ON p.member_id = m.id ORDER BY p.pay_period_start DESC"
        );
        $members = (new Member())->query("SELECT id, first_name, last_name FROM members ORDER BY last_name");

        $this->render('finance/payroll', [
            'title' => 'Payroll Management',
            'payroll' => $payroll,
            'members' => $members,
        ]);
    }

    public function reconciliation(): void
    {
        $reconciliations = $this->model->query(
            "SELECT br.*, u.username as reconciled_by_name FROM bank_reconciliations br 
             LEFT JOIN users u ON br.reconciled_by = u.id ORDER BY br.reconciliation_date DESC"
        );

        $this->render('finance/reconciliation', [
            'title' => 'Bank Reconciliation',
            'reconciliations' => $reconciliations,
            'summary' => $this->model->getSummary(),
        ]);
    }

    public function storeReconciliation(): void
    {
        $bankBalance = (float) $this->input('bank_balance');
        $bookBalance = (float) $this->input('book_balance');

        $id = (new class extends \App\Core\Model { protected string $table = 'bank_reconciliations'; })->create([
            'reconciliation_date' => $this->input('reconciliation_date', date('Y-m-d')),
            'bank_balance' => $bankBalance,
            'book_balance' => $bookBalance,
            'difference' => $bankBalance - $bookBalance,
            'notes' => $this->input('notes'),
            'reconciled_by' => Auth::id(),
        ]);

        AuditLog::log('create', 'reconciliation', $id);
        $this->flash('success', 'Reconciliation recorded.');
        $this->redirect('finance/reconciliation');
    }

    public function receipt(string $id): void
    {
        $transaction = $this->model->queryOne(
            "SELECT ft.*, it.name as type_name,
             TRIM(CONCAT(m.first_name, ' ', IFNULL(CONCAT(m.middle_name, ' '), ''), m.last_name)) as member_name,
             m.membership_id, m.member_code,
             fy.name as financial_year_name, fy.year_label as financial_year_label
             FROM financial_transactions ft
             LEFT JOIN income_types it ON ft.type_id = it.id
             LEFT JOIN members m ON ft.member_id = m.id
             LEFT JOIN financial_years fy ON ft.financial_year_id = fy.id
             WHERE ft.id = ?",
            [$id]
        );

        if (!$transaction) {
            $this->redirect('finance');
        }

        $receiptMeta = $_SESSION['receipt_meta'] ?? null;
        unset($_SESSION['receipt_meta']);

        $smsLog = null;
        if (stripos($transaction['type_name'] ?? '', 'tithe') !== false) {
            $smsLog = $this->model->queryOne(
                "SELECT * FROM sms_logs WHERE context = 'tithe_receipt' AND member_id = ? 
                 AND message LIKE ? ORDER BY created_at DESC LIMIT 1",
                [$transaction['member_id'], '%' . $transaction['receipt_number'] . '%']
            );
        }

        $this->render('finance/receipt', [
            'title' => 'Tithe Receipt - ' . $transaction['receipt_number'],
            'transaction' => $transaction,
            'receiptMeta' => $receiptMeta,
            'smsLog' => $smsLog,
            'layout' => 'layouts/print',
        ]);
    }

    private function resolveTransactionTypeLabel(string $transactionType, int $typeId): string
    {
        if ($transactionType === 'income') {
            $row = $this->model->queryOne('SELECT name FROM income_types WHERE id = ?', [$typeId]);
        } else {
            $row = $this->model->queryOne('SELECT name FROM expense_types WHERE id = ?', [$typeId]);
        }

        return trim($row['name'] ?? 'General');
    }
}
