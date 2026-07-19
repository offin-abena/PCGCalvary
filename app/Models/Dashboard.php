<?php

namespace App\Models;

use App\Core\Model;
use App\Services\AgeGroupService;

class Dashboard extends Model
{
    protected string $table = 'members';

    public function getAdminOverview(): array
    {
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));

        return [
            'members' => $this->getMemberMetrics(),
            'finance' => $this->getFinanceMetrics($startOfMonth, $endOfMonth),
            'attendance' => $this->getAttendanceMetrics($startOfWeek, $endOfWeek),
            'operations' => $this->getOperationsMetrics(),
            'charts' => [
                'monthly_finance' => $this->getMonthlyFinance(6),
                'member_growth' => $this->getMemberGrowth(6),
                'attendance_by_service' => $this->getAttendanceByService($startOfMonth, $endOfMonth),
                'income_by_type' => $this->getIncomeByTypeChart($startOfMonth, $endOfMonth),
            ],
            'alerts' => $this->getAlerts(),
            'recent' => [
                'transactions' => $this->getRecentTransactions(8),
                'members' => $this->getRecentMembers(5),
                'sessions' => $this->getRecentSessions(5),
                'visitors' => $this->getRecentVisitors(5),
                'audit' => $this->getRecentAuditLogs(6),
            ],
            'lists' => [
                'events' => $this->getUpcomingEvents(5),
                'birthdays' => $this->getUpcomingBirthdays(7),
                'prayers' => $this->getPendingPrayers(5),
                'ministries' => $this->getMinistrySummary(),
            ],
        ];
    }

    private function getMemberMetrics(): array
    {
        $ageGroups = (new AgeGroupService())->countByGroup();

        return [
            'total' => (int) $this->db->query("SELECT COUNT(*) FROM members")->fetchColumn(),
            'active' => (int) $this->db->query("SELECT COUNT(*) FROM members WHERE membership_status = 'active'")->fetchColumn(),
            'visitors' => (int) $this->db->query("SELECT COUNT(*) FROM members WHERE membership_status = 'visitor'")->fetchColumn(),
            'new_converts' => (int) $this->db->query("SELECT COUNT(*) FROM members WHERE is_new_convert = 1")->fetchColumn(),
            'new_this_month' => (int) $this->db->query(
                "SELECT COUNT(*) FROM members WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
            )->fetchColumn(),
            'families' => (int) $this->db->query("SELECT COUNT(*) FROM families")->fetchColumn(),
            'children_service' => $ageGroups['children_service'] ?? 0,
            'junior_youth' => $ageGroups['junior_youth'] ?? 0,
            'adults' => $ageGroups['adults'] ?? 0,
        ];
    }

    private function getFinanceMetrics(string $start, string $end): array
    {
        $finance = new Finance();
        $summary = $finance->getSummary($start, $end);
        $yearStart = date('Y-01-01');

        $titheStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(ft.amount), 0) FROM financial_transactions ft
             JOIN income_types it ON ft.type_id = it.id
             WHERE ft.transaction_type = 'income' AND it.name = 'Tithe'
             AND ft.transaction_date BETWEEN ? AND ?"
        );
        $titheStmt->execute([$start, $end]);
        $titheMonth = (float) $titheStmt->fetchColumn();

        $tithePending = (int) $this->db->query(
            "SELECT COUNT(*) FROM members m
             LEFT JOIN families f ON m.family_id = f.id
             WHERE m.membership_status = 'active' AND m.phone IS NOT NULL AND m.phone != ''
             AND " . (new AgeGroupService())->titheEligibleMemberCondition('m', 'f') . "
             AND m.id NOT IN (
                 SELECT DISTINCT ft.member_id FROM financial_transactions ft
                 JOIN income_types it ON ft.type_id = it.id
                 WHERE ft.transaction_type = 'income' AND it.name = 'Tithe'
                 AND ft.member_id IS NOT NULL
                 AND MONTH(ft.transaction_date) = MONTH(CURDATE())
                 AND YEAR(ft.transaction_date) = YEAR(CURDATE())
             )"
        )->fetchColumn();

        $yearSummary = $finance->getSummary($yearStart, $end);

        return array_merge($summary, [
            'tithe_month' => $titheMonth,
            'tithe_pending' => $tithePending,
            'income_ytd' => $yearSummary['total_income'],
            'expense_ytd' => $yearSummary['total_expense'],
        ]);
    }

    private function getAttendanceMetrics(string $weekStart, string $weekEnd): array
    {
        $today = (int) $this->db->query(
            "SELECT COUNT(DISTINCT ar.member_id) FROM attendance_records ar
             JOIN attendance_sessions s ON ar.session_id = s.id
             WHERE s.session_date = CURDATE()"
        )->fetchColumn();

        $weekStmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT ar.member_id) FROM attendance_records ar
             JOIN attendance_sessions s ON ar.session_id = s.id
             WHERE s.session_date BETWEEN ? AND ?"
        );
        $weekStmt->execute([$weekStart, $weekEnd]);
        $week = (int) $weekStmt->fetchColumn();

        $lastSunday = (int) $this->db->query(
            "SELECT COUNT(ar.id) FROM attendance_records ar
             JOIN attendance_sessions s ON ar.session_id = s.id
             JOIN service_types st ON s.service_type_id = st.id
             WHERE st.name LIKE '%Sunday%' AND s.session_date = (
                 SELECT MAX(s2.session_date) FROM attendance_sessions s2
                 JOIN service_types st2 ON s2.service_type_id = st2.id
                 WHERE st2.name LIKE '%Sunday%'
             )"
        )->fetchColumn();

        $sessionsMonth = (int) $this->db->query(
            "SELECT COUNT(*) FROM attendance_sessions
             WHERE MONTH(session_date) = MONTH(NOW()) AND YEAR(session_date) = YEAR(NOW())"
        )->fetchColumn();

        return [
            'today' => $today,
            'week' => $week,
            'last_sunday' => $lastSunday,
            'sessions_month' => $sessionsMonth,
        ];
    }

    private function getOperationsMetrics(): array
    {
        $smsTotal = 0;
        $smsFailed = 0;
        try {
            $smsTotal = (int) $this->db->query("SELECT COUNT(*) FROM sms_logs WHERE MONTH(created_at) = MONTH(NOW())")->fetchColumn();
            $smsFailed = (int) $this->db->query("SELECT COUNT(*) FROM sms_logs WHERE status = 'failed' AND MONTH(created_at) = MONTH(NOW())")->fetchColumn();
        } catch (\PDOException $e) {
            // sms_logs may not exist
        }

        return [
            'events_upcoming' => (int) $this->db->query(
                "SELECT COUNT(*) FROM events WHERE start_date >= NOW() AND status IN ('published','ongoing')"
            )->fetchColumn(),
            'ministries' => (int) $this->db->query("SELECT COUNT(*) FROM ministries WHERE is_active = 1")->fetchColumn(),
            'pending_visitors' => (int) $this->db->query(
                "SELECT COUNT(*) FROM visitors WHERE follow_up_status = 'pending'"
            )->fetchColumn(),
            'pending_prayers' => (int) $this->db->query(
                "SELECT COUNT(*) FROM prayer_requests WHERE status = 'pending'"
            )->fetchColumn(),
            'active_pledges' => (int) $this->db->query(
                "SELECT COUNT(*) FROM pledges WHERE status = 'active'"
            )->fetchColumn(),
            'assets' => (int) $this->db->query("SELECT COUNT(*) FROM assets WHERE is_active = 1")->fetchColumn(),
            'maintenance_due' => (int) $this->db->query(
                "SELECT COUNT(*) FROM assets WHERE next_maintenance_date IS NOT NULL AND next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)"
            )->fetchColumn(),
            'sms_month' => $smsTotal,
            'sms_failed' => $smsFailed,
            'users' => (int) $this->db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
        ];
    }

    private function getMonthlyFinance(int $months): array
    {
        return $this->query(
            "SELECT DATE_FORMAT(transaction_date, '%Y-%m') as month,
             DATE_FORMAT(transaction_date, '%b') as label,
             SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as income,
             SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as expense
             FROM financial_transactions
             WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month, label ORDER BY month",
            [$months]
        );
    }

    private function getMemberGrowth(int $months): array
    {
        return $this->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
             DATE_FORMAT(created_at, '%b') as label,
             COUNT(*) as count
             FROM members
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month, label ORDER BY month",
            [$months]
        );
    }

    private function getAttendanceByService(string $start, string $end): array
    {
        return $this->query(
            "SELECT st.name, COUNT(DISTINCT ar.member_id) as attendees
             FROM attendance_sessions s
             JOIN service_types st ON s.service_type_id = st.id
             LEFT JOIN attendance_records ar ON ar.session_id = s.id
             WHERE s.session_date BETWEEN ? AND ?
             GROUP BY st.id, st.name ORDER BY attendees DESC",
            [$start, $end]
        );
    }

    private function getIncomeByTypeChart(string $start, string $end): array
    {
        $finance = new Finance();
        return $finance->getIncomeByType($start, $end);
    }

    public function getAlerts(): array
    {
        $alerts = [];

        $pendingVisitors = (int) $this->db->query(
            "SELECT COUNT(*) FROM visitors WHERE follow_up_status = 'pending'"
        )->fetchColumn();
        if ($pendingVisitors > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => 'person-plus', 'message' => "$pendingVisitors visitors need follow-up", 'link' => 'visitors'];
        }

        $tithePending = (int) $this->db->query(
            "SELECT COUNT(*) FROM members m
             LEFT JOIN families f ON m.family_id = f.id
             WHERE m.membership_status = 'active'
             AND " . (new AgeGroupService())->titheEligibleMemberCondition('m', 'f') . "
             AND m.id NOT IN (
                 SELECT DISTINCT ft.member_id FROM financial_transactions ft
                 JOIN income_types it ON ft.type_id = it.id
                 WHERE ft.transaction_type = 'income' AND it.name = 'Tithe'
                 AND ft.member_id IS NOT NULL
                 AND MONTH(ft.transaction_date) = MONTH(CURDATE())
             )"
        )->fetchColumn();
        if ($tithePending > 0) {
            $alerts[] = ['type' => 'info', 'icon' => 'cash', 'message' => "$tithePending members have not tithed this month", 'link' => 'finance/tithe-reminders'];
        }

        $prayers = (int) $this->db->query("SELECT COUNT(*) FROM prayer_requests WHERE status = 'pending'")->fetchColumn();
        if ($prayers > 0) {
            $alerts[] = ['type' => 'primary', 'icon' => 'heart', 'message' => "$prayers pending prayer requests", 'link' => 'communication/prayer'];
        }

        $maintenance = (int) $this->db->query(
            "SELECT COUNT(*) FROM assets WHERE next_maintenance_date <= CURDATE()"
        )->fetchColumn();
        if ($maintenance > 0) {
            $alerts[] = ['type' => 'danger', 'icon' => 'tools', 'message' => "$maintenance assets need maintenance", 'link' => 'assets'];
        }

        try {
            $smsFailed = (int) $this->db->query(
                "SELECT COUNT(*) FROM sms_logs WHERE status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )->fetchColumn();
            if ($smsFailed > 0) {
                $alerts[] = ['type' => 'danger', 'icon' => 'phone', 'message' => "$smsFailed SMS failed in the last 7 days", 'link' => 'finance/sms-logs'];
            }
        } catch (\PDOException $e) {}

        $upcomingEvents = (int) $this->db->query(
            "SELECT COUNT(*) FROM events WHERE start_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY) AND status = 'published'"
        )->fetchColumn();
        if ($upcomingEvents > 0) {
            $alerts[] = ['type' => 'success', 'icon' => 'calendar-event', 'message' => "$upcomingEvents events in the next 3 days", 'link' => 'events'];
        }

        return $alerts;
    }

    private function getRecentTransactions(int $limit): array
    {
        return $this->query(
            "SELECT ft.*,
             CASE WHEN ft.transaction_type = 'income' THEN it.name ELSE et.name END as type_name,
             CONCAT(m.first_name, ' ', m.last_name) as member_name
             FROM financial_transactions ft
             LEFT JOIN income_types it ON ft.transaction_type = 'income' AND ft.type_id = it.id
             LEFT JOIN expense_types et ON ft.transaction_type = 'expense' AND ft.type_id = et.id
             LEFT JOIN members m ON ft.member_id = m.id
             ORDER BY ft.created_at DESC LIMIT ?",
            [$limit]
        );
    }

    private function getRecentMembers(int $limit): array
    {
        return $this->query("SELECT * FROM members ORDER BY created_at DESC LIMIT ?", [$limit]);
    }

    private function getRecentSessions(int $limit): array
    {
        return $this->query(
            "SELECT s.*, st.name as service_name,
             (SELECT COUNT(*) FROM attendance_records ar WHERE ar.session_id = s.id) as attendee_count
             FROM attendance_sessions s
             JOIN service_types st ON s.service_type_id = st.id
             ORDER BY s.session_date DESC LIMIT ?",
            [$limit]
        );
    }

    private function getRecentVisitors(int $limit): array
    {
        return $this->query("SELECT * FROM visitors ORDER BY visit_date DESC LIMIT ?", [$limit]);
    }

    private function getRecentAuditLogs(int $limit): array
    {
        return $this->query(
            "SELECT al.*, u.username FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC LIMIT ?",
            [$limit]
        );
    }

    private function getUpcomingEvents(int $limit): array
    {
        return $this->query(
            "SELECT e.*, (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) as registration_count
             FROM events e WHERE e.start_date >= NOW() AND e.status IN ('published','ongoing')
             ORDER BY e.start_date ASC LIMIT ?",
            [$limit]
        );
    }

    private function getUpcomingBirthdays(int $days): array
    {
        return (new Member())->getBirthdays($days);
    }

    private function getPendingPrayers(int $limit): array
    {
        return $this->query(
            "SELECT pr.*, CONCAT(m.first_name, ' ', m.last_name) as member_name
             FROM prayer_requests pr LEFT JOIN members m ON pr.member_id = m.id
             WHERE pr.status = 'pending' ORDER BY pr.created_at DESC LIMIT ?",
            [$limit]
        );
    }

    private function getMinistrySummary(): array
    {
        return $this->query(
            "SELECT mn.name, mn.ministry_type,
             (SELECT COUNT(*) FROM ministry_members mm WHERE mm.ministry_id = mn.id AND mm.is_active = 1) as members
             FROM ministries mn WHERE mn.is_active = 1 ORDER BY members DESC LIMIT 5"
        );
    }
}
