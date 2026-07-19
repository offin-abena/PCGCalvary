<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Finance;
use App\Models\FinancialYear;

class FinancialAccountsConversionService
{
    private array $config;
    private Finance $finance;

    public function __construct()
    {
        $this->finance = new Finance();
        $this->config = $this->mergeConfigWithDatabase();
    }

    public function suggestPeriodBalances(int $financialYearId): array
    {
        $year = (new FinancialYear())->find($financialYearId);
        if (!$year) {
            throw new \RuntimeException('Financial year not found.');
        }

        $receipts = $this->finance->getTransactionsByFinancialYear('income', $financialYearId);
        $payments = $this->finance->getTransactionsByFinancialYear('expense', $financialYearId);
        $assets = $this->loadAssets($financialYearId);
        $totalReceipts = array_sum(array_map(fn($r) => (float) $r['amount'], $receipts));
        $totalPayments = array_sum(array_map(fn($r) => (float) $r['amount'], $payments));

        $closing = $this->resolveClosingBalances($year, $assets, $totalReceipts, $totalPayments);
        $openingFund = (float) ($year['opening_accumulated_fund'] ?? 0);

        if ($openingFund <= 0) {
            $priorYear = $this->findPriorFinancialYear($financialYearId);
            if ($priorYear) {
                try {
                    $priorReport = $this->buildReport((int) $priorYear['id']);
                    $openingFund = (float) ($priorReport['financial_position']['accumulated_fund']['closing'] ?? 0);
                } catch (\Throwable) {
                    $openingFund = 0;
                }
            }
        }

        return [
            'opening_cash' => (float) ($year['opening_cash'] ?? 0),
            'opening_bank' => (float) ($year['opening_bank'] ?? 0),
            'closing_cash' => $closing['closing_cash'],
            'closing_bank' => $closing['closing_bank'],
            'opening_accumulated_fund' => $openingFund,
            'closing_source' => $closing['source'],
            'closing_detail' => $closing['detail'] ?? '',
        ];
    }

