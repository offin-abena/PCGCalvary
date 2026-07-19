<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Http\Controllers\Controller;
use App\Core\Model;
use App\Models\Member;

class CounselingController extends Controller
{
    public function index(): void
    {
        $db = new class extends Model { protected string $table = 'counseling_appointments'; };
        $appointments = $db->query(
            "SELECT ca.*, CONCAT(m.first_name, ' ', m.last_name) as member_name,
             CONCAT(c.first_name, ' ', c.last_name) as counselor_name
             FROM counseling_appointments ca
             JOIN members m ON ca.member_id = m.id
             LEFT JOIN members c ON ca.counselor_id = c.id
             ORDER BY ca.appointment_date DESC"
        );

        $this->render('counseling/index', ['title' => 'Counseling', 'appointments' => $appointments]);
    }

    public function schedule(): void
    {
        $members = (new Member())->query("SELECT id, first_name, last_name FROM members ORDER BY last_name");
        $counselors = (new Member())->query("SELECT id, first_name, last_name FROM members WHERE membership_status = 'active' ORDER BY last_name");

        $this->render('counseling/schedule', [
            'title' => 'Schedule Appointment',
            'members' => $members,
            'counselors' => $counselors,
        ]);
    }

    public function store(): void
    {
        $db = new class extends Model { protected string $table = 'counseling_appointments'; };
        $id = $db->create([
            'member_id' => $this->input('member_id'),
            'counselor_id' => $this->input('counselor_id') ?: null,
            'appointment_date' => $this->input('appointment_date'),
            'duration_minutes' => $this->input('duration_minutes', 60),
            'topic' => $this->input('topic'),
            'is_confidential' => $this->input('is_confidential') ? 1 : 0,
        ]);

        AuditLog::log('create', 'counseling', $id);
        $this->flash('success', 'Appointment scheduled.');
        $this->redirect('counseling/' . $id);
    }

    public function show(string $id): void
    {
        $db = new class extends Model { protected string $table = 'counseling_appointments'; };
        $appointment = $db->queryOne(
            "SELECT ca.*, CONCAT(m.first_name, ' ', m.last_name) as member_name,
             CONCAT(c.first_name, ' ', c.last_name) as counselor_name
             FROM counseling_appointments ca
             JOIN members m ON ca.member_id = m.id
             LEFT JOIN members c ON ca.counselor_id = c.id
             WHERE ca.id = ?",
            [$id]
        );

        if (!$appointment) {
            $this->redirect('counseling');
        }

        $notes = $db->query(
            "SELECT cn.*, CONCAT(c.first_name, ' ', c.last_name) as counselor_name 
             FROM counseling_notes cn JOIN members c ON cn.counselor_id = c.id 
             WHERE cn.appointment_id = ? ORDER BY cn.created_at DESC",
            [$id]
        );

        $followups = $db->query("SELECT * FROM counseling_followups WHERE appointment_id = ? ORDER BY follow_up_date", [$id]);

        $this->render('counseling/show', [
            'title' => 'Counseling Session',
            'appointment' => $appointment,
            'notes' => $notes,
            'followups' => $followups,
        ]);
    }

    public function addNotes(string $id): void
    {
        $db = new class extends Model { protected string $table = 'counseling_notes'; };
        $db->create([
            'appointment_id' => (int) $id,
            'counselor_id' => $this->input('counselor_id'),
            'notes' => $this->input('notes'),
            'follow_up_required' => $this->input('follow_up_required') ? 1 : 0,
            'follow_up_date' => $this->input('follow_up_date') ?: null,
        ]);

        if ($this->input('follow_up_required') && $this->input('follow_up_date')) {
            $followupDb = new class extends Model { protected string $table = 'counseling_followups'; };
            $followupDb->create([
                'appointment_id' => (int) $id,
                'follow_up_date' => $this->input('follow_up_date'),
                'notes' => 'Follow-up from counseling session',
            ]);
        }

        AuditLog::log('add_notes', 'counseling', (int) $id);
        $this->flash('success', 'Notes added (confidential).');
        $this->redirect('counseling/' . $id);
    }
}
