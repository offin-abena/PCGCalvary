<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Model;
use PDO;

class GenerationalGroupService
{
    private PDO $db;
    private array $rules;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->rules = require config_path('generational_groups.php');
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function ageAsOf(?string $dateOfBirth, string $asOfDate): ?int
    {
        if (!$dateOfBirth || $dateOfBirth === '0000-00-00') {
            return null;
        }

        try {
            $birth = new \DateTime($dateOfBirth);
            $ref = new \DateTime($asOfDate);
            return (int) $birth->diff($ref)->y;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function yearEndDate(int $year): string
    {
        return $year . '-12-31';
    }

    public function reachedThresholdInYear(?string $dateOfBirth, int $thresholdAge, int $graduationYear): bool
    {
        if (!$dateOfBirth) {
            return false;
        }

        try {
            $birth = new \DateTime($dateOfBirth);
            $birthdayAtThreshold = (clone $birth)->modify('+' . $thresholdAge . ' years');
            return (int) $birthdayAtThreshold->format('Y') === $graduationYear;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getFamilyByName(string $name): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM families WHERE UPPER(family_name) = UPPER(?) LIMIT 1');
        $stmt->execute([trim($name)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function resolveNextGroup(string $currentGroupName, string $gender): ?array
    {
        $rule = $this->rules[$currentGroupName] ?? null;
        if (!$rule) {
            return null;
        }

        $nextName = null;
        if (!empty($rule['next_by_gender'])) {
            $nextName = $rule['next_by_gender'][$gender] ?? $rule['next_by_gender']['other'] ?? null;
        } elseif (!empty($rule['next'])) {
            $nextName = $rule['next'];
        }

        return $nextName ? $this->getFamilyByName($nextName) : null;
    }

    public function isDueForGraduation(array $member, array $family, int $graduationYear, bool $onlyThisYear = false): bool
    {
        if ($family['max_age'] === null) {
            return false;
        }

        $asOf = $this->yearEndDate($graduationYear);
        $age = $this->ageAsOf($member['date_of_birth'] ?? null, $asOf);
        if ($age === null) {
            return false;
        }

        $threshold = (int) $family['max_age'] + 1;
        if ($age < $threshold) {
            return false;
        }

        if ($onlyThisYear) {
            return $this->reachedThresholdInYear($member['date_of_birth'], $threshold, $graduationYear);
        }

        return true;
    }

    public function getGraduationCandidates(int $graduationYear, bool $onlyThisYear = false): array
    {
        $asOf = $this->yearEndDate($graduationYear);
        $db = new class extends Model { protected string $table = 'members'; };

        $members = $db->query(
            "SELECT m.id, m.membership_id, m.member_code, m.first_name, m.last_name, m.middle_name,
                    m.gender, m.date_of_birth, m.family_id, f.family_name, f.min_age, f.max_age, f.gender_scope
             FROM members m
             JOIN families f ON m.family_id = f.id
             WHERE f.max_age IS NOT NULL
             ORDER BY f.sort_order, m.last_name, m.first_name"
        );

        $due = [];
        $missingDob = [];

        foreach ($members as $member) {
            if (!$member['date_of_birth']) {
                $missingDob[] = $member;
                continue;
            }

            if (!$this->isDueForGraduation($member, $member, $graduationYear, $onlyThisYear)) {
                continue;
            }

            $age = $this->ageAsOf($member['date_of_birth'], $asOf);
            $next = $this->resolveNextGroup($member['family_name'], $member['gender'] ?? 'other');

            $due[] = array_merge($member, [
                'age_as_of' => $age,
                'next_family_id' => $next['id'] ?? null,
                'next_family_name' => $next['family_name'] ?? null,
                'threshold_age' => (int) $member['max_age'] + 1,
            ]);
        }

        return [
            'due' => $due,
            'missing_dob' => $missingDob,
            'graduation_year' => $graduationYear,
            'only_this_year' => $onlyThisYear,
            'as_of' => $asOf,
        ];
    }

    public function getGraduatedMemberIdsForYear(int $year): array
    {
        $stmt = $this->db->prepare('SELECT member_id FROM generational_graduations WHERE graduation_year = ?');
        $stmt->execute([$year]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function graduateMembers(array $memberIds, int $graduationYear, ?int $userId = null): array
    {
        $results = ['graduated' => 0, 'skipped' => 0, 'errors' => []];
        $asOf = $this->yearEndDate($graduationYear);
        $db = new class extends Model { protected string $table = 'members'; };

        foreach ($memberIds as $memberId) {
            $memberId = (int) $memberId;
            $steps = 0;

            while ($steps < 3) {
                $member = $db->queryOne(
                    "SELECT m.*, f.family_name, f.max_age
                     FROM members m
                     JOIN families f ON m.family_id = f.id
                     WHERE m.id = ?",
                    [$memberId]
                );

                if (!$member || $member['max_age'] === null) {
                    if ($steps === 0) {
                        $results['skipped']++;
                    }
                    break;
                }

                $next = $this->resolveNextGroup($member['family_name'], $member['gender'] ?? 'other');
                if (!$next) {
                    if ($steps === 0) {
                        $results['errors'][] = "No next group for {$member['first_name']} {$member['last_name']}.";
                        $results['skipped']++;
                    }
                    break;
                }

                if (!$this->isDueForGraduation($member, $member, $graduationYear, false)) {
                    if ($steps === 0) {
                        $results['skipped']++;
                    }
                    break;
                }

                $age = $this->ageAsOf($member['date_of_birth'], $asOf);
                $fromFamilyId = (int) $member['family_id'];

                try {
                    $this->db->beginTransaction();

                    $db->update($memberId, ['family_id' => (int) $next['id']]);

                    $stepStmt = $this->db->prepare(
                        'SELECT COALESCE(MAX(graduation_step), 0) + 1 FROM generational_graduations WHERE member_id = ? AND graduation_year = ?'
                    );
                    $stepStmt->execute([$memberId, $graduationYear]);
                    $graduationStep = (int) $stepStmt->fetchColumn();

                    $stmt = $this->db->prepare(
                        'INSERT INTO generational_graduations
                         (member_id, from_family_id, to_family_id, graduation_year, graduation_step, member_age, graduated_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([
                        $memberId,
                        $fromFamilyId,
                        (int) $next['id'],
                        $graduationYear,
                        $graduationStep,
                        $age,
                        $userId,
                    ]);

                    $this->db->commit();
                    $results['graduated']++;
                    $steps++;
                } catch (\Exception $e) {
                    $this->db->rollBack();
                    $results['errors'][] = $e->getMessage();
                    if ($steps === 0) {
                        $results['skipped']++;
                    }
                    break;
                }
            }
        }

        return $results;
    }

    public function getRecentGraduations(int $limit = 20): array
    {
        $db = new class extends Model { protected string $table = 'generational_graduations'; };
        return $db->query(
            "SELECT gg.*,
                    CONCAT(m.first_name, ' ', m.last_name) AS member_name,
                    ff.family_name AS from_group, tf.family_name AS to_group
             FROM generational_graduations gg
             JOIN members m ON m.id = gg.member_id
             JOIN families ff ON ff.id = gg.from_family_id
             JOIN families tf ON tf.id = gg.to_family_id
             ORDER BY gg.graduated_at DESC
             LIMIT ?",
            [$limit]
        );
    }
}
