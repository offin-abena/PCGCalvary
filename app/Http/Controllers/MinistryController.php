<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Http\Controllers\Controller;
use App\Core\Model;
use App\Models\Member;

class MinistryController extends Controller
{
    public function index(): void
    {
        $db = new class extends Model { protected string $table = 'ministries'; };
        $ministries = $db->query(
            "SELECT mn.*, CONCAT(l.first_name, ' ', l.last_name) as leader_name,
             (SELECT COUNT(*) FROM ministry_members mm WHERE mm.ministry_id = mn.id AND mm.is_active = 1) as member_count
             FROM ministries mn LEFT JOIN members l ON mn.leader_id = l.id ORDER BY mn.name"
        );

        $this->render('ministries/index', ['title' => 'Ministries & Groups', 'ministries' => $ministries]);
    }

    public function create(): void
    {
        $members = (new Member())->query("SELECT id, first_name, last_name FROM members WHERE membership_status = 'active' ORDER BY last_name");
        $this->render('ministries/create', ['title' => 'Create Ministry', 'members' => $members]);
    }

    public function store(): void
    {
        $db = new class extends Model { protected string $table = 'ministries'; };
        $id = $db->create([
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'ministry_type' => $this->input('ministry_type', 'other'),
            'leader_id' => $this->input('leader_id') ?: null,
            'meeting_day' => $this->input('meeting_day'),
            'meeting_time' => $this->input('meeting_time') ?: null,
            'meeting_location' => $this->input('meeting_location'),
        ]);

        AuditLog::log('create', 'ministries', $id);
        $this->flash('success', 'Ministry created successfully.');
        $this->redirect('ministries/' . $id);
    }

    public function show(string $id): void
    {
        $db = new class extends Model { protected string $table = 'ministries'; };
        $ministry = $db->queryOne(
            "SELECT mn.*, CONCAT(l.first_name, ' ', l.last_name) as leader_name 
             FROM ministries mn LEFT JOIN members l ON mn.leader_id = l.id WHERE mn.id = ?",
            [$id]
        );

        if (!$ministry) {
            $this->redirect('ministries');
        }

        $members = $db->query(
            "SELECT mm.*, CONCAT(m.first_name, ' ', m.last_name) as member_name 
             FROM ministry_members mm JOIN members m ON mm.member_id = m.id 
             WHERE mm.ministry_id = ? AND mm.is_active = 1",
            [$id]
        );

        $meetings = $db->query("SELECT * FROM ministry_meetings WHERE ministry_id = ? ORDER BY meeting_date DESC LIMIT 10", [$id]);
        $allMembers = (new Member())->query("SELECT id, first_name, last_name FROM members WHERE membership_status = 'active' ORDER BY last_name");

        $this->render('ministries/show', [
            'title' => $ministry['name'],
            'ministry' => $ministry,
            'members' => $members,
            'meetings' => $meetings,
            'allMembers' => $allMembers,
        ]);
    }

    public function addMember(string $id): void
    {
        $db = new class extends Model { protected string $table = 'ministry_members'; };
        try {
            $db->create([
                'ministry_id' => (int) $id,
                'member_id' => $this->input('member_id'),
                'role' => $this->input('role', 'member'),
                'joined_date' => date('Y-m-d'),
            ]);
            $this->flash('success', 'Member added to ministry.');
        } catch (\Exception $e) {
            $this->flash('warning', 'Member already in this ministry.');
        }
        $this->redirect('ministries/' . $id);
    }
}
