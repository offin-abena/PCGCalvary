<?php

namespace App\Services;

use App\Core\Model;

class TitheSmsService
{
    private SmsService $sms;
    private array $config;
    private array $appConfig;

    public function __construct()
    {
        $this->sms = new SmsService();
        $this->config = require config_path('sms.php');
        $this->appConfig = config('church');
    }

    public function sendTitheReceipt(array $member, float $amount, string $receiptNumber, string $date): ?array
    {
        if (empty($member['phone'])) {
            return null;
        }

        $template = $this->config['templates']['tithe_receipt'];
        $message = $this->sms->applyTemplate($template, [
            'name' => $member['first_name'],
            'amount' => 'GHS ' . number_format($amount, 2),
            'date' => date('M d, Y', strtotime($date)),
            'receipt' => $receiptNumber,
            'church' => $this->appConfig['church_name'],
        ]);

        return $this->sms->send($member['phone'], $message, (int) $member['id'], 'tithe_receipt');
    }

    public function sendTitheReminders(bool $onlyWithoutTithe = true): array
    {
        $db = new class extends Model { protected string $table = 'members'; };
        $template = $this->config['templates']['tithe_reminder'];
        $month = date('F Y');
        $excludeChildren = (new AgeGroupService())->titheEligibleMemberCondition('m', 'f');

        if ($onlyWithoutTithe) {
            $members = $db->query(
                "SELECT m.* FROM members m
                 LEFT JOIN families f ON m.family_id = f.id
                 WHERE m.membership_status = 'active'
                 AND m.phone IS NOT NULL AND m.phone != ''
                 AND {$excludeChildren}
                 AND m.id NOT IN (
                     SELECT DISTINCT ft.member_id FROM financial_transactions ft
                     JOIN income_types it ON ft.type_id = it.id
                     WHERE ft.transaction_type = 'income'
                     AND it.name = 'Tithe'
                     AND ft.member_id IS NOT NULL
                     AND MONTH(ft.transaction_date) = MONTH(CURDATE())
                     AND YEAR(ft.transaction_date) = YEAR(CURDATE())
                 )"
            );
        } else {
            $members = $db->query(
                "SELECT m.* FROM members m
                 LEFT JOIN families f ON m.family_id = f.id
                 WHERE m.membership_status = 'active'
                 AND m.phone IS NOT NULL AND m.phone != ''
                 AND {$excludeChildren}"
            );
        }

        $recipients = array_map(function ($m) use ($month) {
            return [
                'id' => $m['id'],
                'phone' => $m['phone'],
                'name' => $m['first_name'] . ' ' . $m['last_name'],
                'first_name' => $m['first_name'],
                'month' => $month,
            ];
        }, $members);

        $results = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'total' => count($recipients), 'details' => []];

        foreach ($recipients as $recipient) {
            $personalMessage = $this->sms->applyTemplate($template, [
                'name' => $recipient['first_name'],
                'month' => $month,
                'church' => $this->appConfig['church_name'],
            ]);

            $result = $this->sms->send($recipient['phone'], $personalMessage, $recipient['id'], 'tithe_reminder');

            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'name' => $recipient['name'],
                'phone' => $recipient['phone'],
                'success' => $result['success'],
                'error' => $result['message'] ?? null,
            ];
        }

        return $results;
    }

    public function isTitheType(int $incomeTypeId): bool
    {
        $db = new class extends Model { protected string $table = 'income_types'; };
        $type = $db->find($incomeTypeId);
        return $type && stripos($type['name'], 'tithe') !== false;
    }

    public function getSmsStats(): array
    {
        try {
            $db = new class extends Model { protected string $table = 'sms_logs'; };
            return [
                'total' => (int) ($db->queryOne("SELECT COUNT(*) as c FROM sms_logs")['c'] ?? 0),
                'tithe_receipts' => (int) ($db->queryOne("SELECT COUNT(*) as c FROM sms_logs WHERE context = 'tithe_receipt'")['c'] ?? 0),
                'tithe_reminders' => (int) ($db->queryOne("SELECT COUNT(*) as c FROM sms_logs WHERE context = 'tithe_reminder'")['c'] ?? 0),
                'failed' => (int) ($db->queryOne("SELECT COUNT(*) as c FROM sms_logs WHERE status = 'failed'")['c'] ?? 0),
                'recent' => $db->query("SELECT sl.*, CONCAT(m.first_name, ' ', m.last_name) as member_name FROM sms_logs sl LEFT JOIN members m ON sl.member_id = m.id ORDER BY sl.created_at DESC LIMIT 10"),
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'tithe_receipts' => 0, 'tithe_reminders' => 0, 'failed' => 0, 'recent' => []];
        }
    }
}