    public function buildReport(int $financialYearId, ?int $compareYearId = null): array
    {
        $year = (new FinancialYear())->find($financialYearId);
        if (!$year) {
            throw new \RuntimeException('Financial year not found.');
        }

        $receipts = $this->finance->getTransactionsByFinancialYear('income', $financialYearId);
        $payments = $this->finance->getTransactionsByFinancialYear('expense', $financialYearId);
        $accruals = $this->loadAccruals($financialYearId);
        $assets = $this->loadAssets($financialYearId);
        $liabilities = $this->loadLiabilities($financialYearId);

        $classifiedReceipts = $this->classifyTransactions($receipts, 'income');
        $classifiedPayments = $this->classifyTransactions($payments, 'expense');

        $depreciation = $this->calculateDepreciation($assets, $financialYearId);
        $incomeSection = $this->buildIncomeSection($classifiedReceipts, $accruals);
        $expenseSection = $this->buildExpenseSection($classifiedPayments, $accruals, $depreciation);

        $totalIncome = array_sum(array_column($incomeSection['lines'], 'adjusted_amount'));
        $totalExpenditure = array_sum(array_column($expenseSection['lines'], 'adjusted_amount'));
        $surplus = $totalIncome - $totalExpenditure;

        $capitalReceiptsTotal = $this->sumByClass($classifiedReceipts, 'capital_receipt');
        $capitalPaymentsTotal = $this->sumByClass($classifiedPayments, 'capital_payment');

        $totalReceipts = $this->sumCash($classifiedReceipts);
        $totalPayments = $this->sumCash($classifiedPayments);
        $closingBalances = $this->resolveClosingBalances($year, $assets, $totalReceipts, $totalPayments);

        $position = $this->buildFinancialPosition(
            $year,
            $assets,
            $liabilities,
            $depreciation,
            $surplus,
            $capitalReceiptsTotal,
            (float) ($year['opening_accumulated_fund'] ?? 0),
            $closingBalances
        );

        $validations = $this->validate(
            $year,
            $classifiedReceipts,
            $classifiedPayments,
            $incomeSection,
            $expenseSection,
            $position
        );

        $report = [
            'financial_year' => $year,
            'receipts_payments' => [
                'receipts' => $this->summarizeCashMovements($classifiedReceipts, 'receipt'),
                'payments' => $this->summarizeCashMovements($classifiedPayments, 'payment'),
                'total_receipts' => $this->sumCash($classifiedReceipts),
                'total_payments' => $this->sumCash($classifiedPayments),
                'net_cash' => $this->sumCash($classifiedReceipts) - $this->sumCash($classifiedPayments),
            ],
            'excluded_items' => [
                'receipts' => array_values(array_filter($classifiedReceipts, fn($r) => in_array($r['class'], ['excluded', 'non_operating'], true))),
                'payments' => array_values(array_filter($classifiedPayments, fn($r) => in_array($r['class'], ['excluded', 'non_operating'], true))),
            ],
            'capital_movements' => [
                'receipts' => array_values(array_filter($classifiedReceipts, fn($r) => $r['class'] === 'capital_receipt')),
                'payments' => array_values(array_filter($classifiedPayments, fn($r) => $r['class'] === 'capital_payment')),
                'total_receipts' => $capitalReceiptsTotal,
                'total_payments' => $capitalPaymentsTotal,
            ],
            'income_expenditure' => [
                'income' => $incomeSection,
                'expenditure' => $expenseSection,
                'total_income' => $totalIncome,
                'total_expenditure' => $totalExpenditure,
                'surplus' => $surplus,
                'deficit' => $surplus < 0 ? abs($surplus) : 0,
                'result_label' => $surplus >= 0 ? 'Surplus for the Year' : 'Deficit for the Year',
                'result_amount' => abs($surplus),
            ],
            'financial_position' => $position,
            'accrual_schedule' => $this->buildAccrualSchedule($incomeSection, $expenseSection),
            'depreciation_schedule' => $depreciation,
            'validations' => $validations,
            'flags' => $this->flagUnusualTransactions(array_merge($classifiedReceipts, $classifiedPayments)),
            'ratios' => $this->calculateRatios($totalIncome, $totalExpenditure, $surplus, $position),
            'closing_balances' => $closingBalances,
        ];

        if ($compareYearId && $compareYearId !== $financialYearId) {
            try {
                $report['comparative'] = $this->buildReport($compareYearId)['income_expenditure'];
                $report['comparative']['financial_year'] = (new FinancialYear())->find($compareYearId);
            } catch (\Throwable) {
                $report['comparative'] = null;
            }
        }

        return $report;
    }

    public function classifyCategory(string $name, string $side): string
    {
        $normalized = $this->normalizeName($name);
        $lists = [
            'excluded' => $this->config['excluded_non_operating'] ?? [],
            'capital_receipt' => $this->config['capital_receipts'] ?? [],
            'capital_payment' => $this->config['capital_payments'] ?? [],
            'revenue_income' => $this->config['revenue_income'] ?? [],
            'revenue_expenditure' => $this->config['revenue_expenditure'] ?? [],
        ];

        foreach ($lists as $class => $items) {
            if ($this->nameInList($normalized, $items)) {
                if ($class === 'excluded') {
                    return 'excluded';
                }
                if ($side === 'income' && $class === 'capital_receipt') {
                    return 'capital_receipt';
                }
                if ($side === 'expense' && $class === 'capital_payment') {
                    return 'capital_payment';
                }
                if ($side === 'income' && $class === 'revenue_income') {
                    return 'revenue_income';
                }
                if ($side === 'expense' && $class === 'revenue_expenditure') {
                    return 'revenue_expenditure';
                }
            }
        }

        if ($this->matchesKeywords($normalized, ['building fund', 'asset donation', 'loan received', 'sale of asset'])) {
            return $side === 'income' ? 'capital_receipt' : 'capital_payment';
        }

        if ($this->matchesKeywords($normalized, ['opening', 'transfer', 'inter account'])) {
            return 'excluded';
        }

        if ($this->matchesKeywords($normalized, ['land', 'building construct', 'vehicle', 'equipment purchase', 'sound', 'computer'])) {
            return $side === 'expense' ? 'capital_payment' : 'non_operating';
        }

        return $side === 'income' ? 'revenue_income' : 'revenue_expenditure';
    }

