<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Attendance;
use App\Models\Finance;
use App\Models\FinancialYear;
use App\Services\FinancialAccountsConversionService;

class ReportController extends Controller
{
    public function index(): void
    {
        $this->render('reports/index', ['title' => 'Reports & Analytics']);
    }

    public function membership(): void
    {
        $memberModel = new Member();
        $stats = $memberModel->getStats();

        $growth = $memberModel->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
             FROM members WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY month ORDER BY month"
        );

        $byStatus = $memberModel->query(
            "SELECT membership_status, COUNT(*) as count FROM members GROUP BY membership_status"
        );

        $byGender = $memberModel->query(
            "SELECT gender, COUNT(*) as count FROM members GROUP BY gender"
        );

        $this->render('reports/membership', [
            'title' => 'Membership Report',
            'stats' => $stats,
            'growth' => $growth,
            'byStatus' => $byStatus,
            'byGender' => $byGender,
        ]);
    }

    public function attendance(): void
    {
        $attendanceModel = new Attendance();
        $startDate = $this->input('start_date', date('Y-m-01'));
        $endDate = $this->input('end_date', date('Y-m-t'));

        $this->render('reports/attendance', [
            'title' => 'Attendance Report',
            'analytics' => $attendanceModel->getAnalytics($startDate, $endDate),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function incomeExpenditure(): void
    {
        $this->renderFinancialYearReport('income-expenditure', 'reports/income-expenditure', 'Income & Expenditure Report');
    }

    public function receiptsPayments(): void
    {
        $this->renderFinancialYearReport('receipts-payments', 'reports/receipts-payments', 'Receipts & Payments Report');
    }

    public function accountsConversion(): void
    {
        $financialYear = resolve_report_financial_year();
        $financialYears = (new FinancialYear())->getAll();
        $compareYearId = (int) $this->input('compare_year_id', 0);
        $report = null;
        $error = null;
        $suggestedBalances = null;

        if ($financialYear) {
            try {
                $service = new FinancialAccountsConversionService();
                $report = $service->buildReport(
                    (int) $financialYear['id'],
                    $compareYearId > 0 ? $compareYearId : null
                );
                $suggestedBalances = $service->suggestPeriodBalances((int) $financialYear['id']);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $this->render('reports/accounts-conversion', [
            'title' => 'Accounts Conversion Report',
            'financialYear' => $financialYear,
            'financialYears' => $financialYears,
            'compareYearId' => $compareYearId,
            'report' => $report,
            'suggestedBalances' => $suggestedBalances,
            'error' => $error,
        ]);
    }

    public function saveAccountsConversionSettings(): void
    {
        $fyId = (int) $this->input('financial_year_id');
        if ($fyId <= 0) {
            $this->flash('danger', 'Financial year is required.');
            $this->redirect('reports/accounts-conversion');
        }

        $fyModel = new FinancialYear();
        try {
            $fyModel->update($fyId, [
                'opening_cash' => (float) $this->input('opening_cash', 0),
                'opening_bank' => (float) $this->input('opening_bank', 0),
                'closing_cash' => $this->input('closing_cash') !== '' ? (float) $this->input('closing_cash') : null,
                'closing_bank' => $this->input('closing_bank') !== '' ? (float) $this->input('closing_bank') : null,
                'opening_accumulated_fund' => (float) $this->input('opening_accumulated_fund', 0),
            ]);
        } catch (\Throwable $e) {
            $this->flash('danger', 'Could not save period balances. Run scripts/run_financial_conversion_migration.php first.');
            $this->redirect('reports/accounts-conversion?financial_year_id=' . $fyId);
        }

        $service = new FinancialAccountsConversionService();
        $categories = json_decode($this->input('accruals_json', '[]'), true) ?: [];
        foreach ($categories as $row) {
            if (empty($row['category']) || empty($row['side'])) {
                continue;
            }
            $service->saveAccrual($fyId, $row['category'], $row['side'], $row);
        }

        $this->flash('success', 'Conversion settings saved.');
        $this->redirect('reports/accounts-conversion?financial_year_id=' . $fyId);
    }

    public function exportAccountsConversionExcel(): void
    {
        $financialYear = resolve_report_financial_year();
        if (!$financialYear) {
            $this->flash('danger', 'No financial year selected.');
            $this->redirect('reports/accounts-conversion');
        }

        $report = (new FinancialAccountsConversionService())->buildReport((int) $financialYear['id']);
        $filename = 'accounts_conversion_' . $financialYear['year_label'] . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');

        fputcsv($out, ['Accounts Conversion', $financialYear['name']]);
        fputcsv($out, []);

        fputcsv($out, ['INCOME & EXPENDITURE']);
        fputcsv($out, ['Category', 'Cash', 'Adjusted', 'Side']);
        foreach ($report['income_expenditure']['income']['lines'] as $line) {
            fputcsv($out, [$line['category'], $line['cash_amount'], $line['adjusted_amount'], 'Income']);
        }
        foreach ($report['income_expenditure']['expenditure']['lines'] as $line) {
            fputcsv($out, [$line['category'], $line['cash_amount'] ?? 0, $line['adjusted_amount'], 'Expenditure']);
        }
        fputcsv($out, ['Total Income', '', $report['income_expenditure']['total_income']]);
        fputcsv($out, ['Total Expenditure', '', $report['income_expenditure']['total_expenditure']]);
        fputcsv($out, [$report['income_expenditure']['result_label'], '', $report['income_expenditure']['result_amount']]);
        fputcsv($out, []);

        fputcsv($out, ['STATEMENT OF FINANCIAL POSITION']);
        foreach ($report['financial_position']['assets'] as $a) {
            fputcsv($out, ['Asset', $a['label'], $a['amount']]);
        }
        foreach ($report['financial_position']['liabilities'] as $l) {
            fputcsv($out, ['Liability', $l['label'], $l['amount']]);
        }
        fputcsv($out, ['Accumulated Fund (Closing)', '', $report['financial_position']['accumulated_fund']['closing']]);

        fclose($out);
        exit;
    }

    public function exportAccountsConversionPdf(): void
    {
        $financialYear = resolve_report_financial_year();
        if (!$financialYear) {
            $this->flash('danger', 'No financial year selected.');
            $this->redirect('reports/accounts-conversion');
        }

        $config = config('church');
        $compareYearId = (int) $this->input('compare_year_id', 0);
        $service = new FinancialAccountsConversionService();
        $report = $service->buildReport(
            (int) $financialYear['id'],
            $compareYearId > 0 ? $compareYearId : null
        );

        $this->render('reports/accounts-conversion-pdf', [
            'title' => 'Accounts Conversion Report',
            'backUrl' => 'reports/accounts-conversion?financial_year_id=' . (int) $financialYear['id'],
            'config' => $config,
            'financialYear' => $financialYear,
            'report' => $report,
            'layout' => 'layouts/print',
        ]);
    }

    public function applyAccountsConversionSuggestion(): void
    {
        $fyId = (int) $this->input('financial_year_id');
        if ($fyId <= 0) {
            $this->flash('danger', 'Financial year is required.');
            $this->redirect('reports/accounts-conversion');
        }

        try {
            $suggested = (new FinancialAccountsConversionService())->suggestPeriodBalances($fyId);
            (new FinancialYear())->update($fyId, [
                'opening_cash' => $suggested['opening_cash'],
                'opening_bank' => $suggested['opening_bank'],
                'closing_cash' => $suggested['closing_cash'],
                'closing_bank' => $suggested['closing_bank'],
                'opening_accumulated_fund' => $suggested['opening_accumulated_fund'],
            ]);
            $this->flash('success', 'Period balances applied from ' . $suggested['closing_source'] . ' source.');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('reports/accounts-conversion?financial_year_id=' . $fyId);
    }

    public function exportIncomeExpenditureExcel(): void
    {
        $this->exportFinancialYearReportExcel('income-expenditure');
    }

    public function exportIncomeExpenditurePdf(): void
    {
        $this->exportFinancialYearReportPdf('income-expenditure', 'Income & Expenditure Report', 'reports/income-expenditure');
    }

    public function exportReceiptsPaymentsExcel(): void
    {
        $this->exportFinancialYearReportExcel('receipts-payments');
    }

    public function exportReceiptsPaymentsPdf(): void
    {
        $this->exportFinancialYearReportPdf('receipts-payments', 'Receipts & Payments Report', 'reports/receipts-payments');
    }

    private function renderFinancialYearReport(string $reportType, string $view, string $title): void
    {
        $financialYear = resolve_report_financial_year();
        $financeModel = new Finance();
        $data = [
            'title' => $title,
            'reportType' => $reportType,
            'financialYear' => $financialYear,
            'financialYears' => (new FinancialYear())->getAll(),
        ];

        if ($financialYear) {
            $fyId = (int) $financialYear['id'];
            $data['summary'] = $financeModel->getSummaryByFinancialYear($fyId);
            $data['incomeByType'] = $financeModel->getIncomeByTypeForFinancialYear($fyId);
            $data['expenseByType'] = $financeModel->getExpenseByTypeForFinancialYear($fyId);
            $data['monthly'] = $financeModel->getMonthlyByFinancialYear($fyId);
            $data['receipts'] = $financeModel->getTransactionsByFinancialYear('income', $fyId);
            $data['payments'] = $financeModel->getTransactionsByFinancialYear('expense', $fyId);
        }

        $this->render($view, $data);
    }

    private function exportFinancialYearReportExcel(string $reportType): void
    {
        $financialYear = resolve_report_financial_year();
        if (!$financialYear) {
            $this->flash('danger', 'No financial year selected.');
            $this->redirect('reports/' . $reportType);
        }

        $financeModel = new Finance();
        $fyId = (int) $financialYear['id'];
        $summary = $financeModel->getSummaryByFinancialYear($fyId);
        $slug = str_replace('-', '_', $reportType);
        $filename = $slug . '_' . $financialYear['year_label'] . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');

        fputcsv($out, [$financialYear['name'], $financialYear['start_date'] . ' to ' . $financialYear['end_date']]);

        if ($reportType === 'income-expenditure') {
            fputcsv($out, ['INCOME']);
            fputcsv($out, ['Category', 'Records', 'Amount']);
            foreach ($financeModel->getIncomeByTypeForFinancialYear($fyId) as $row) {
                if ($row['total'] <= 0) continue;
                fputcsv($out, [$row['name'], $row['record_count'], number_format((float) $row['total'], 2, '.', '')]);
            }
            fputcsv($out, ['Total Income', '', number_format($summary['total_income'], 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['EXPENDITURE']);
            fputcsv($out, ['Category', 'Records', 'Amount']);
            foreach ($financeModel->getExpenseByTypeForFinancialYear($fyId) as $row) {
                if ($row['total'] <= 0) continue;
                fputcsv($out, [$row['name'], $row['record_count'], number_format((float) $row['total'], 2, '.', '')]);
            }
            fputcsv($out, ['Total Expenditure', '', number_format($summary['total_expense'], 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['Surplus / (Deficit)', number_format($summary['balance'], 2, '.', '')]);
        } else {
            fputcsv($out, ['RECEIPTS']);
            fputcsv($out, ['Category', 'Records', 'Amount']);
            foreach ($financeModel->getIncomeByTypeForFinancialYear($fyId) as $row) {
                if ($row['total'] <= 0) continue;
                fputcsv($out, [$row['name'], $row['record_count'], number_format((float) $row['total'], 2, '.', '')]);
            }
            fputcsv($out, ['Total Receipts', '', number_format($summary['total_income'], 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['PAYMENTS']);
            fputcsv($out, ['Category', 'Records', 'Amount']);
            foreach ($financeModel->getExpenseByTypeForFinancialYear($fyId) as $row) {
                if ($row['total'] <= 0) continue;
                fputcsv($out, [$row['name'], $row['record_count'], number_format((float) $row['total'], 2, '.', '')]);
            }
            fputcsv($out, ['Total Payments', '', number_format($summary['total_expense'], 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['Net (Receipts - Payments)', number_format($summary['balance'], 2, '.', '')]);
        }

        fclose($out);
        exit;
    }

    private function exportFinancialYearReportPdf(string $reportType, string $title, string $backUrl): void
    {
        $financialYear = resolve_report_financial_year();
        if (!$financialYear) {
            $this->flash('danger', 'No financial year selected.');
            $this->redirect($backUrl);
        }

        $config = config('church');
        $financeModel = new Finance();
        $fyId = (int) $financialYear['id'];

        $this->render('reports/fy-report-pdf', [
            'title' => $title,
            'reportType' => $reportType,
            'backUrl' => $backUrl,
            'config' => $config,
            'financialYear' => $financialYear,
            'summary' => $financeModel->getSummaryByFinancialYear($fyId),
            'incomeByType' => $financeModel->getIncomeByTypeForFinancialYear($fyId),
            'expenseByType' => $financeModel->getExpenseByTypeForFinancialYear($fyId),
            'receipts' => $financeModel->getTransactionsByFinancialYear('income', $fyId),
            'payments' => $financeModel->getTransactionsByFinancialYear('expense', $fyId),
            'layout' => 'layouts/print',
        ]);
    }

    public function financial(): void
    {
        $financeModel = new Finance();
        $startDate = $this->input('start_date', date('Y-01-01'));
        $endDate = $this->input('end_date', date('Y-m-t'));

        $monthly = $financeModel->query(
            "SELECT DATE_FORMAT(transaction_date, '%Y-%m') as month,
             SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as income,
             SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as expense
             FROM financial_transactions WHERE transaction_date BETWEEN ? AND ?
             GROUP BY month ORDER BY month",
            [$startDate, $endDate]
        );

        $this->render('reports/financial', [
            'title' => 'Financial Report',
            'summary' => $financeModel->getSummary($startDate, $endDate),
            'incomeByType' => $financeModel->getIncomeByType($startDate, $endDate),
            'expenseByType' => $financeModel->getExpenseByType($startDate, $endDate),
            'incomeRecords' => $financeModel->getReportTransactions('income', $startDate, $endDate),
            'expenseRecords' => $financeModel->getReportTransactions('expense', $startDate, $endDate),
            'monthly' => $monthly,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function export(string $type): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $type . '_report_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        switch ($type) {
            case 'members':
                fputcsv($output, ['Code', 'First Name', 'Last Name', 'Email', 'Phone', 'Status', 'Joined']);
                $members = (new Member())->all('last_name ASC');
                foreach ($members as $m) {
                    fputcsv($output, [$m['member_code'], $m['first_name'], $m['last_name'], $m['email'], $m['phone'], $m['membership_status'], $m['created_at']]);
                }
                break;

            case 'finance':
                fputcsv($output, ['Date', 'Type', 'Amount', 'Method', 'Receipt', 'Description']);
                $transactions = (new Finance())->all('transaction_date DESC');
                foreach ($transactions as $t) {
                    fputcsv($output, [$t['transaction_date'], $t['transaction_type'], $t['amount'], $t['payment_method'], $t['receipt_number'], $t['description']]);
                }
                break;

            case 'attendance':
                fputcsv($output, ['Date', 'Service', 'Member', 'Check-in Time', 'Method']);
                $records = (new Attendance())->query(
                    "SELECT s.session_date, st.name, CONCAT(m.first_name, ' ', m.last_name) as member, ar.check_in_time, ar.check_in_method
                     FROM attendance_records ar
                     JOIN attendance_sessions s ON ar.session_id = s.id
                     JOIN service_types st ON s.service_type_id = st.id
                     JOIN members m ON ar.member_id = m.id
                     ORDER BY ar.check_in_time DESC"
                );
                foreach ($records as $r) {
                    fputcsv($output, [$r['session_date'], $r['name'], $r['member'], $r['check_in_time'], $r['check_in_method']]);
                }
                break;
        }

        fclose($output);
        exit;
    }
}
