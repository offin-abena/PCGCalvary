<?php

namespace App\Models;

use App\Core\Model;
use App\Services\AgeGroupService;

class Attendance extends Model
{
    protected string $table = 'attendance_sessions';

    public function getSessionDetails(int $id): ?array
    {
        return $this->queryOne(
            "SELECT s.*, st.name AS service_name FROM attendance_sessions s
             JOIN service_types st ON s.service_type_id = st.id WHERE s.id = ?",
            [$id]
        );
    }

    public function getSessionAttendees(int $sessionId): array
    {
        return $this->query(
            "SELECT ar.*, m.first_name, m.last_name, m.member_code, m.photo, m.gender
             FROM attendance_records ar
             JOIN members m ON ar.member_id = m.id
             WHERE ar.session_id = ? ORDER BY ar.check_in_time",
            [$sessionId]
        );
    }

    public function checkIn(int $sessionId, int $memberId, string $method = 'manual'): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO attendance_records (session_id, member_id, check_in_method) VALUES (?, ?, ?)"
            );
            return $stmt->execute([$sessionId, $memberId, $method]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function getAnalytics(string $startDate, string $endDate): array
    {
        return $this->query(
            "SELECT st.name AS service_name, COUNT(DISTINCT ar.member_id) AS unique_attendees,
             COUNT(ar.id) AS total_checkins
             FROM attendance_sessions s
             JOIN service_types st ON s.service_type_id = st.id
             LEFT JOIN attendance_records ar ON ar.session_id = s.id
             WHERE s.session_date BETWEEN ? AND ?
             GROUP BY st.id, st.name ORDER BY unique_attendees DESC",
            [$startDate, $endDate]
        );
    }

    public function getServiceTypes(): array
    {
        return $this->query("SELECT * FROM service_types WHERE is_active = 1 ORDER BY name");
    }

    public function getTodaySessions(?string $ageGroupKey = null): array
    {
        $params = [];
        $where = 's.session_date = CURDATE()';

        if ($ageGroupKey) {
            $where .= ' AND s.age_group = ?';
            $params[] = $ageGroupKey;
        }

        return $this->query(
            "SELECT s.id, s.age_group, st.name, s.session_date, s.title
             FROM attendance_sessions s
             JOIN service_types st ON s.service_type_id = st.id
             WHERE {$where}
             ORDER BY s.age_group, st.name, s.session_time",
            $params
        );
    }

    public function updateGeneralCounts(int $sessionId, int $male, int $female): bool
    {
        return $this->update($sessionId, [
            'male_count' => max(0, $male),
            'female_count' => max(0, $female),
        ]);
    }

    public function getGroupGenderStats(string $ageGroupKey, string $startDate, string $endDate): array
    {
        if ((new AgeGroupService())->usesGeneralAttendance($ageGroupKey)) {
            $row = $this->queryOne(
                "SELECT
                    COALESCE(SUM(s.male_count), 0) AS male,
                    COALESCE(SUM(s.female_count), 0) AS female
                 FROM attendance_sessions s
                 WHERE s.session_date BETWEEN ? AND ?
                 AND s.age_group = ?",
                [$startDate, $endDate, $ageGroupKey]
            ) ?? ['male' => 0, 'female' => 0];
        } else {
            $row = $this->queryOne(
                "SELECT
                    COALESCE(SUM(CASE WHEN m.gender = 'male' THEN 1 ELSE 0 END), 0) AS male,
                    COALESCE(SUM(CASE WHEN m.gender = 'female' THEN 1 ELSE 0 END), 0) AS female
                 FROM attendance_records ar
                 JOIN attendance_sessions s ON ar.session_id = s.id
                 JOIN members m ON ar.member_id = m.id
                 WHERE s.session_date BETWEEN ? AND ?
                 AND s.age_group = ?",
                [$startDate, $endDate, $ageGroupKey]
            ) ?? ['male' => 0, 'female' => 0];
        }

        $male = (int) $row['male'];
        $female = (int) $row['female'];

        return [
            'male' => $male,
            'female' => $female,
            'total' => $male + $female,
        ];
    }

    public function getSessionsForGroup(string $ageGroupKey, string $startDate, string $endDate, int $page = 1): array
    {
        $offset = ($page - 1) * 15;
        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM attendance_sessions WHERE session_date BETWEEN ? AND ? AND age_group = ?'
        );
        $countStmt->execute([$startDate, $endDate, $ageGroupKey]);
        $total = (int) $countStmt->fetchColumn();

        if ((new AgeGroupService())->usesGeneralAttendance($ageGroupKey)) {
            $data = $this->query(
                "SELECT s.*, st.name AS service_name,
                    COALESCE(s.male_count, 0) AS male_count,
                    COALESCE(s.female_count, 0) AS female_count
                 FROM attendance_sessions s
                 JOIN service_types st ON s.service_type_id = st.id
                 WHERE s.session_date BETWEEN ? AND ? AND s.age_group = ?
                 ORDER BY s.session_date DESC, s.session_time DESC
                 LIMIT 15 OFFSET ?",
                [$startDate, $endDate, $ageGroupKey, $offset]
            );
        } else {
            $data = $this->query(
                "SELECT s.*, st.name AS service_name,
                    COALESCE(SUM(CASE WHEN m.gender = 'male' THEN 1 ELSE 0 END), 0) AS male_count,
                    COALESCE(SUM(CASE WHEN m.gender = 'female' THEN 1 ELSE 0 END), 0) AS female_count
                 FROM attendance_sessions s
                 JOIN service_types st ON s.service_type_id = st.id
                 LEFT JOIN attendance_records ar ON ar.session_id = s.id
                 LEFT JOIN members m ON ar.member_id = m.id
                 WHERE s.session_date BETWEEN ? AND ? AND s.age_group = ?
                 GROUP BY s.id, st.name
                 ORDER BY s.session_date DESC, s.session_time DESC
                 LIMIT 15 OFFSET ?",
                [$startDate, $endDate, $ageGroupKey, $offset]
            );
        }

        foreach ($data as &$session) {
            $session['male_count'] = (int) $session['male_count'];
            $session['female_count'] = (int) $session['female_count'];
            $session['total_count'] = $session['male_count'] + $session['female_count'];
        }
        unset($session);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total > 0 ? (int) ceil($total / 15) : 0,
        ];
    }
}
