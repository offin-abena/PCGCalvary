<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Finance;
use App\Models\FinancialYear;

class FinanceImportService
{
    private Finance $finance;
    private FinancialYear $financialYear;
    private SimpleSpreadsheetReader $reader;

    private const INCOME_COLUMNS = ['date', 'receipt no', 'category', 'description', 'amount', 'payment'];
    private const EXPENSE_COLUMNS = ['date', 'voucher no', 'category', 'description', 'amount', 'payment'];

    public function __construct()
    {
        $this->finance = new Finance();
        $this->financialYear = new FinancialYear();
        $this->reader = new SimpleSpreadsheetReader();
    }

    public function getTemplateHeaders(string $type): array
    {
        return $type === 'income' ? self::INCOME_COLUMNS : self::EXPENSE_COLUMNS;
    }

    public function getTemplateSampleRow(string $type): array
    {
        if ($type === 'income') {
            return ['2026-01-15', 'INC_TH_26_01_0001', 'Tithe', 'Sunday offering', '500.00', 'Cash'];
        }

        return ['2026-01-15', 'EXP_2026_0001', 'Utilities', 'Electricity bill', '250.00', 'Mobile Money'];
    }

    public function import(string $filePath, string $type): array
    {
        if (!in_array($type, ['income', 'expense'], true)) {
            throw new \InvalidArgumentException('Invalid import type.');
        }

        $currentFy = $this->financialYear->getCurrent();
        if (!$currentFy) {
            throw new \RuntimeException('No active financial year is set.');
        }

        $rawRows = $this->reader->read($filePath);
        $headers = array_map(fn($h) => $this->normalizeHeader($h), $rawRows[0]);
        $expected = $type === 'income' ? self::INCOME_COLUMNS : self::EXPENSE_COLUMNS;

        $this->validateHeaders($headers, $expected);

        $typeMap = $this->loadTypeMap($type);
        $documentField = $type === 'income' ? 'receipt_number' : 'voucher_number';
        $allowedPayments = $type === 'income'
            ? ['cash', 'check', 'mobile_money', 'bank_transfer', 'online', 'other']
            : ['cash', 'check', 'mobile_money'];

        $imported = 0;
        $errors = [];
        $seenDocuments = [];
        $db = Database::getInstance();

        for ($i = 1; $i < count($rawRows); $i++) {
            $rowNumber = $i + 1;
            $row = $this->mapRow($headers, $rawRows[$i]);

            try {
                $date = $this->parseDate($row['date'] ?? '');
                if (!$date) {
                    throw new \RuntimeException('Invalid or missing date.');
                }

                if (!$this->financialYear->isDateInYear($currentFy, $date)) {
                    throw new \RuntimeException('Date is outside the current financial year.');
                }

                $category = trim($row['category'] ?? '');
                if ($category === '') {
                    throw new \RuntimeException('Category is required.');
                }

                $typeId = $typeMap[strtolower($category)] ?? null;
                if (!$typeId) {
                    throw new \RuntimeException("Category '{$category}' was not found.");
                }

                $amount = $this->parseAmount($row['amount'] ?? '');
                if ($amount <= 0) {
                    throw new \RuntimeException('Amount must be greater than zero.');
                }

                $payment = $this->parsePaymentMethod($row['payment'] ?? '');
                if (!$payment || !in_array($payment, $allowedPayments, true)) {
                    throw new \RuntimeException('Invalid payment type.');
                }

                $documentNo = trim($row[$type === 'income' ? 'receipt no' : 'voucher no'] ?? '');
                if ($documentNo === '') {
                    $documentNo = $this->financialYear->generateTransactionNumber(
                        (int) $currentFy['id'],
                        $type,
                        $category,
                        $date
                    );
                } else {
                    if (isset($seenDocuments[$documentNo])) {
                        throw new \RuntimeException("Duplicate {$documentField} in file.");
                    }

                    $exists = $this->finance->queryOne(
                        "SELECT id FROM financial_transactions WHERE {$documentField} = ?",
                        [$documentNo]
                    );
                    if ($exists) {
                        throw new \RuntimeException("{$documentField} '{$documentNo}' already exists.");
                    }

                    $this->financialYear->bumpSequenceIfHigher((int) $currentFy['id'], $type, $documentNo);
                }

                $seenDocuments[$documentNo] = true;

                $data = [
                    'transaction_type' => $type,
                    'type_id' => $typeId,
                    'member_id' => null,
                    'amount' => $amount,
                    'transaction_date' => $date,
                    'financial_year_id' => $currentFy['id'],
                    'payment_method' => $payment,
                    'description' => trim($row['description'] ?? ''),
                    'recorded_by' => Auth::id(),
                    $documentField => $documentNo,
                ];

                $this->finance->create($data);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'failed' => count($errors),
            'errors' => $errors,
            'total_rows' => count($rawRows) - 1,
        ];
    }

    private function validateHeaders(array $headers, array $expected): void
    {
        $normalized = array_map(fn($h) => $this->normalizeHeader($h), $headers);
        $missing = array_diff($expected, $normalized);

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required columns: ' . implode(', ', array_map('ucwords', $missing))
            );
        }
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace(['_', '-'], ' ', $header);
        $header = preg_replace('/\s+/', ' ', $header);

        $aliases = [
            'receipt number' => 'receipt no',
            'voucher number' => 'voucher no',
            'payment type' => 'payment',
            'payment method' => 'payment',
        ];

        return $aliases[$header] ?? $header;
    }

    private function mapRow(array $headers, array $values): array
    {
        $mapped = [];
        foreach ($headers as $index => $header) {
            $mapped[$header] = trim((string) ($values[$index] ?? ''));
        }
        return $mapped;
    }

    private function loadTypeMap(string $type): array
    {
        $table = $type === 'income' ? 'income_types' : 'expense_types';
        $rows = $this->finance->query("SELECT id, name FROM {$table} WHERE is_active = 1");
        $map = [];

        foreach ($rows as $row) {
            $map[strtolower($row['name'])] = (int) $row['id'];
        }

        return $map;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;
            if ($serial > 20000 && $serial < 60000) {
                $unix = (int) round(($serial - 25569) * 86400);
                return gmdate('Y-m-d', $unix);
            }
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd M Y', 'M d, Y'];
        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt && $dt->format($format) === $value) {
                return $dt->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function parseAmount(string $value): float
    {
        $value = str_replace([',', 'GH₵', 'GHS', ' '], '', trim($value));
        return (float) $value;
    }

    private function parsePaymentMethod(string $value): ?string
    {
        $value = strtolower(trim($value));
        $map = [
            'cash' => 'cash',
            'cheque' => 'check',
            'check' => 'check',
            'mobile money' => 'mobile_money',
            'mobile_money' => 'mobile_money',
            'momo' => 'mobile_money',
            'bank transfer' => 'bank_transfer',
            'bank_transfer' => 'bank_transfer',
            'online' => 'online',
            'other' => 'other',
        ];

        return $map[$value] ?? null;
    }
}
