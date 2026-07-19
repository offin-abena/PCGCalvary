<?php

namespace App\Services;

class AgeGroupService
{
    private array $groups;

    public function __construct()
    {
        $this->groups = require config_path('age_groups.php');
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getGroup(string $key): ?array
    {
        return $this->groups[$key] ?? null;
    }

    public function getLabel(string $key): string
    {
        return $this->groups[$key]['label'] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public function getFamilyNames(string $groupKey): array
    {
        $group = $this->getGroup($groupKey);
        if (!$group) {
            return [];
        }

        return $group['family_names'] ?? [$group['family_name'] ?? ''];
    }

    public function matchesFamilyGroup(?string $familyName, string $groupKey): bool
    {
        if (!$familyName) {
            return false;
        }

        $normalized = strtoupper(trim($familyName));
        foreach ($this->getFamilyNames($groupKey) as $name) {
            if ($normalized === strtoupper(trim($name))) {
                return true;
            }
        }

        return false;
    }

    public function ageFromDob(?string $dateOfBirth, ?string $asOfDate = null): ?int
    {
        if (!$dateOfBirth || $dateOfBirth === '0000-00-00') {
            return null;
        }

        try {
            $birth = new \DateTime($dateOfBirth);
            $ref = new \DateTime($asOfDate ?? date('Y-m-d'));
            return (int) $birth->diff($ref)->y;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function resolveGroupKeyFromAge(?string $dateOfBirth, ?string $asOfDate = null): ?string
    {
        $age = $this->ageFromDob($dateOfBirth, $asOfDate);
        if ($age === null) {
            return null;
        }

        foreach ($this->groups as $key => $group) {
            $min = (int) $group['min_age'];
            $max = $group['max_age'];

            if ($age < $min) {
                continue;
            }

            if ($max === null || $age <= $max) {
                return $key;
            }
        }

        return null;
    }

    public function resolveGroupKey(?string $dateOfBirth, ?string $asOfDate = null, ?string $familyName = null): ?string
    {
        if ($familyName && $this->matchesFamilyGroup($familyName, 'children_service')) {
            return 'children_service';
        }

        if ($familyName && $this->matchesFamilyGroup($familyName, 'junior_youth')) {
            return 'junior_youth';
        }

        return $this->resolveGroupKeyFromAge($dateOfBirth, $asOfDate);
    }

    public function sqlAgeExpression(string $memberAlias = 'm'): string
    {
        return "TIMESTAMPDIFF(YEAR, {$memberAlias}.date_of_birth, CURDATE())";
    }

    public function sqlFamilyMatch(string $groupKey, string $memberAlias = 'm', ?string $familyAlias = null): ?string
    {
        $names = $this->getFamilyNames($groupKey);
        if (empty($names)) {
            return null;
        }

        $escaped = array_map(
            fn ($name) => "'" . str_replace("'", "''", strtoupper(trim($name))) . "'",
            $names
        );
        $list = implode(', ', $escaped);

        if ($familyAlias) {
            return "UPPER(TRIM({$familyAlias}.family_name)) IN ({$list})";
        }

        return "{$memberAlias}.family_id IN (
            SELECT id FROM families WHERE UPPER(TRIM(family_name)) IN ({$list})
        )";
    }

    public function sqlCondition(string $groupKey, string $memberAlias = 'm', ?string $familyAlias = null): ?string
    {
        $group = $this->getGroup($groupKey);
        if (!$group) {
            return null;
        }

        if ($groupKey === 'children_service') {
            $familyMatch = $this->sqlFamilyMatch($groupKey, $memberAlias, $familyAlias);
            $ageExpr = $this->sqlAgeExpression($memberAlias);
            $ageMatch = "({$memberAlias}.date_of_birth IS NOT NULL
                AND {$memberAlias}.date_of_birth != '0000-00-00'
                AND {$ageExpr} <= " . (int) $group['max_age'] . ')';

            if ($familyMatch) {
                return "({$ageMatch} OR {$familyMatch})";
            }

            return $ageMatch;
        }

        if ($groupKey === 'junior_youth') {
            $familyMatch = $this->sqlFamilyMatch($groupKey, $memberAlias, $familyAlias);
            $ageExpr = $this->sqlAgeExpression($memberAlias);
            $ageMatch = "({$memberAlias}.date_of_birth IS NOT NULL
                AND {$memberAlias}.date_of_birth != '0000-00-00'
                AND {$ageExpr} BETWEEN " . (int) $group['min_age'] . ' AND ' . (int) $group['max_age'] . ')';

            if ($familyMatch) {
                return "({$ageMatch} OR {$familyMatch})";
            }

            return $ageMatch;
        }

        $ageExpr = $this->sqlAgeExpression($memberAlias);
        $conditions = [
            "{$memberAlias}.date_of_birth IS NOT NULL",
            "{$memberAlias}.date_of_birth != '0000-00-00'",
        ];

        $min = (int) $group['min_age'];
        $max = $group['max_age'];

        if ($max === null) {
            $conditions[] = "{$ageExpr} >= {$min}";
        } else {
            $conditions[] = "{$ageExpr} BETWEEN {$min} AND {$max}";
        }

        return '(' . implode(' AND ', $conditions) . ')';
    }

    public function sqlExcludeGroupCondition(string $groupKey, string $memberAlias = 'm', ?string $familyAlias = null): ?string
    {
        $include = $this->sqlCondition($groupKey, $memberAlias, $familyAlias);
        if (!$include) {
            return null;
        }

        return "NOT ({$include})";
    }

    public function titheEligibleMemberCondition(string $memberAlias = 'm', ?string $familyAlias = 'f'): string
    {
        return $this->sqlExcludeGroupCondition('children_service', $memberAlias, $familyAlias) ?? '1=1';
    }

    public function memberBelongsToGroup(array $member, string $groupKey): bool
    {
        return $this->resolveGroupKey(
            $member['date_of_birth'] ?? null,
            null,
            $member['family_name'] ?? null
        ) === $groupKey;
    }

    public function countByGroup(): array
    {
        $counts = [];
        $db = \App\Core\Database::getInstance();

        foreach ($this->groups as $key => $group) {
            $condition = $this->sqlCondition($key);
            if (!$condition) {
                $counts[$key] = 0;
                continue;
            }

            $counts[$key] = (int) $db->query(
                "SELECT COUNT(*) FROM members m WHERE {$condition}"
            )->fetchColumn();
        }

        return $counts;
    }

    public function getListUrl(?string $groupKey): string
    {
        return match ($groupKey) {
            'children_service' => 'members/children-service',
            'junior_youth' => 'members/junior-youth',
            default => 'members/adults',
        };
    }

    public function getAttendanceListUrl(?string $groupKey): string
    {
        return match ($groupKey) {
            'children_service' => 'attendance/children-service',
            'junior_youth' => 'attendance/junior-youth',
            default => 'attendance/adults',
        };
    }

    public function isValidGroupKey(?string $groupKey): bool
    {
        return in_array($groupKey, ['adults', 'children_service', 'junior_youth'], true);
    }

    public function normalizeGroupKey(?string $groupKey): string
    {
        return $this->isValidGroupKey($groupKey) ? $groupKey : 'adults';
    }

    public function usesGeneralAttendance(string $groupKey): bool
    {
        return in_array($groupKey, ['adults', 'junior_youth'], true);
    }

    public function getListUrlForMember(?string $dateOfBirth, ?string $familyName = null): string
    {
        return $this->getListUrl($this->resolveGroupKey($dateOfBirth, null, $familyName));
    }
}
