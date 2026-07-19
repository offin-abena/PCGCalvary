<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class FinancialYear extends Model
{
    protected string $table = 'financial_years';

    public function getCurrent(): ?array
    {
        return $this->queryOne(
            "SELECT * FROM financial_years WHERE is_current = 1 AND status = 'active' LIMIT 1"
        );
    }

    public function getAll(): array
    {
        return $this->query(
            "SELECT fy.*, u.username as created_by_name, cu.username as closed_by_name
             FROM financial_years fy
             LEFT JOIN users u ON fy.created_by = u.id
             LEFT JOIN users cu ON fy.closed_by = cu.id
             ORDER BY fy.year_label DESC"
        );
    }

    public function findByYearLabel(int $yearLabel): ?array
    {
        return $this->queryOne('SELECT * FROM financial_years WHERE year_label = ?', [$yearLabel]);
    }

    public function createYear(array $data, int $createdBy): int
    {
        $id = $this->create([
            'name' => $data['name'],
            'year_label' => $data['year_label'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => 'active',
            'is_current' => 0,
            'created_by' => $createdBy,
        ]);

        if (!empty($data['set_current'])) {
            $this->setCurrent($id);
        }

        return $id;
    }

    public function setCurrent(int $id): void
    {
        $year = $this->find($id);
        if (!$year || $year['status'] !== 'active') {
            throw new \RuntimeException('Only an active financial year can be set as current.');
        }

        $this->db->prepare('UPDATE financial_years SET is_current = 0')->execute();
        $this->update($id, ['is_current' => 1]);
    }

    public function closeYear(int $id, int $closedBy): void
    {
        $year = $this->find($id);
        if (!$year) {
            throw new \RuntimeException('Financial year not found.');
        }
        if ($year['status'] === 'closed') {
            throw new \RuntimeException('Financial year is already closed.');
        }

        $this->update($id, [
            'status' => 'closed',
            'is_current' => 0,
            'closed_by' => $closedBy,
            'closed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function isDateInYear(array $year, string $date): bool
    {
        return $date >= $year['start_date'] && $date <= $year['end_date'];
    }

    public static function deriveTypeCode(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return 'GN';
        }

        $clean = preg_replace('/[^a-zA-Z\s]/', '', $label) ?? '';
        $words = array_values(array_filter(preg_split('/\s+/', $clean) ?: []));

        if (count($words) >= 2) {
            $code = '';
            foreach ($words as $word) {
                $code .= strtoupper($word[0]);
            }

            return substr($code, 0, 4);
        }

        $word = strtoupper($words[0]);
        if (strlen($word) <= 1) {
            return str_pad($word, 2, 'X');
        }

        $first = $word[0];
        $firstLower = strtolower($first);
        for ($i = 1, $len = strlen($word); $i < $len; $i++) {
            if (!preg_match('/[BCDFGHJKLMNPQRSTVWXYZ]/i', $word[$i])) {
                continue;
            }
            if (strtolower($word[$i]) === $firstLower) {
                continue;
            }

            return $first . strtoupper($word[$i]);
        }

        return substr($word, 0, 2);
    }

    public function peekNextNumbersForTypes(array $year, string $type, string $transactionDate, array $typeRows): array
    {
        $numbers = [];
        foreach ($typeRows as $row) {
            $name = $row['name'] ?? '';
            if ($name === '') {
                continue;
            }
            $numbers[(int) $row['id']] = $this->peekNextNumber($year, $type, $name, $transactionDate);
        }

        return $numbers;
    }

    private static function numberTypeConfig(string $type): array
    {
        $map = [
            'income' => ['col' => 'last_income_seq', 'prefix' => 'INC'],
            'expense' => ['col' => 'last_expense_seq', 'prefix' => 'EXP'],
            'asset' => ['col' => 'last_asset_seq', 'prefix' => 'AST'],
            'liability' => ['col' => 'last_liability_seq', 'prefix' => 'LIA'],
        ];

        if (!isset($map[$type])) {
            throw new \InvalidArgumentException('Invalid record type for numbering.');
        }

        return $map[$type];
    }

    public function peekNextNumber(array $year, string $type, string $typeLabel = '', string $transactionDate = ''): string
    {
        if (!in_array($type, ['income', 'expense'], true)) {
            $config = self::numberTypeConfig($type);
            $next = (int) ($year[$config['col']] ?? 0) + 1;

            return sprintf('%s_%d_%04d', $config['prefix'], $year['year_label'], $next);
        }

        $transactionDate = $transactionDate !== '' ? $transactionDate : date('Y-m-d');
        $typeLabel = $typeLabel !== '' ? $typeLabel : 'General';
        $config = self::numberTypeConfig($type);
        $typeCode = self::deriveTypeCode($typeLabel);
        [$yy, $mm] = $this->dateParts($transactionDate);
        $nextSeq = $this->resolveNextSequence((int) $year['id'], $type, $config['prefix'], $typeCode, $yy, $mm);

        return $this->formatTypedNumber($config['prefix'], $typeCode, $yy, $mm, $nextSeq);
    }

    public function generateTransactionNumber(
        int $financialYearId,
        string $type,
        string $typeLabel = '',
        string $transactionDate = ''
    ): string {
        $config = self::numberTypeConfig($type);
        $db = Database::getInstance();

        $db->beginTransaction();

        try {
            $seqCol = $config['col'];
            $stmt = $db->prepare(
                "SELECT year_label, status, {$seqCol} FROM financial_years WHERE id = ? FOR UPDATE"
            );
            $stmt->execute([$financialYearId]);
            $year = $stmt->fetch();

            if (!$year) {
                throw new \RuntimeException('Financial year not found.');
            }
            if ($year['status'] !== 'active') {
                throw new \RuntimeException('Cannot record transactions in a closed financial year.');
            }

            if (!in_array($type, ['income', 'expense'], true)) {
                $nextSeq = (int) $year[$seqCol] + 1;
                $number = sprintf('%s_%d_%04d', $config['prefix'], $year['year_label'], $nextSeq);

                $db->prepare("UPDATE financial_years SET {$seqCol} = ? WHERE id = ?")
                    ->execute([$nextSeq, $financialYearId]);

                $db->commit();

                return $number;
            }

            $transactionDate = $transactionDate !== '' ? $transactionDate : date('Y-m-d');
            $typeLabel = $typeLabel !== '' ? $typeLabel : 'General';
            $typeCode = self::deriveTypeCode($typeLabel);
            [$yy, $mm] = $this->dateParts($transactionDate);
            $nextSeq = $this->resolveNextSequence($financialYearId, $type, $config['prefix'], $typeCode, $yy, $mm);
            $number = $this->formatTypedNumber($config['prefix'], $typeCode, $yy, $mm, $nextSeq);

            if ($nextSeq > (int) ($year[$seqCol] ?? 0)) {
                $db->prepare("UPDATE financial_years SET {$seqCol} = ? WHERE id = ?")
                    ->execute([$nextSeq, $financialYearId]);
            }

            $db->commit();

            return $number;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function bumpSequenceIfHigher(int $financialYearId, string $type, string $documentNumber): void
    {
        $config = self::numberTypeConfig($type);

        if (preg_match('/^' . $config['prefix'] . '_([A-Z0-9]+)_(\d{2})_(\d{2})_(\d{4})$/', $documentNumber, $matches)) {
            $seq = (int) $matches[4];
            $year = $this->find($financialYearId);
            if ($year && $seq > (int) ($year[$config['col']] ?? 0)) {
                $this->update($financialYearId, [$config['col'] => $seq]);
            }

            return;
        }

        if (!preg_match('/^' . $config['prefix'] . '_(\d+)_(\d+)$/', $documentNumber, $matches)) {
            return;
        }

        $seq = (int) $matches[2];
        $year = $this->find($financialYearId);
        if ($year && $seq > (int) ($year[$config['col']] ?? 0)) {
            $this->update($financialYearId, [$config['col'] => $seq]);
        }
    }

    private function formatTypedNumber(string $prefix, string $typeCode, string $yy, string $mm, int $seq): string
    {
        return sprintf('%s_%s_%s_%s_%04d', $prefix, $typeCode, $yy, $mm, $seq);
    }

    private function dateParts(string $transactionDate): array
    {
        $timestamp = strtotime($transactionDate) ?: time();

        return [date('y', $timestamp), date('m', $timestamp)];
    }

    private function resolveNextSequence(
        int $financialYearId,
        string $type,
        string $prefix,
        string $typeCode,
        string $yy,
        string $mm
    ): int {
        $column = $type === 'income' ? 'receipt_number' : 'voucher_number';
        $pattern = sprintf('%s_%s_%s_%s_%%', $prefix, $typeCode, $yy, $mm);

        $row = $this->queryOne(
            "SELECT {$column} AS doc_no FROM financial_transactions
             WHERE transaction_type = ? AND financial_year_id = ? AND {$column} LIKE ?
             ORDER BY {$column} DESC LIMIT 1",
            [$type, $financialYearId, $pattern]
        );

        if (!$row || !preg_match('/_(\d{4})$/', (string) $row['doc_no'], $matches)) {
            return 1;
        }

        return (int) $matches[1] + 1;
    }
}
