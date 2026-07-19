<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Member;
use App\Services\AgeGroupService;

class AttendanceController extends Controller
{
    private Attendance $model;
    private AgeGroupService $ageGroups;

    public function __construct()
    {
        $this->model = new Attendance();
        $this->ageGroups = new AgeGroupService();
    }

    public function index(): void
    {
        $this->redirect('attendance/adults');
    }

    public function adults(): void
    {
        $this->listGroup('General Attendance', 'adults');
    }

    public function childrenService(): void
    {
        $this->listGroup('Children Service Attendance', 'children_service');
    }

    public function juniorYouth(): void
    {
        $this->listGroup('Junior Youth Attendance', 'junior_youth');
    }

    private function listGroup(string $title, string $ageGroupKey): void
    {
        $startDate = $this->input('start_date', date('Y-m-01'));
        $endDate = $this->input('end_date', date('Y-m-t'));
        $page = (int) $this->input('page', 1);
        $general = $this->ageGroups->usesGeneralAttendance($ageGroupKey);

        $this->render('attendance/group', [
            'title' => $title,
            'ageGroup' => $ageGroupKey,
            'ageGroupLabel' => $general && $ageGroupKey === 'adults'
                ? 'General'
                : $this->ageGroups->getLabel($ageGroupKey),
            'general' => $general,
            'listUrl' => $this->ageGroups->getAttendanceListUrl($ageGroupKey),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'stats' => $this->model->getGroupGenderStats($ageGroupKey, $startDate, $endDate),
            'sessions' => $this->model->getSessionsForGroup($ageGroupKey, $startDate, $endDate, $page),
        ]);
    }

    public function create(): void
    {
        $ageGroup = $this->ageGroups->normalizeGroupKey($this->input('group'));
        $general = $this->ageGroups->usesGeneralAttendance($ageGroup);

        $this->render('attendance/create', [
            'title' => 'New ' . $this->ageGroups->getLabel($ageGroup) . ' Session',
            'serviceTypes' => $this->model->getServiceTypes(),
            'ageGroup' => $ageGroup,
            'ageGroupLabel' => $general && $ageGroup === 'adults'
                ? 'General (Adults)'
                : $this->ageGroups->getLabel($ageGroup),
            'general' => $general,
            'listUrl' => $this->ageGroups->getAttendanceListUrl($ageGroup),
        ]);
    }

    public function store(): void
    {
        $ageGroup = $this->ageGroups->normalizeGroupKey($this->input('age_group'));

        $id = $this->model->create([
            'service_type_id' => $this->input('service_type_id'),
            'age_group' => $ageGroup,
            'session_date' => $this->input('session_date'),
            'session_time' => $this->input('session_time') ?: null,
            'title' => $this->input('title'),
            'notes' => $this->input('notes'),
            'created_by' => Auth::id(),
        ]);

        AuditLog::log('create', 'attendance', $id);
        $this->flash('success', 'Attendance session created.');
        $this->redirect('attendance/' . $id);
    }

    public function show(string $id): void
    {
        $session = $this->model->getSessionDetails((int) $id);
        if (!$session) {
            $this->redirect('attendance/adults');
        }

        $ageGroup = $this->ageGroups->normalizeGroupKey($session['age_group'] ?? 'adults');
        $general = $this->ageGroups->usesGeneralAttendance($ageGroup);

        $this->render('attendance/show', [
            'title' => 'Attendance Session',
            'session' => $session,
            'general' => $general,
            'attendees' => $general ? [] : $this->model->getSessionAttendees((int) $id),
            'members' => $general ? [] : (new Member())->getActiveForAgeGroup($ageGroup),
            'maleCount' => (int) ($session['male_count'] ?? 0),
            'femaleCount' => (int) ($session['female_count'] ?? 0),
            'ageGroupLabel' => $general && $ageGroup === 'adults'
                ? 'General (Adults)'
                : $this->ageGroups->getLabel($ageGroup),
            'listUrl' => $this->ageGroups->getAttendanceListUrl($ageGroup),
        ]);
    }

    public function saveCounts(string $id): void
    {
        $session = $this->model->getSessionDetails((int) $id);
        if (!$session) {
            $this->redirect('attendance/adults');
        }

        $ageGroup = $this->ageGroups->normalizeGroupKey($session['age_group'] ?? 'adults');
        if (!$this->ageGroups->usesGeneralAttendance($ageGroup)) {
            $this->flash('danger', 'This session uses individual member check-in.');
            $this->redirect('attendance/' . $id);
        }

        $male = max(0, (int) $this->input('male_count'));
        $female = max(0, (int) $this->input('female_count'));

        $this->model->updateGeneralCounts((int) $id, $male, $female);
        AuditLog::log('update', 'attendance', (int) $id, [
            'male_count' => $male,
            'female_count' => $female,
        ]);
        $this->flash('success', 'Attendance counts saved.');
        $this->redirect('attendance/' . $id);
    }

    public function checkin(string $id): void
    {
        $session = $this->model->getSessionDetails((int) $id);
        if ($session && $this->ageGroups->usesGeneralAttendance($session['age_group'] ?? '')) {
            $this->flash('warning', 'This session uses general headcount. Enter male and female counts instead.');
            $this->redirect('attendance/' . $id);
        }

        $memberId = (int) $this->input('member_id');
        if ($this->model->checkIn((int) $id, $memberId, 'manual')) {
            AuditLog::log('checkin', 'attendance', (int) $id, ['member_id' => $memberId]);
            $this->flash('success', 'Member checked in successfully.');
        } else {
            $this->flash('warning', 'Member already checked in or error occurred.');
        }
        $this->redirect('attendance/' . $id);
    }

    public function scan(): void
    {
        $ageGroup = $this->ageGroups->normalizeGroupKey($this->input('group'));

        if ($this->ageGroups->usesGeneralAttendance($ageGroup)) {
            $this->flash('info', 'QR check-in is only available for Children Service attendance.');
            $this->redirect($this->ageGroups->getAttendanceListUrl($ageGroup));
        }

        $this->render('attendance/scan', [
            'title' => 'QR Check-in',
            'ageGroup' => $ageGroup,
            'ageGroupLabel' => $this->ageGroups->getLabel($ageGroup),
            'listUrl' => $this->ageGroups->getAttendanceListUrl($ageGroup),
            'todaySessions' => $this->model->getTodaySessions($ageGroup),
        ]);
    }

    public function qrCheckin(): void
    {
        $qrCode = $this->input('qr_code');
        $sessionId = (int) $this->input('session_id');

        $session = $this->model->getSessionDetails($sessionId);
        if ($session && $this->ageGroups->usesGeneralAttendance($session['age_group'] ?? '')) {
            $this->json(['success' => false, 'message' => 'This session uses general headcount, not individual check-in.'], 400);
        }

        $member = (new Member())->findByQrCode($qrCode);
        if (!$member) {
            $this->json(['success' => false, 'message' => 'Invalid QR code.'], 404);
        }

        if ($this->model->checkIn($sessionId, $member['id'], 'qr_code')) {
            AuditLog::log('qr_checkin', 'attendance', $sessionId, ['member_id' => $member['id']]);
            $this->json([
                'success' => true,
                'message' => 'Welcome, ' . $member['first_name'] . '!',
                'member' => $member['first_name'] . ' ' . $member['last_name'],
            ]);
        }

        $this->json(['success' => false, 'message' => 'Already checked in.'], 400);
    }
}
