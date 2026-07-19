<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Http\Controllers\Controller;
use App\Core\Model;
use App\Models\Member;
use App\Services\SmsService;
use App\Services\TitheSmsService;

class AutomationController extends Controller
{
    public function index(): void
    {
        $db = new class extends Model { protected string $table = 'automation_rules'; };
        $rules = $db->query("SELECT ar.*, (SELECT COUNT(*) FROM automation_logs al WHERE al.rule_id = ar.id) as run_count FROM automation_rules ar ORDER BY ar.name");
        $recentLogs = $db->query(
            "SELECT al.*, ar.name as rule_name FROM automation_logs al 
             JOIN automation_rules ar ON al.rule_id = ar.id ORDER BY al.executed_at DESC LIMIT 20"
        );

        $this->render('automation/index', [
            'title' => 'Automation',
            'rules' => $rules,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function toggle(string $id): void
    {
        $db = new class extends Model { protected string $table = 'automation_rules'; };
        $rule = $db->find((int) $id);
        if ($rule) {
            $db->update((int) $id, ['is_active' => $rule['is_active'] ? 0 : 1]);
            AuditLog::log('toggle', 'automation', (int) $id);
        }
        $this->redirect('automation');
    }

    public function run(): void
    {
        $db = new class extends Model { protected string $table = 'automation_rules'; };
        $memberModel = new Member();
        $rules = $db->query("SELECT * FROM automation_rules WHERE is_active = 1");
        $results = [];

        $sms = new SmsService();
        $titheSms = new TitheSmsService();
        $smsConfig = config('sms');
        $appConfig = config('church');

        foreach ($rules as $rule) {
            $recipients = 0;
            $status = 'success';
            $details = '';

            switch ($rule['rule_type']) {
                case 'birthday':
                    $members = $memberModel->getBirthdays(0);
                    foreach ($members as $m) {
                        if (!empty($m['phone'])) {
                            $msg = $sms->applyTemplate($rule['template_body'], [
                                'name' => $m['first_name'],
                                'church' => $appConfig['church_name'],
                            ]);
                            $r = $sms->send($m['phone'], $msg, (int) $m['id'], 'automation');
                            if ($r['success']) $recipients++;
                        }
                    }
                    $details = "Sent birthday SMS to {$recipients} members";
                    break;

                case 'anniversary':
                    $members = $memberModel->query(
                        "SELECT * FROM members WHERE membership_status = 'active' AND membership_date IS NOT NULL 
                         AND DATE_FORMAT(membership_date, '%m-%d') = DATE_FORMAT(NOW(), '%m-%d')"
                    );
                    foreach ($members as $m) {
                        if (!empty($m['phone'])) {
                            $msg = $sms->applyTemplate($rule['template_body'], [
                                'name' => $m['first_name'],
                                'church' => $appConfig['church_name'],
                            ]);
                            $r = $sms->send($m['phone'], $msg, (int) $m['id'], 'automation');
                            if ($r['success']) $recipients++;
                        }
                    }
                    $details = "Sent anniversary SMS to {$recipients} members";
                    break;

                case 'visitor_followup':
                    $visitors = $memberModel->query(
                        "SELECT * FROM visitors WHERE follow_up_status = 'pending' AND visit_date <= DATE_SUB(NOW(), INTERVAL 3 DAY)"
                    );
                    foreach ($visitors as $v) {
                        if (!empty($v['phone'])) {
                            $msg = $sms->applyTemplate($rule['template_body'], [
                                'name' => $v['first_name'],
                                'church' => $appConfig['church_name'],
                            ]);
                            $r = $sms->send($v['phone'], $msg, null, 'automation');
                            if ($r['success']) $recipients++;
                        }
                    }
                    $details = "Sent visitor follow-up SMS to {$recipients} visitors";
                    break;

                case 'tithe_reminder':
                    $smsResults = $titheSms->sendTitheReminders($smsConfig['reminder_only_without_tithe']);
                    $recipients = $smsResults['sent'];
                    $status = $smsResults['failed'] > 0 ? 'partial' : 'success';
                    $details = "Tithe reminders: {$smsResults['sent']} sent, {$smsResults['failed']} failed";
                    break;
            }

            $logDb = new class extends Model { protected string $table = 'automation_logs'; };
            $logDb->create([
                'rule_id' => $rule['id'],
                'recipients_count' => $recipients,
                'status' => $status,
                'details' => $details ?: "Processed {$recipients} recipients for {$rule['name']}",
            ]);

            $db->update($rule['id'], ['last_run' => date('Y-m-d H:i:s')]);
            $results[] = "{$rule['name']}: {$details}";
        }

        AuditLog::log('run', 'automation');
        $this->flash('success', 'Automation executed: ' . implode(', ', $results));
        $this->redirect('automation');
    }
}
