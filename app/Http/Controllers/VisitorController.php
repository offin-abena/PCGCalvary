<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Http\Controllers\Controller;
use App\Models\Visitor;

class VisitorController extends Controller
{
    public function index(): void
    {
        $model = new Visitor();
        $this->render('visitors/index', [
            'title' => 'Visitors',
            'visitors' => $model->getWithInviter((int) $this->input('page', 1)),
            'pendingFollowups' => $model->getPendingFollowups(),
        ]);
    }

    public function create(): void
    {
        $members = (new Visitor())->query("SELECT id, first_name, last_name FROM members WHERE membership_status = 'active' ORDER BY last_name");
        $this->render('visitors/create', ['title' => 'Register Visitor', 'members' => $members]);
    }

    public function store(): void
    {
        $model = new Visitor();
        $id = $model->create([
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'phone' => $this->input('phone'),
            'email' => $this->input('email'),
            'address' => $this->input('address'),
            'visit_date' => $this->input('visit_date', date('Y-m-d')),
            'invited_by' => $this->input('invited_by') ?: null,
            'follow_up_notes' => $this->input('notes'),
        ]);

        AuditLog::log('create', 'visitors', $id);
        $this->flash('success', 'Visitor registered successfully.');
        $this->redirect('visitors');
    }

    public function followup(string $id): void
    {
        $model = new Visitor();
        $model->update((int) $id, [
            'follow_up_status' => $this->input('follow_up_status'),
            'follow_up_date' => date('Y-m-d'),
            'follow_up_notes' => $this->input('follow_up_notes'),
        ]);

        AuditLog::log('followup', 'visitors', (int) $id);
        $this->flash('success', 'Follow-up updated.');
        $this->redirect('visitors');
    }
}
