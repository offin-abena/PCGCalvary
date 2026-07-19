<?php

namespace App\Services;

class SimpleSpreadsheetReader
{
    public function read(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'csv' => $this->readCsv($filePath),
            'xlsx' => $this->readXlsx($filePath),
            default => throw new \RuntimeException('Unsupported file type. Please upload a .csv or .xlsx file.'),
        };
    }

    private function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to read the uploaded file.');
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if ($this->isEmptyRow($data)) {
                continue;
            }
            $rows[] = array_map(fn($cell) => trim((string) $cell), $data);
        }
        fclose($handle);

        if (empty($rows)) {
            throw new \RuntimeException('The file is empty.');
        }

        return $rows;
    }

    private function readXlsx(string $filePath): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive is required to read Excel files.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Unable to open the Excel file.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $xml = simplexml_load_string($sharedXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new \RuntimeException('No worksheet found in the Excel file.');
        }

        $sheet = simplexml_load_string($sheetXml);
        if (!$sheet || !isset($sheet->sheetData->row)) {
            throw new \RuntimeException('The Excel worksheet is empty.');
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            $maxCol = 0;

            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $colIndex = $this->columnIndexFromReference($ref);
                $maxCol = max($maxCol, $colIndex);
                $cells[$colIndex] = $this->cellValue($cell, $sharedStrings);
            }

            if ($maxCol === 0 && empty($cells)) {
                continue;
            }

            $rowData = [];
            for ($i = 1; $i <= $maxCol; $i++) {
                $rowData[] = trim((string) ($cells[$i] ?? ''));
            }

            if (!$this->isEmptyRow($rowData)) {
                $rows[] = $rowData;
            }
        }

        if (empty($rows)) {
            throw new \RuntimeException('The Excel file is empty.');
        }

        return $rows;
    }

    private function cellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');
        $value = (string) ($cell->v ?? '');

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        if ($type === 'b') {
            return $value === '1' ? 'TRUE' : 'FALSE';
        }

        return $value;
    }

    private function columnIndexFromReference(string $reference): int
    {
        preg_match('/^[A-Z]+/', strtoupper($reference), $matches);
        $letters = $matches[0] ?? 'A';
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }
}
