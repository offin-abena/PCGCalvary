<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Member;

class ChildrenServiceImportService
{
    public function importFromRecords(array $records): array
    {
        $familyId = $this->getChildrenServiceFamilyId();
        if (!$familyId) {
            throw new \RuntimeException('Children Service generational group not found.');
        }

        $memberModel = new Member();
        $existing = $this->loadExistingMembers();
        $results = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'total' => count($records),
            'details' => [],
        ];

        foreach ($records as $record) {
            $fullName = trim((string) ($record['full_name'] ?? ''));
            if ($fullName === '') {
                $results['skipped']++;
                continue;
            }

            $parsed = $this->parseName($fullName);
            $gender = ($record['gender'] ?? 'male') === 'female' ? 'female' : 'male';
            $match = $this->findMatchingMember($existing, $parsed, $gender);

            $note = $this->buildNotes($record);
            $data = [
                'first_name' => $parsed['first_name'],
                'last_name' => $parsed['last_name'],
                'middle_name' => $parsed['middle_name'],
                'gender' => $gender,
                'family_id' => $familyId,
                'membership_status' => 'active',
                'notes' => $note,
            ];

            if (!empty($record['birthday'])) {
                $data['date_of_birth'] = $record['birthday'];
            }

            if (!empty($record['contact'])) {
                $data['phone'] = preg_replace('/[^0-9+]/', '', $record['contact']) ?: $record['contact'];
            }

            if ($match) {
                $update = $data;
                if (empty($record['birthday'])) {
                    unset($update['date_of_birth']);
                }
                if (empty($record['contact'])) {
                    unset($update['phone']);
                }

                $memberModel->update((int) $match['id'], $update);
                $existing[(int) $match['id']] = array_merge($match, $update);
                $results['updated']++;
                $results['details'][] = ['action' => 'updated', 'name' => $fullName, 'member_id' => (int) $match['id']];
            } else {
                $data['member_code'] = generate_member_code();
                $data['qr_code'] = generate_qr_code();
                $id = $memberModel->create($data);
                $existing[$id] = array_merge($data, ['id' => $id]);
                $results['created']++;
                $results['details'][] = ['action' => 'created', 'name' => $fullName, 'member_id' => $id];
            }
        }

        return $results;
    }

    public function parseRecordsFromXlsx(string $filePath): array
    {
        $python = $this->resolvePythonBinary();
        $parser = escapeshellarg(dirname(__DIR__, 2) . '/scripts/parse_children_xlsx.py');
        $file = escapeshellarg($filePath);
        $command = "{$python} {$parser} {$file} 2>&1";
        $output = shell_exec($command);

        if (!$output) {
            throw new \RuntimeException('Unable to parse Excel file.');
        }

        $payload = json_decode($output, true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Invalid parser output: ' . substr($output, 0, 200));
        }

        if (!empty($payload['error'])) {
            throw new \RuntimeException($payload['error']);
        }

        return $payload['records'] ?? [];
    }

    private function resolvePythonBinary(): string
    {
        foreach (['python', 'python3', 'py -3'] as $candidate) {
            $check = trim((string) shell_exec($candidate . ' --version 2>&1'));
            if ($check !== '') {
                return $candidate;
            }
        }

        throw new \RuntimeException('Python is required to read Excel files.');
    }

    private function getChildrenServiceFamilyId(): ?int
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT id FROM families WHERE UPPER(TRIM(family_name)) IN ('CHILDREN SERVICE', 'CHILDREN SERVICE MINISTRY') ORDER BY id LIMIT 1"
        );
        $id = $stmt->fetchColumn();

        return $id ? (int) $id : null;
    }

    private function loadExistingMembers(): array
    {
        $db = Database::getInstance();
        $rows = $db->query(
            "SELECT m.*, f.family_name
             FROM members m
             LEFT JOIN families f ON m.family_id = f.id"
        )->fetchAll();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['id']] = $row;
        }

        return $indexed;
    }

    private function parseName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        if (count($parts) === 1) {
            return [
                'first_name' => $parts[0],
                'middle_name' => null,
                'last_name' => $parts[0],
                'normalized' => $this->normalizeName($fullName),
            ];
        }

        return [
            'first_name' => $parts[0],
            'middle_name' => count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : null,
            'last_name' => $parts[count($parts) - 1],
            'normalized' => $this->normalizeName($fullName),
        ];
    }

    private function findMatchingMember(array $existing, array $parsed, string $gender): ?array
    {
        $best = null;
        $bestScore = 0;

        foreach ($existing as $member) {
            if (($member['gender'] ?? '') !== $gender) {
                continue;
            }

            $score = $this->matchScore($member, $parsed);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $member;
            }
        }

        return $bestScore >= 70 ? $best : null;
    }

    private function matchScore(array $member, array $parsed): int
    {
        $dbFull = $this->normalizeName(trim(
            ($member['first_name'] ?? '') . ' ' .
            ($member['middle_name'] ? $member['middle_name'] . ' ' : '') .
            ($member['last_name'] ?? '')
        ));

        if ($dbFull === $parsed['normalized']) {
            return 100;
        }

        $lastMatch = strcasecmp($member['last_name'] ?? '', $parsed['last_name']) === 0;
        $firstMatch = strcasecmp($member['first_name'] ?? '', $parsed['first_name']) === 0;

        if ($lastMatch && $firstMatch) {
            return 95;
        }

        if ($lastMatch && str_starts_with(strtolower($parsed['first_name']), strtolower($member['first_name'] ?? ''))) {
            return 85;
        }

        if ($lastMatch && str_contains($parsed['normalized'], strtolower($member['first_name'] ?? ''))) {
            return 80;
        }

        similar_text($dbFull, $parsed['normalized'], $percent);

        return (int) $percent;
    }

    private function normalizeName(string $name): string
    {
        return preg_replace('/\s+/', ' ', strtolower(trim($name)));
    }

    private function buildNotes(array $record): string
    {
        $classes = [$record['class_label'] ?? null];
        if (!empty($record['class_labels']) && is_array($record['class_labels'])) {
            $classes = array_merge($classes, $record['class_labels']);
        }

        $classes = array_values(array_unique(array_filter($classes)));
        $classText = implode('; ', $classes);
        $label = trim((string) ($record['register_label'] ?? ''));
        if ($label === '') {
            $gender = ($record['gender'] ?? 'male') === 'female' ? 'Girls' : 'Boys';
            $label = "CHS 2nd Q 2025 {$gender} Register";
        }

        return trim($label . ' | Class: ' . $classText . ' | Sheet: ' . ($record['sheet'] ?? ''));
    }
}
