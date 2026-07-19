<?php

namespace App\Models;

use App\Core\Model;

class Finance extends Model
{
    protected string $table = 'financial_transactions';

    public function getTransactions(
        string $type,
        int $page = 1,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 50
    ): array {
        $where = "transaction_type = ?";
        $params = [$type];

        if ($startDate && $endDate) {
            $where .= " AND transaction_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM financial_transactions WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT ft.*, 
                CASE WHEN ft.transaction_type = 'income' THEN it.name ELSE et.name END as type_name,
                CONCAT(m.first_name, ' ', m.last_name) as member_name,
                fy.name as financial_year_name, fy.year_label as financial_year_label
                FROM financial_transactions ft
                LEFT JOIN income_types it ON ft.transaction_type = 'income' AND ft.type_id = it.id
                LEFT JOIN expense_types et ON ft.transaction_type = 'expense' AND ft.type_id = et.id
                LEFT JOIN members m ON ft.member_id = m.id
                LEFT JOIN financial_years fy ON ft.financial_year_id = fy.id
                WHERE {$where} ORDER BY ft.transaction_date DESC LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage) ?: 1,
        ];
    }

    public function getTransactionTotal(string $type, ?string $startDate = null, ?string $endDate = null): float
    {
        $where = 'transaction_type = ?';
        $params = [$type];

        if ($startDate && $endDate) {
            $where .= ' AND transaction_date BETWEEN ? AND ?';
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE {$where}");
        $stmt->execute($params);

        return (float) $stmt->fetchColumn();
    }

    public function getTransactionsForExport(string $type, ?string $startDate = null, ?string $endDate = null): array
    {
        $where = 'ft.transaction_type = ?';
        $params = [$type];

        if ($startDate && $endDate) {
            $where .= ' AND ft.transaction_date BETWEEN ? AND ?';
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql = "SELECT ft.*,
                CASE WHEN ft.transaction_type = 'income' THEN it.name ELSE et.name END as type_name,
                CONCAT(m.first_name, ' ', m.last_name) as member_name,
                fy.name as financial_year_name
                FROM financial_transactions ft
                LEFT JOIN income_types it ON ft.transaction_type = 'income' AND ft.type_id = it.id
                LEFT JOIN expense_types et ON ft.transaction_type = 'expense' AND ft.type_id = et.id
                LEFT JOIN members m ON ft.member_id = m.id
                LEFT JOIN financial_years fy ON ft.financial_year_id = fy.id
                WHERE {$where}
                ORDER BY ft.transaction_date DESC, ft.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $dateFilter = '';
        $params = [];

        if ($startDate && $endDate) {
            $dateFilter = ' AND transaction_date BETWEEN ? AND ?';
            $params = [$startDate, $endDate];
        }

        $incomeStmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE transaction_type = 'income' {$dateFilter}");
        $incomeStmt->execute($params);
        $income = (float) $incomeStmt->fetchColumn();

        $expenseStmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE transaction_type = 'expense' {$dateFilter}");
        $expenseStmt->execute($params);
        $expense = (float) $expenseStmt->fetchColumn();

        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    public function getExpenseByType(?string $startDate = null, ?string $endDate = null): array
    {
        $sql = "SELECT et.name, COALESCE(SUM(ft.amount), 0) as total
                FROM expense_types et
                LEFT JOIN financial_transactions ft ON ft.type_id = et.id AND ft.transaction_type = 'expense'";
        $params = [];

        if ($startDate && $endDate) {
            $sql .= " AND ft.transaction_date BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }

        $sql .= " GROUP BY et.id, et.name ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getReportTransactions(string $type, string $startDate, string $endDate): array
    {
        $sql = "SELECT ft.*,
                CASE WHEN ft.transaction_type = 'income' THEN it.name ELSE et.name END as type_name,
                CONCAT(m.first_name, ' ', m.last_name) as member_name,
                fy.name as financial_year_name, fy.year_label as financial_year_label
                FROM financial_transactions ft
                LEFT JOIN income_types it ON ft.transaction_type = 'income' AND ft.type_id = it.id
                LEFT JOIN expense_types et ON ft.transaction_type = 'expense' AND ft.type_id = et.id
                LEFT JOIN members m ON ft.member_id = m.id
                LEFT JOIN financial_years fy ON ft.financial_year_id = fy.id
                WHERE ft.transaction_type = ? AND ft.transaction_date BETWEEN ? AND ?
                ORDER BY ft.transaction_date DESC, ft.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type, $startDate, $endDate]);
        return $stmt->fetchAll();
    }

    public function getIncomeByType(?string $startDate = null, ?string $endDate = null): array
    {
        $sql = "SELECT it.name, COALESCE(SUM(ft.amount), 0) as total
                FROM income_types it
                LEFT JOIN financial_transactions ft ON ft.type_id = it.id AND ft.transaction_type = 'income'";
        $params = [];

        if ($startDate && $endDate) {
            $sql .= " AND ft.transaction_date BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }

        $sql .= " GROUP BY it.id, it.name ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getIncomeTypes(): array
    {
        return $this->query("SELECT * FROM income_types WHERE is_active = 1 ORDER BY name");
    }

    public function getExpenseTypes(): array
    {
        return $this->query("SELECT * FROM expense_types WHERE is_active = 1 ORDER BY name");
    }

    public function incomeTypeNameExists(string $name, ?int $excludeId = null): bool
    {
        return $this->typeNameExists('income_types', $name, $excludeId);
    }

    public function expenseTypeNameExists(string $name, ?int $excludeId = null): bool
    {
        return $this->typeNameExists('expense_types', $name, $excludeId);
    }

    public function getDuplicateIncomeTypeNames(): array
    {
        return $this->getDuplicateTypeNames('income_types');
    }

    public function getDuplicateExpenseTypeNames(): array
    {
        return $this->getDuplicateTypeNames('expense_types');
    }

    private function typeNameExists(string $table, string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM {$table} WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))";
        $params = [$name];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return (bool) $this->queryOne($sql, $params);
    }

    private function getDuplicateTypeNames(string $table): array
    {
        $rows = $this->query(
            "SELECT LOWER(TRIM(name)) AS norm_name
             FROM {$table}
             GROUP BY LOWER(TRIM(name))
             HAVING COUNT(*) > 1"
        );

        return array_column($rows, 'norm_name');
    }

    public function getPledges(int $page = 1): array
    {
        $offset = ($page - 1) * 15;
        $total = (int) $this->db->query("SELECT COUNT(*) FROM pledges")->fetchColumn();

        $data = $this->query(
            "SELECT p.*, CONCAT(m.first_name, ' ', m.last_name) as member_name 
             FROM pledges p JOIN members m ON p.member_id = m.id 
             ORDER BY p.pledge_date DESC LIMIT 15 OFFSET ?",
            [$offset]
        );

        return ['data' => $data, 'total' => $total, 'page' => $page, 'total_pages' => (int) ceil($total / 15)];
    }

    public function getBudgets(): array
    {
        return $this->query(
            "SELECT b.*, CONCAT(u.username) as created_by_name FROM budgets b 
             LEFT JOIN users u ON b.created_by = u.id ORDER BY b.fiscal_year DESC"
        );
    }

    public function getAssetRecords(int $page = 1, ?int $financialYearId = null): array
    {
        $where = '1=1';
        $params = [];

        if ($financialYearId) {
            $where .= ' AND far.financial_year_id = ?';
            $params[] = $financialYearId;
        }

        $offset = ($page - 1) * 15;
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM financial_asset_records far WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT far.*, fy.name as financial_year_name, u.username as recorded_by_name
                FROM financial_asset_records far
                JOIN financial_years fy ON far.financial_year_id = fy.id
                LEFT JOIN users u ON far.recorded_by = u.id
                WHERE {$where}
                ORDER BY far.record_date DESC, far.id DESC
                LIMIT 15 OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'total_pages' => (int) ceil($total / 15)];
    }

    public function getLiabilityRecords(int $page = 1, ?int $financialYearId = null): array
    {
        $where = '1=1';
        $params = [];

        if ($financialYearId) {
            $where .= ' AND flr.financial_year_id = ?';
            $params[] = $financialYearId;
        }

        $offset = ($page - 1) * 15;
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM financial_liability_records flr WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT flr.*, fy.name as financial_year_name, u.username as recorded_by_name
                FROM financial_liability_records flr
                JOIN financial_years fy ON flr.financial_year_id = fy.id
                LEFT JOIN users u ON flr.recorded_by = u.id
                WHERE {$where}
                ORDER BY flr.record_date DESC, flr.id DESC
                LIMIT 15 OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'total_pages' => (int) ceil($total / 15)];
    }

    public function getSummaryByFinancialYear(int $financialYearId): array
    {
        $incomeStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions
             WHERE transaction_type = 'income' AND financial_year_id = ?"
        );
        $incomeStmt->execute([$financialYearId]);
        $income = (float) $incomeStmt->fetchColumn();

        $expenseStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions
             WHERE transaction_type = 'expense' AND financial_year_id = ?"
        );
        $expenseStmt->execute([$financialYearId]);
        $expense = (float) $expenseStmt->fetchColumn();

        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    public function getIncomeByTypeForFinancialYear(int $financialYearId): array
    {
        $stmt = $this->db->prepare(
            "SELECT it.name, COALESCE(SUM(ft.amount), 0) as total, COUNT(ft.id) as record_count
             FROM income_types it
             LEFT JOIN financial_transactions ft
                ON ft.type_id = it.id AND ft.transaction_type = 'income' AND ft.financial_year_id = ?
             GROUP BY it.id, it.name
             ORDER BY total DESC, it.name ASC"
        );
        $stmt->execute([$financialYearId]);
        return $stmt->fetchAll();
    }

    public function getExpenseByTypeForFinancialYear(int $financialYearId): array
    {
        $stmt = $this->db->prepare(
            "SELECT et.name, COALESCE(SUM(ft.amount), 0) as total, COUNT(ft.id) as record_count
             FROM expense_types et
             LEFT JOIN financial_transactions ft
                ON ft.type_id = et.id AND ft.transaction_type = 'expense' AND ft.financial_year_id = ?
             GROUP BY et.id, et.name
             ORDER BY total DESC, et.name ASC"
        );
        $stmt->execute([$financialYearId]);
        return $stmt->fetchAll();
    }

    public function getTransactionsByFinancialYear(string $type, int $financialYearId): array
    {
        $sql = "SELECT ft.*,
                CASE WHEN ft.transaction_type = 'income' THEN it.name ELSE et.name END as type_name,
                CONCAT(m.first_name, ' ', m.last_name) as member_name,
                fy.name as financial_year_name
                FROM financial_transactions ft
                LEFT JOIN income_types it ON ft.transaction_type = 'income' AND ft.type_id = it.id
                LEFT JOIN expense_types et ON ft.transaction_type = 'expense' AND ft.type_id = et.id
                LEFT JOIN members m ON ft.member_id = m.id
                LEFT JOIN financial_years fy ON ft.financial_year_id = fy.id
                WHERE ft.transaction_type = ? AND ft.financial_year_id = ?
                ORDER BY ft.transaction_date ASC, ft.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type, $financialYearId]);
        return $stmt->fetchAll();
    }

    public function getMonthlyByFinancialYear(int $financialYearId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(transaction_date, '%Y-%m') as month,
             SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as income,
             SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as expense
             FROM financial_transactions
             WHERE financial_year_id = ?
             GROUP BY month ORDER BY month"
        );
        $stmt->execute([$financialYearId]);
        return $stmt->fetchAll();
    }

    public function getAssetsLiabilitiesSummary(?int $financialYearId = null): array
    {
        $fyWhere = $financialYearId ? ' WHERE financial_year_id = ?' : '';
        $params = $financialYearId ? [$financialYearId] : [];

        $assetStmt = $this->db->prepare(
            "SELECT category, COALESCE(SUM(amount), 0) AS total
             FROM financial_asset_records{$fyWhere}
             GROUP BY category"
        );
        $assetStmt->execute($params);
        $byCategory = [];
        foreach ($assetStmt->fetchAll() as $row) {
            $byCategory[$row['category']] = (float) $row['total'];
        }

        $nonCurrent = ($byCategory['Non Current Assets'] ?? 0)
            + ($byCategory['Land & Buildings'] ?? 0)
            + ($byCategory['Equipment & Furniture'] ?? 0)
            + ($byCategory['Investments'] ?? 0)
            + ($byCategory['Other Assets'] ?? 0);
        $current = ($byCategory['Current Assets'] ?? 0)
            + ($byCategory['Cash & Bank'] ?? 0)
            + ($byCategory['Accounts Receivable'] ?? 0);
        $funds = $byCategory['Funds'] ?? 0;
        $liabilitiesOnAssets = $byCategory['Current Liabilities'] ?? 0;

        $liabilityStmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM financial_liability_records' . $fyWhere
        );
        $liabilityStmt->execute($params);
        $liabilitiesFromTable = (float) $liabilityStmt->fetchColumn();

        $totalAssets = $nonCurrent + $current;
        $totalLiabilities = $liabilitiesFromTable + $liabilitiesOnAssets;

        return [
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_funds' => $funds,
            'non_current_assets' => $nonCurrent,
            'current_assets' => $current,
            'net_worth' => $totalAssets - $totalLiabilities,
            'by_category' => $byCategory,
        ];
    }
}
