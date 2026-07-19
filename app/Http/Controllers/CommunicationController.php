<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Http\Controllers\Controller;
use App\Core\Model;
use App\Services\SmsService;

class CommunicationController extends Controller
{
    public function index(): void
    {
        $db = new class extends Model { protected string $table = 'announcements'; };
        $announcements = $db->query("SELECT a.*, u.username as author FROM announcements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC LIMIT 5");
        $messages = $db->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5");
        $prayerCount = (int) $db->queryOne("SELECT COUNT(*) as cnt FROM prayer_requests WHERE status = 'pending'")['cnt'] ?? 0;

        $this->render('communication/index', [
            'title' => 'Communication',
            'announcements' => $announcements,
            'messages' => $messages,
            'pendingPrayers' => $prayerCount,
        ]);
    }

    public function announcements(): void
    {
        $db = new class extends Model { protected string $table = 'announcements'; };
        $this->render('communication/announcements', [
            'title' => 'Announcements',
            'announcements' => $db->query("SELECT a.*, u.username as author FROM announcements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC"),
        ]);
    }

    public function storeAnnouncement(): void
    {
        $db = new class extends Model { protected string $table = 'announcements'; };
        $id = $db->create([
            'title' => $this->input('title'),
            'content' => $this->input('content'),
            'priority' => $this->input('priority', 'normal'),
            'target_audience' => $this->input('target_audience', 'all'),
            'publish_date' => $this->input('publish_date') ?: date('Y-m-d H:i:s'),
            'expiry_date' => $this->input('expiry_date') ?: null,
            'is_published' => $this->input('is_published') ? 1 : 0,
            'created_by' => Auth::id(),
        ]);

        AuditLog::log('create', 'announcements', $id);
        $this->flash('success', 'Announcement created.');
        $this->redirect('communication/announcements');
    }

    public function messages(): void
    {
        $db = new class extends Model { protected string $table = 'messages'; };
        $members = $db->query("SELECT id, first_name, last_name, phone, email FROM members WHERE membership_status = 'active' ORDER BY last_name");

        $this->render('communication/messages', [
            'title' => 'Send Messages',
            'messages' => $db->query("SELECT m.*, u.username as sender FROM messages m JOIN users u ON m.sender_id = u.id ORDER BY m.created_at DESC LIMIT 20"),
            'members' => $members,
        ]);
    }

    public function sendMessage(): void
    {
        $db = new class extends Model { protected string $table = 'messages'; };
        $messageType = $this->input('message_type');
        $content = $this->input('content');
        $sentCount = 0;
        $failedCount = 0;

        $messageId = $db->create([
            'sender_id' => Auth::id(),
            'message_type' => $messageType,
            'subject' => $this->input('subject'),
            'content' => $content,
            'recipient_type' => $this->input('recipient_type', 'all'),
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
        ]);

        $recipientModel = new class extends Model { protected string $table = 'message_recipients'; };
        $sms = new SmsService();

        if ($this->input('recipient_type') === 'individual' && $this->input('member_id')) {
            $members = [$db->queryOne("SELECT * FROM members WHERE id = ?", [$this->input('member_id')])];
        } else {
            $members = $db->query("SELECT * FROM members WHERE membership_status = 'active'");
        }

        foreach ($members as $member) {
            if (!$member) continue;

            $recipientStatus = 'sent';

            if ($messageType === 'sms' && !empty($member['phone'])) {
                $result = $sms->send($member['phone'], $content, (int) $member['id'], 'bulk');
                $recipientStatus = $result['success'] ? 'delivered' : 'failed';
                $result['success'] ? $sentCount++ : $failedCount++;
            } elseif ($messageType === 'sms') {
                $recipientStatus = 'failed';
                $failedCount++;
            } else {
                $sentCount++;
            }

            $recipientModel->create([
                'message_id' => $messageId,
                'member_id' => $member['id'],
                'email' => $member['email'],
                'phone' => $member['phone'],
                'status' => $recipientStatus,
            ]);
        }

        AuditLog::log('send', 'messages', $messageId);
        $flash = $messageType === 'sms'
            ? "SMS sent: {$sentCount} delivered, {$failedCount} failed."
            : 'Message sent successfully.';
        $this->flash($failedCount > 0 ? 'warning' : 'success', $flash);
        $this->redirect('communication/messages');
    }

    public function prayerRequests(): void
    {
        $db = new class extends Model { protected string $table = 'prayer_requests'; };
        $this->render('communication/prayer', [
            'title' => 'Prayer Requests',
            'requests' => $db->query(
                "SELECT pr.*, CONCAT(m.first_name, ' ', m.last_name) as member_name 
                 FROM prayer_requests pr LEFT JOIN members m ON pr.member_id = m.id ORDER BY pr.created_at DESC"
            ),
        ]);
    }

    public function storePrayerRequest(): void
    {
        $db = new class extends Model { protected string $table = 'prayer_requests'; };
        $id = $db->create([
            'member_id' => $this->input('member_id') ?: null,
            'requester_name' => $this->input('requester_name'),
            'request_text' => $this->input('request_text'),
            'is_anonymous' => $this->input('is_anonymous') ? 1 : 0,
            'is_confidential' => $this->input('is_confidential') ? 1 : 0,
            'status' => 'pending',
        ]);

        AuditLog::log('create', 'prayer', $id);
        $this->flash('success', 'Prayer request submitted.');
        $this->redirect('communication/prayer');
    }
}
