<?php

namespace App\Models;

use App\Core\Model;

class Member extends Model
{
    protected string $table = 'members';

    public function search(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.middle_name LIKE ? OR m.email LIKE ? OR m.phone LIKE ? OR m.member_code LIKE ? OR m.membership_id LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params = array_merge($params, array_fill(0, 7, $term));
        }

        if (!empty($filters['status'])) {
            $where[] = "m.membership_status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['gender'])) {
            $where[] = "m.gender = ?";
            $params[] = $filters['gender'];
        }

        if (!empty($filters['family_id'])) {
            $where[] = "m.family_id = ?";
            $params[] = $filters['family_id'];
        }

        if (!empty($filters['age_group'])) {
            $ageCondition = (new \App\Services\AgeGroupService())->sqlCondition(
                $filters['age_group'],
                'm',
                'f'
            );
            if ($ageCondition) {
                $where[] = $ageCondition;
            }
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM members m LEFT JOIN families f ON m.family_id = f.id WHERE {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT m.*, f.family_name,
                    yc.name as youth_class_name, yc.ministry_type as youth_ministry_type
             FROM members m
             LEFT JOIN families f ON m.family_id = f.id
             LEFT JOIN youth_class_members ycm ON ycm.child_member_id = m.id AND ycm.is_active = 1
             LEFT JOIN youth_classes yc ON yc.id = ycm.class_id
             WHERE {$whereSql}
             ORDER BY m.last_name ASC, m.first_name ASC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    public function getWithFamily(int $id): ?array
    {
        $member = $this->queryOne(
            "SELECT m.*, f.family_name FROM members m LEFT JOIN families f ON m.family_id = f.id WHERE m.id = ?",
            [$id]
        );

        if (!$member) {
            return null;
        }

        $member['youth_ministry'] = $this->getYouthMinistry($id);
        return $member;
    }

    public function getGenerationalFamilies(): array
    {
        return $this->query(
            "SELECT id, family_name, min_age, max_age, gender_scope
             FROM families
             ORDER BY sort_order, family_name"
        );
    }

    public function getYouthMinistry(int $id): ?array
    {
        return $this->queryOne(
            "SELECT yc.id as class_id, yc.name, yc.ministry_type, ycm.enrolled_date
             FROM youth_class_members ycm
             JOIN youth_classes yc ON yc.id = ycm.class_id
             WHERE ycm.child_member_id = ? AND ycm.is_active = 1
             LIMIT 1",
            [$id]
        );
    }

    public function isInYouthMinistry(int $id): bool
    {
        return $this->getYouthMinistry($id) !== null;
    }

    public function getStats(): array
    {
        return [
            'total' => (int) $this->db->query("SELECT COUNT(*) FROM members")->fetchColumn(),
            'active' => (int) $this->db->query("SELECT COUNT(*) FROM members WHERE membership_status = 'active'")->fetchColumn(),
            'visitors' => (int) $this->db->query("SELECT COUNT(*) FROM members WHERE membership_status = 'visitor'")->fetchColumn(),
            'new_converts' => (int) $this->db->query("SELECT COUNT(*) FROM members WHERE is_new_convert = 1")->fetchColumn(),
            'new_this_month' => (int) $this->db->query("SELECT COUNT(*) FROM members WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn(),
        ];
    }

    public function getBirthdays(int $days = 7): array
    {
        return $this->query(
            "SELECT * FROM members WHERE membership_status = 'active' 
             AND DATE_FORMAT(date_of_birth, '%m-%d') BETWEEN DATE_FORMAT(NOW(), '%m-%d') 
             AND DATE_FORMAT(DATE_ADD(NOW(), INTERVAL ? DAY), '%m-%d')
             ORDER BY DATE_FORMAT(date_of_birth, '%m-%d')",
            [$days]
        );
    }

    public function findByQrCode(string $qrCode): ?array
    {
        return $this->queryOne("SELECT * FROM members WHERE qr_code = ?", [$qrCode]);
    }

    public function getActiveForAgeGroup(string $ageGroupKey): array
    {
        if ($ageGroupKey === 'adults') {
            return $this->query(
                "SELECT m.id, m.first_name, m.last_name, m.member_code
                 FROM members m
                 WHERE m.membership_status = 'active'
                 ORDER BY m.last_name, m.first_name"
            );
        }

        $condition = (new \App\Services\AgeGroupService())->sqlCondition($ageGroupKey, 'm', 'f');
        if (!$condition) {
            return [];
        }

        return $this->query(
            "SELECT m.id, m.first_name, m.last_name, m.member_code
             FROM members m
             LEFT JOIN families f ON m.family_id = f.id
             WHERE m.membership_status = 'active' AND {$condition}
             ORDER BY m.last_name, m.first_name"
        );
    }

    public function getActiveExcludingAgeGroup(string $ageGroupKey): array
    {
        $exclude = (new \App\Services\AgeGroupService())->sqlExcludeGroupCondition($ageGroupKey, 'm', 'f');
        if (!$exclude) {
            return $this->query(
                "SELECT m.id, m.first_name, m.last_name, m.phone, m.membership_id
                 FROM members m
                 WHERE m.membership_status = 'active'
                 ORDER BY m.last_name, m.first_name"
            );
        }

        return $this->query(
            "SELECT m.id, m.first_name, m.last_name, m.phone, m.membership_id
             FROM members m
             LEFT JOIN families f ON m.family_id = f.id
             WHERE m.membership_status = 'active' AND {$exclude}
             ORDER BY m.last_name, m.first_name"
        );
    }

    public function getTithePendingMembers(): array
    {
        $exclude = (new \App\Services\AgeGroupService())->titheEligibleMemberCondition('m', 'f');

        return $this->query(
            "SELECT m.id, m.first_name, m.last_name, m.phone
             FROM members m
             LEFT JOIN families f ON m.family_id = f.id
             WHERE m.membership_status = 'active'
             AND m.phone IS NOT NULL AND m.phone != ''
             AND {$exclude}
             AND m.id NOT IN (
                 SELECT DISTINCT ft.member_id FROM financial_transactions ft
                 JOIN income_types it ON ft.type_id = it.id
                 WHERE ft.transaction_type = 'income' AND it.name = 'Tithe'
                 AND ft.member_id IS NOT NULL
                 AND MONTH(ft.transaction_date) = MONTH(CURDATE())
                 AND YEAR(ft.transaction_date) = YEAR(CURDATE())
             )
             ORDER BY m.last_name, m.first_name"
        );
    }

    public function resolveGenerationalFamilyId(array $data, ?array $existing = null): ?int
    {
        $ageService = new \App\Services\AgeGroupService();
        $dob = $data['date_of_birth'] ?? null;
        $submittedFamilyId = !empty($data['family_id']) ? (int) $data['family_id'] : null;

        $submittedFamily = $submittedFamilyId
            ? $this->queryOne('SELECT id, family_name FROM families WHERE id = ?', [$submittedFamilyId])
            : null;

        // Manual change to Junior Youth (e.g. from Children Service)
        if ($submittedFamily && $ageService->matchesFamilyGroup($submittedFamily['family_name'], 'junior_youth')) {
            return (int) $submittedFamily['id'];
        }

        // Date of birth qualifies for Junior Youth — move from Children Service automatically
        $ageGroup = $ageService->resolveGroupKeyFromAge($dob);
        if ($ageGroup === 'junior_youth') {
            return $this->getFamilyIdByGroupKey('junior_youth') ?? $submittedFamilyId;
        }

        if ($ageGroup === 'children_service') {
            return $this->getFamilyIdByGroupKey('children_service') ?? $submittedFamilyId;
        }

        // No DOB: keep Children Service assignment
        if ($submittedFamily && $ageService->matchesFamilyGroup($submittedFamily['family_name'], 'children_service')) {
            return (int) $submittedFamily['id'];
        }

        return $submittedFamilyId;
    }

    public function getFamilyIdByGroupKey(string $groupKey): ?int
    {
        $ageService = new \App\Services\AgeGroupService();
        foreach ($ageService->getFamilyNames($groupKey) as $name) {
            $family = $this->queryOne(
                'SELECT id FROM families WHERE UPPER(TRIM(family_name)) = UPPER(?) LIMIT 1',
                [trim($name)]
            );
            if ($family) {
                return (int) $family['id'];
            }
        }

        return null;
    }

    public function getEligibleForYouthClass(int $classId, string $ministryType): array
    {
        $ageService = new \App\Services\AgeGroupService();
        $ageCondition = $this->youthClassAgeCondition($ministryType, $ageService);
        if ($ageCondition === null) {
            return [];
        }

        return $this->query(
            "SELECT m.id, m.first_name, m.last_name, m.middle_name, m.member_code, m.membership_id,
                    m.gender, m.date_of_birth,
                    (
                        SELECT yc.name
                        FROM youth_class_members ycm2
                        JOIN youth_classes yc ON yc.id = ycm2.class_id
                        WHERE ycm2.child_member_id = m.id
                          AND ycm2.is_active = 1
                          AND ycm2.class_id != ?
                          AND yc.ministry_type = ?
                        LIMIT 1
                    ) AS enrolled_elsewhere
             FROM members m
             LEFT JOIN families f ON m.family_id = f.id
             LEFT JOIN youth_class_members ycm_here
                 ON ycm_here.child_member_id = m.id AND ycm_here.class_id = ? AND ycm_here.is_active = 1
             WHERE m.membership_status = 'active'
               AND ycm_here.id IS NULL
               AND ({$ageCondition})
             ORDER BY m.last_name, m.first_name, m.middle_name",
            [$classId, $ministryType, $classId]
        );
    }

    private function youthClassAgeCondition(string $ministryType, \App\Services\AgeGroupService $ageService): ?string
    {
        return match ($ministryType) {
            'children' => $ageService->sqlCondition('children_service', 'm', 'f'),
            'junior_youth' => $ageService->sqlCondition('junior_youth', 'm', 'f'),
            'youth' => $ageService->sqlExcludeGroupCondition('children_service', 'm', 'f'),
            default => null,
        };
    }
}
