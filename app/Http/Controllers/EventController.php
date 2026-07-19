<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Member;

class EventController extends Controller
{
    private Event $model;

    public function __construct()
    {
        $this->model = new Event();
    }

    public function index(): void
    {
        $this->render('events/index', [
            'title' => 'Events',
            'events' => $this->model->getAll((int) $this->input('page', 1)),
            'upcoming' => $this->model->getUpcoming(5),
        ]);
    }

    public function create(): void
    {
        $this->render('events/create', ['title' => 'Create Event']);
    }

    public function store(): void
    {
        $id = $this->model->create([
            'title' => $this->input('title'),
            'description' => $this->input('description'),
            'event_type' => $this->input('event_type', 'other'),
            'start_date' => $this->input('start_date'),
            'end_date' => $this->input('end_date') ?: null,
            'location' => $this->input('location'),
            'max_attendees' => $this->input('max_attendees') ?: null,
            'registration_required' => $this->input('registration_required') ? 1 : 0,
            'status' => $this->input('status', 'draft'),
            'created_by' => Auth::id(),
        ]);

        AuditLog::log('create', 'events', $id);
        $this->flash('success', 'Event created successfully.');
        $this->redirect('events/' . $id);
    }

    public function show(string $id): void
    {
        $event = $this->model->find((int) $id);
        if (!$event) {
            $this->redirect('events');
        }

        $this->render('events/show', [
            'title' => $event['title'],
            'event' => $event,
            'registrations' => $this->model->getRegistrations((int) $id),
            'volunteers' => $this->model->getVolunteers((int) $id),
            'members' => (new Member())->query("SELECT id, first_name, last_name FROM members WHERE membership_status = 'active' ORDER BY last_name"),
        ]);
    }

    public function register(string $id): void
    {
        $regModel = new class extends \App\Core\Model { protected string $table = 'event_registrations'; };
        $regModel->create([
            'event_id' => (int) $id,
            'member_id' => $this->input('member_id') ?: null,
            'guest_name' => $this->input('guest_name'),
            'guest_email' => $this->input('guest_email'),
            'guest_phone' => $this->input('guest_phone'),
        ]);

        AuditLog::log('register', 'events', (int) $id);
        $this->flash('success', 'Registration recorded.');
        $this->redirect('events/' . $id);
    }
}