    public function saveAccrual(int $financialYearId, string $category, string $side, array $data): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO financial_accrual_adjustments
             (financial_year_id, category_name, account_side, outstanding_end, outstanding_begin, prepaid_end, prepaid_begin, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             outstanding_end = VALUES(outstanding_end),
             outstanding_begin = VALUES(outstanding_begin),
             prepaid_end = VALUES(prepaid_end),
             prepaid_begin = VALUES(prepaid_begin),
             notes = VALUES(notes)'
        );
        $stmt->execute([
            $financialYearId,
            $category,
            $side,
            (float) ($data['outstanding_end'] ?? 0),
            (float) ($data['outstanding_begin'] ?? 0),
            (float) ($data['prepaid_end'] ?? 0),
            (float) ($data['prepaid_begin'] ?? 0),
            $data['notes'] ?? null,
        ]);
    }

    private function classifyTransactions(array $transactions, string $side): array
    {
        $result = [];
        foreach ($transactions as $row) {
            $name = $row['type_name'] ?? 'Uncategorized';
            $class = $this->classifyCategory($name, $side);
            $result[] = array_merge($row, [
                'category' => $name,
                'class' => $class,
                'in_income_expenditure' => in_array($class, ['revenue_income', 'revenue_expenditure'], true),
            ]);
        }

        return $result;
    }

    private function buildIncomeSection(array $classified, array $accruals): array
    {
        $grouped = $this->groupByCategory(array_filter($classified, fn($r) => $r['class'] === 'revenue_income'));
        $lines = [];

        foreach ($grouped as $category => $cash) {
            $adj = $accruals['income'][$category] ?? [];
            $adjusted = $this->adjustIncome(
                $cash,
                (float) ($adj['outstanding_end'] ?? 0),
                (float) ($adj['outstanding_begin'] ?? 0)
            );
            $lines[] = [
                'category' => $category,
                'cash_amount' => $cash,
                'outstanding_end' => (float) ($adj['outstanding_end'] ?? 0),
                'outstanding_begin' => (float) ($adj['outstanding_begin'] ?? 0),
                'adjusted_amount' => $adjusted,
            ];
        }

        usort($lines, fn($a, $b) => $b['adjusted_amount'] <=> $a['adjusted_amount']);

        return ['lines' => $lines];
    }

    private function buildExpenseSection(array $classified, array $accruals, array $depreciation): array
    {
        $grouped = $this->groupByCategory(array_filter($classified, fn($r) => $r['class'] === 'revenue_expenditure'));
        $lines = [];

        foreach ($grouped as $category => $cash) {
            $adj = $accruals['expense'][$category] ?? [];
            $adjusted = $this->adjustExpense(
                $cash,
                (float) ($adj['outstanding_end'] ?? 0),
                (float) ($adj['outstanding_begin'] ?? 0),
                (float) ($adj['prepaid_end'] ?? 0),
                (float) ($adj['prepaid_begin'] ?? 0)
            );
            $lines[] = [
                'category' => $category,
                'cash_amount' => $cash,
                'outstanding_end' => (float) ($adj['outstanding_end'] ?? 0),
                'outstanding_begin' => (float) ($adj['outstanding_begin'] ?? 0),
                'prepaid_end' => (float) ($adj['prepaid_end'] ?? 0),
                'prepaid_begin' => (float) ($adj['prepaid_begin'] ?? 0),
                'adjusted_amount' => $adjusted,
            ];
        }

        $depTotal = array_sum(array_column($depreciation['lines'], 'depreciation_amount'));
        if ($depTotal > 0) {
            $lines[] = [
                'category' => 'Depreciation',
                'cash_amount' => 0,
                'outstanding_end' => 0,
                'outstanding_begin' => 0,
                'prepaid_end' => 0,
                'prepaid_begin' => 0,
                'adjusted_amount' => $depTotal,
                'is_depreciation' => true,
            ];
        }

        usort($lines, fn($a, $b) => $b['adjusted_amount'] <=> $a['adjusted_amount']);

        return ['lines' => $lines];
    }

    private function adjustIncome(float $cash, float $outstandingEnd, float $outstandingBegin): float
    {
        return $cash + $outstandingEnd - $outstandingBegin;
    }

    private function adjustExpense(float $cash, float $outstandingEnd, float $outstandingBegin, float $prepaidEnd, float $prepaidBegin): float
    {
        return $cash + $outstandingEnd - $outstandingBegin - $prepaidEnd + $prepaidBegin;
    }

    private function calculateDepreciation(array $assets, int $financialYearId): array
    {
        $lines = [];
        $db = Database::getInstance();

        foreach ($assets as $asset) {
            $category = $asset['category'] ?? 'Other Assets';
            if (!$this->isDepreciableCategory($category)) {
                continue;
            }
            $rate = $this->depreciationRate($category);
            $cost = (float) $asset['amount'];
            $depreciation = round($cost * $rate, 2);
            $prior = $this->priorAccumulatedDepreciation((int) $asset['id'], $financialYearId);
            $accumulated = $prior + $depreciation;
            $net = max(0, $cost - $accumulated);

            $lines[] = [
                'asset_id' => (int) $asset['id'],
                'name' => $asset['name'],
                'category' => $category,
                'cost' => $cost,
                'rate' => $rate,
                'rate_percent' => round($rate * 100, 1),
                'depreciation_amount' => $depreciation,
                'accumulated_depreciation' => $accumulated,
                'net_book_value' => $net,
            ];

            try {
                $stmt = $db->prepare(
                    'INSERT INTO financial_asset_depreciation
                     (asset_record_id, financial_year_id, cost, depreciation_rate, depreciation_amount, accumulated_depreciation, net_book_value)
                     VALUES (?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                     cost = VALUES(cost),
                     depreciation_rate = VALUES(depreciation_rate),
                     depreciation_amount = VALUES(depreciation_amount),
                     accumulated_depreciation = VALUES(accumulated_depreciation),
                     net_book_value = VALUES(net_book_value)'
                );
                $stmt->execute([
                    (int) $asset['id'],
                    $financialYearId,
                    $cost,
                    $rate,
                    $depreciation,
                    $accumulated,
                    $net,
                ]);
            } catch (\PDOException) {
                // Table may not exist until migration is run
            }
        }

        return [
            'lines' => $lines,
            'total_depreciation' => array_sum(array_column($lines, 'depreciation_amount')),
            'total_net_book_value' => array_sum(array_column($lines, 'net_book_value')),
        ];
    }

    private function buildFinancialPosition(
        array $year,
        array $assets,
        array $liabilities,
        array $depreciation,
        float $surplus,
        float $capitalDonations,
        float $openingFund,
        array $closingBalances
    ): array {
        $cashBankFromAssets = $this->sumCurrentAssetsCash($assets);
        $cashBank = $cashBankFromAssets > 0
            ? $cashBankFromAssets
            : $closingBalances['closing_cash'] + $closingBalances['closing_bank'];
        $receivables = $this->sumCurrentAssetsReceivables($assets);
        $fixedCategories = $this->config['fixed_asset_categories'] ?? [];
        $fixedGross = $this->sumAssetsByCategories($assets, $fixedCategories);
        $fixedNet = $depreciation['total_net_book_value'] > 0
            ? $depreciation['total_net_book_value']
            : $fixedGross;
        $fundsFromRegister = $this->sumAssetsByCategories($assets, $this->config['fund_categories'] ?? []);
        $liabilitiesFromAssets = $this->sumAssetsByCategories($assets, $this->config['liability_asset_categories'] ?? []);

        $currentAssetsTotal = $this->sumAssetsByCategories($assets, ['Current Assets', 'Cash & Bank', 'Accounts Receivable']);
        if ($currentAssetsTotal <= 0) {
            $currentAssetsTotal = $cashBank + $receivables;
        }

        $totalAssets = $fixedNet + $currentAssetsTotal;
        $totalLiabilities = array_sum(array_map(fn($l) => (float) $l['amount'], $liabilities)) + $liabilitiesFromAssets;
        $closingFund = $openingFund + $surplus + $capitalDonations;
        if ($fundsFromRegister > 0) {
            $closingFund = max($closingFund, $fundsFromRegister);
        }

        $assetLines = [
            ['label' => 'Non Current Assets (Net)', 'amount' => $fixedNet],
            ['label' => 'Current Assets', 'amount' => $currentAssetsTotal],
        ];
        if ($fundsFromRegister > 0) {
            $assetLines[] = ['label' => 'Funds', 'amount' => $fundsFromRegister];
        }

        return [
            'assets' => $assetLines,
            'liabilities' => array_map(fn($l) => [
                'label' => $l['name'] . ' (' . $l['category'] . ')',
                'amount' => (float) $l['amount'],
            ], $liabilities),
            'accumulated_fund' => [
                'opening' => $openingFund,
                'surplus' => max(0, $surplus),
                'deficit' => max(0, -$surplus),
                'capital_donations' => $capitalDonations,
                'closing' => $closingFund,
            ],
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_liabilities_and_fund' => $totalLiabilities + $closingFund,
            'balanced' => abs($totalAssets - ($totalLiabilities + $closingFund)) < 0.01,
            'opening_cash' => (float) ($year['opening_cash'] ?? 0),
            'opening_bank' => (float) ($year['opening_bank'] ?? 0),
            'closing_cash' => $closingBalances['closing_cash'],
            'closing_bank' => $closingBalances['closing_bank'],
            'closing_source' => $closingBalances['source'],
            'closing_detail' => $closingBalances['detail'] ?? '',
        ];
    }

    private function resolveClosingBalances(array $year, array $assets, float $totalReceipts, float $totalPayments): array
    {
        $hasManualCash = array_key_exists('closing_cash', $year) && $year['closing_cash'] !== null && $year['closing_cash'] !== '';
        $hasManualBank = array_key_exists('closing_bank', $year) && $year['closing_bank'] !== null && $year['closing_bank'] !== '';

        if ($hasManualCash || $hasManualBank) {
            return [
                'closing_cash' => $hasManualCash ? (float) $year['closing_cash'] : 0.0,
                'closing_bank' => $hasManualBank ? (float) $year['closing_bank'] : 0.0,
                'source' => 'manual',
                'detail' => 'Entered on financial year settings',
            ];
        }

        $cashAssets = array_filter(
            $assets,
            fn($a) => $this->isCashAssetCategory($a['category'] ?? '')
        );

        if (!empty($cashAssets)) {
            $cash = 0.0;
            $bank = 0.0;
            $lines = [];

            foreach ($cashAssets as $asset) {
                $amount = (float) $asset['amount'];
                if ($this->isBankAsset($asset['name'] ?? '')) {
                    $bank += $amount;
                    $lines[] = ($asset['name'] ?? 'Bank') . ': ' . number_format($amount, 2);
                } else {
                    $cash += $amount;
                    $lines[] = ($asset['name'] ?? 'Cash') . ': ' . number_format($amount, 2);
                }
            }

            return [
                'closing_cash' => $cash,
                'closing_bank' => $bank,
                'source' => 'assets',
                'detail' => 'From asset register: ' . implode('; ', $lines),
            ];
        }

        $openingCash = (float) ($year['opening_cash'] ?? 0);
        $openingBank = (float) ($year['opening_bank'] ?? 0);
        $openingTotal = $openingCash + $openingBank;
        $closingTotal = $openingTotal + $totalReceipts - $totalPayments;

        if ($openingTotal > 0) {
            return [
                'closing_cash' => round($closingTotal * ($openingCash / $openingTotal), 2),
                'closing_bank' => round($closingTotal * ($openingBank / $openingTotal), 2),
                'source' => 'calculated',
                'detail' => sprintf(
                    'Opening %s + Receipts %s − Payments %s',
                    number_format($openingTotal, 2),
                    number_format($totalReceipts, 2),
                    number_format($totalPayments, 2)
                ),
            ];
        }

        return [
            'closing_cash' => $closingTotal,
            'closing_bank' => 0.0,
            'source' => 'calculated',
            'detail' => sprintf(
                'Receipts %s − Payments %s (no opening balance set)',
                number_format($totalReceipts, 2),
                number_format($totalPayments, 2)
            ),
        ];
    }

    private function mergeConfigWithDatabase(): array
    {
        $config = require config_path('finance_account_classification.php');

        try {
            foreach ($this->finance->query('SELECT name FROM income_types ORDER BY name') as $row) {
                $name = trim($row['name']);
                if ($name !== '' && !$this->nameInClassificationLists($name, $config)) {
                    $config['revenue_income'][] = $name;
                }
            }

            foreach ($this->finance->query('SELECT name FROM expense_types ORDER BY name') as $row) {
                $name = trim($row['name']);
                if ($name !== '' && !$this->nameInClassificationLists($name, $config)) {
                    $config['revenue_expenditure'][] = $name;
                }
            }
        } catch (\PDOException) {
            // Use static config only
        }

        return $config;
    }

    private function nameInClassificationLists(string $name, array $config): bool
    {
        $lists = [
            'revenue_income',
            'capital_receipts',
            'revenue_expenditure',
            'capital_payments',
            'excluded_non_operating',
        ];

        foreach ($lists as $key) {
            if ($this->nameInList($this->normalizeName($name), $config[$key] ?? [])) {
                return true;
            }
        }

        return false;
    }

    private function isCashAssetCategory(string $category): bool
    {
        $normalized = $this->normalizeName($category);
        foreach ($this->config['cash_asset_categories'] ?? [] as $item) {
            if ($normalized === $this->normalizeName($item)) {
                return true;
            }
        }

        return false;
    }

    private function isBankAsset(string $name): bool
    {
        $normalized = $this->normalizeName($name);
        foreach ($this->config['bank_name_keywords'] ?? [] as $keyword) {
            if (str_contains($normalized, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function isDepreciableCategory(string $category): bool
    {
        $normalized = $this->normalizeName($category);
        foreach ($this->config['depreciable_asset_categories'] ?? [] as $item) {
            if ($normalized === $this->normalizeName($item)) {
                return true;
            }
        }

        return false;
    }

    private function isReceivableAsset(string $name): bool
    {
        $normalized = $this->normalizeName($name);

        return str_contains($normalized, 'receivable')
            || str_contains($normalized, 'debtor')
            || str_contains($normalized, 'owing');
    }

    private function sumCurrentAssetsCash(array $assets): float
    {
        $total = 0.0;
        foreach ($assets as $asset) {
            if (!$this->isCurrentAssetCategory($asset['category'] ?? '')) {
                continue;
            }
            if ($this->isReceivableAsset($asset['name'] ?? '')) {
                continue;
            }
            $total += (float) $asset['amount'];
        }

        return $total + $this->sumAssetsByCategories($assets, ['Cash & Bank', 'Cash', 'Bank']);
    }

    private function sumCurrentAssetsReceivables(array $assets): float
    {
        $total = 0.0;
        foreach ($assets as $asset) {
            if (!$this->isCurrentAssetCategory($asset['category'] ?? '')) {
                continue;
            }
            if ($this->isReceivableAsset($asset['name'] ?? '')) {
                $total += (float) $asset['amount'];
            }
        }

        return $total + $this->sumAssetsByCategories($assets, ['Accounts Receivable']);
    }

    private function isCurrentAssetCategory(string $category): bool
    {
        $normalized = $this->normalizeName($category);

        return $normalized === $this->normalizeName('Current Assets');
    }

    private function findPriorFinancialYear(int $financialYearId): ?array
    {
        $year = (new FinancialYear())->find($financialYearId);
        if (!$year) {
            return null;
        }

        return $this->finance->queryOne(
            'SELECT * FROM financial_years WHERE end_date < ? ORDER BY end_date DESC LIMIT 1',
            [$year['start_date']]
        );
    }

    private function validate(array $year, array $receipts, array $payments, array $income, array $expense, array $position): array
    {
        $totalReceipts = $this->sumCash($receipts);
        $totalPayments = $this->sumCash($payments);
        $openingCash = (float) ($year['opening_cash'] ?? 0) + (float) ($year['opening_bank'] ?? 0);
        $closingExpected = $openingCash + $totalReceipts - $totalPayments;
        $closingActual = (float) ($position['closing_cash'] ?? 0) + (float) ($position['closing_bank'] ?? 0);

        $capitalInIe = array_merge(
            array_filter($income['lines'], fn($l) => $this->isCapitalCategory($l['category'], 'income')),
            array_filter($expense['lines'], fn($l) => $this->isCapitalCategory($l['category'], 'expense'))
        );

        return [
            [
                'rule' => 'Opening Balance + Receipts − Payments = Closing Cash Balance',
                'passed' => abs($closingExpected - $closingActual) < 1.0,
                'detail' => sprintf(
                    'Opening %s + Receipts %s − Payments %s = %s (Closing %s%s)',
                    number_format($openingCash, 2),
                    number_format($totalReceipts, 2),
                    number_format($totalPayments, 2),
                    number_format($closingExpected, 2),
                    number_format($closingActual, 2),
                    !empty($position['closing_source']) ? ', source: ' . $position['closing_source'] : ''
                ),
            ],
            [
                'rule' => 'Total Assets = Total Liabilities + Accumulated Fund',
                'passed' => $position['balanced'],
                'detail' => sprintf(
                    'Assets %s vs Liabilities + Fund %s',
                    number_format($position['total_assets'], 2),
                    number_format($position['total_liabilities_and_fund'], 2)
                ),
            ],
            [
                'rule' => 'No capital items in Income & Expenditure Account',
                'passed' => count($capitalInIe) === 0,
                'detail' => count($capitalInIe) === 0
                    ? 'No capital categories posted to I&E.'
                    : count($capitalInIe) . ' capital item(s) incorrectly classified in I&E.',
            ],
            [
                'rule' => 'Building Fund and asset purchases on Balance Sheet only',
                'passed' => true,
                'detail' => 'Capital receipts/payments routed to Statement of Financial Position section.',
            ],
        ];
    }

    private function flagUnusualTransactions(array $transactions): array
    {
        $byCategory = [];
        foreach ($transactions as $tx) {
            $cat = $tx['category'] ?? 'Other';
            $byCategory[$cat][] = (float) $tx['amount'];
        }

        $flags = [];
        $multiplier = (float) ($this->config['unusual_amount_multiplier'] ?? 3);

        foreach ($transactions as $tx) {
            $cat = $tx['category'] ?? 'Other';
            $amounts = $byCategory[$cat] ?? [];
            $avg = count($amounts) ? array_sum($amounts) / count($amounts) : 0;
            $reasons = [];

            if ($avg > 0 && (float) $tx['amount'] > $avg * $multiplier) {
                $reasons[] = 'Amount exceeds ' . $multiplier . '× category average';
            }
            if (($tx['class'] ?? '') === 'revenue_income' && $this->classifyCategory($cat, 'income') === 'capital_receipt') {
                $reasons[] = 'Review capital vs revenue classification';
            }
            if (empty($tx['type_name'])) {
                $reasons[] = 'Missing category';
            }

            if ($reasons) {
                $flags[] = [
                    'date' => $tx['transaction_date'],
                    'category' => $cat,
                    'amount' => (float) $tx['amount'],
                    'type' => $tx['transaction_type'] ?? '',
                    'reasons' => $reasons,
                ];
            }
        }

        return $flags;
    }

    private function calculateRatios(float $income, float $expenditure, float $surplus, array $position): array
    {
        return [
            ['label' => 'Surplus / Income', 'value' => $income > 0 ? round(($surplus / $income) * 100, 1) . '%' : 'N/A'],
            ['label' => 'Expenditure / Income', 'value' => $income > 0 ? round(($expenditure / $income) * 100, 1) . '%' : 'N/A'],
            ['label' => 'Net Assets', 'value' => $position['total_assets'] - $position['total_liabilities']],
            ['label' => 'Accumulated Fund (Closing)', 'value' => $position['accumulated_fund']['closing']],
        ];
    }

    private function buildAccrualSchedule(array $income, array $expense): array
    {
        $rows = [];
        foreach ($income['lines'] as $line) {
            $rows[] = array_merge($line, ['side' => 'income']);
        }
        foreach ($expense['lines'] as $line) {
            if (!empty($line['is_depreciation'])) {
                continue;
            }
            $rows[] = array_merge($line, ['side' => 'expense']);
        }

        return $rows;
    }

    private function summarizeCashMovements(array $classified, string $label): array
    {
        $groups = [];
        foreach ($classified as $row) {
            $cat = $row['category'];
            $groups[$cat] = ($groups[$cat] ?? 0) + (float) $row['amount'];
        }
        arsort($groups);
        $lines = [];
        foreach ($groups as $category => $amount) {
            $lines[] = ['category' => $category, 'amount' => $amount, 'class' => $this->classifyCategory($category, $label === 'receipt' ? 'income' : 'expense')];
        }

        return $lines;
    }

    private function groupByCategory(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $cat = $row['category'];
            $groups[$cat] = ($groups[$cat] ?? 0) + (float) $row['amount'];
        }

        return $groups;
    }

    private function sumCash(array $rows): float
    {
        return array_sum(array_map(fn($r) => (float) $r['amount'], $rows));
    }

    private function sumByClass(array $rows, string $class): float
    {
        return array_sum(array_map(
            fn($r) => $r['class'] === $class ? (float) $r['amount'] : 0,
            $rows
        ));
    }

    private function sumAssetsByCategories(array $assets, array $categories): float
    {
        $normalized = array_map(fn($c) => $this->normalizeName($c), $categories);
        $total = 0;
        foreach ($assets as $asset) {
            if (in_array($this->normalizeName($asset['category'] ?? ''), $normalized, true)) {
                $total += (float) $asset['amount'];
            }
        }

        return $total;
    }

    private function loadAccruals(int $financialYearId): array
    {
        try {
            $db = Database::getInstance();
            $rows = $db->prepare(
                'SELECT * FROM financial_accrual_adjustments WHERE financial_year_id = ?'
            );
            $rows->execute([$financialYearId]);
            $data = ['income' => [], 'expense' => []];
            foreach ($rows->fetchAll() as $row) {
                $data[$row['account_side']][$row['category_name']] = $row;
            }

            return $data;
        } catch (\PDOException) {
            return ['income' => [], 'expense' => []];
        }
    }

    private function loadAssets(int $financialYearId): array
    {
        return $this->finance->query(
            'SELECT * FROM financial_asset_records WHERE financial_year_id = ? ORDER BY category, name',
            [$financialYearId]
        );
    }

    private function loadLiabilities(int $financialYearId): array
    {
        return $this->finance->query(
            'SELECT * FROM financial_liability_records WHERE financial_year_id = ? ORDER BY category, name',
            [$financialYearId]
        );
    }

    private function priorAccumulatedDepreciation(int $assetId, int $financialYearId): float
    {
        try {
            $row = $this->finance->queryOne(
                'SELECT accumulated_depreciation FROM financial_asset_depreciation
                 WHERE asset_record_id = ? AND financial_year_id < ?
                 ORDER BY financial_year_id DESC LIMIT 1',
                [$assetId, $financialYearId]
            );

            return (float) ($row['accumulated_depreciation'] ?? 0);
        } catch (\PDOException) {
            return 0;
        }
    }

    private function depreciationRate(string $category): float
    {
        $rates = $this->config['depreciation_rates'] ?? [];
        foreach ($rates as $name => $rate) {
            if ($this->normalizeName($name) === $this->normalizeName($category)) {
                return (float) $rate;
            }
        }

        if ($this->normalizeName($category) === $this->normalizeName('Non Current Assets')) {
            return 0.10;
        }

        if ($this->matchesKeywords($category, ['computer', 'sound'])) {
            return 0.20;
        }
        if ($this->matchesKeywords($category, ['vehicle', 'motor'])) {
            return 0.25;
        }
        if ($this->matchesKeywords($category, ['land', 'building'])) {
            return 0.02;
        }

        return 0.10;
    }

    private function isCapitalCategory(string $name, string $side): bool
    {
        $class = $this->classifyCategory($name, $side);

        return in_array($class, ['capital_receipt', 'capital_payment'], true);
    }

    private function normalizeName(string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    }

    private function nameInList(string $normalized, array $list): bool
    {
        foreach ($list as $item) {
            if ($normalized === $this->normalizeName($item)) {
                return true;
            }
        }

        return false;
    }

    private function matchesKeywords(string $normalized, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($normalized, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
