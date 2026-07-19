<?php



namespace App\Http\Controllers;



use App\Core\AuditLog;

use App\Http\Controllers\Controller;

use App\Models\Member;

use App\Services\AgeGroupService;



class MemberController extends Controller

{

    private Member $model;



    public function __construct()

    {

        $this->model = new Member();

    }



    public function index(): void

    {

        $this->redirect('members/adults');

    }



    public function adults(): void

    {

        $this->listMembers('Adults', 'adults');

    }



    public function juniorYouth(): void

    {

        $this->listMembers('Junior Youth', 'junior_youth');

    }



    public function childrenService(): void

    {

        $this->listMembers('Children Service', 'children_service');

    }



    private function listMembers(string $title, ?string $ageGroup = null): void

    {

        $page = (int) ($this->input('page', 1));

        $filters = [

            'search' => $this->input('search'),

            'status' => $this->input('status'),

            'gender' => $this->input('gender'),

            'family_id' => $this->input('family_id'),

            'age_group' => $ageGroup,

        ];



        $this->render('members/index', [

            'title' => $title,

            'members' => $this->model->search($filters, $page),

            'filters' => $filters,

            'families' => $this->model->getGenerationalFamilies(),

            'ageGroup' => $ageGroup,

        ]);

    }



    public function create(): void

    {

        $families = $this->model->getGenerationalFamilies();

        $this->render('members/create', ['title' => 'Add Member', 'families' => $families]);

    }



    public function store(): void

    {

        $errors = $this->validate([

            'first_name' => 'required',

            'last_name' => 'required',

        ]);



        if ($errors) {

            $_SESSION['old'] = $_POST;

            $this->flash('danger', reset($errors));

            $this->redirect('members/create');

        }



        $photo = $this->uploadFile('photo', 'members');

        $data = $this->collectMemberData(true);

        if ($photo) {

            $data['photo'] = $photo;

        }

        $data['family_id'] = $this->model->resolveGenerationalFamilyId($data);



        $id = $this->model->create($data);



        AuditLog::log('create', 'members', $id);

        $this->flash('success', 'Member registered successfully.');

        $this->redirect('members/' . $id);

    }



    public function show(string $id): void

    {

        $member = $this->model->getWithFamily((int) $id);

        if (!$member) {

            $this->flash('danger', 'Member not found.');

            $this->redirect((new \App\Services\AgeGroupService())->getListUrlForMember(null, null));

        }



        $attendance = $this->model->query(

            "SELECT ar.*, s.session_date, st.name as service_name 

             FROM attendance_records ar 

             JOIN attendance_sessions s ON ar.session_id = s.id 

             JOIN service_types st ON s.service_type_id = st.id 

             WHERE ar.member_id = ? ORDER BY ar.check_in_time DESC LIMIT 10",

            [$id]

        );



        $donations = $this->model->query(

            "SELECT * FROM financial_transactions WHERE member_id = ? AND transaction_type = 'income' ORDER BY transaction_date DESC LIMIT 10",

            [$id]

        );



        $this->render('members/show', [

            'title' => $member['first_name'] . ' ' . $member['last_name'],

            'member' => $member,

            'attendance' => $attendance,

            'donations' => $donations,

            'backUrl' => (new \App\Services\AgeGroupService())->getListUrlForMember(
                $member['date_of_birth'] ?? null,
                $member['family_name'] ?? null
            ),

        ]);

    }



    public function edit(string $id): void

    {

        $member = $this->model->find((int) $id);

        if (!$member) {

            $this->redirect((new \App\Services\AgeGroupService())->getListUrlForMember(null, null));

        }



        $families = $this->model->getGenerationalFamilies();

        $this->render('members/edit', ['title' => 'Edit Member', 'member' => $member, 'families' => $families]);

    }



    public function update(string $id): void

    {

        $errors = $this->validate([

            'first_name' => 'required',

            'last_name' => 'required',

        ]);



        if ($errors) {

            $_SESSION['old'] = $_POST;

            $this->flash('danger', reset($errors));

            $this->redirect('members/' . $id . '/edit');

        }



        $photo = $this->uploadFile('photo', 'members');

        $existing = $this->model->getWithFamily((int) $id);

        $data = $this->collectMemberData(false);

        if ($photo) {

            $data['photo'] = $photo;

        }

        $previousFamilyName = $existing['family_name'] ?? null;
        $data['family_id'] = $this->model->resolveGenerationalFamilyId($data, $existing);

        $this->model->update((int) $id, $data);

        $ageService = new \App\Services\AgeGroupService();
        $newFamily = $data['family_id']
            ? $this->model->queryOne('SELECT family_name FROM families WHERE id = ?', [$data['family_id']])
            : null;
        $movedToJuniorYouth = $ageService->matchesFamilyGroup($previousFamilyName, 'children_service')
            && $ageService->matchesFamilyGroup($newFamily['family_name'] ?? null, 'junior_youth');

        AuditLog::log('update', 'members', (int) $id);

        if (!empty($movedToJuniorYouth)) {
            $this->flash('success', 'Member updated and moved to Junior Youth.');
        } else {
            $this->flash('success', 'Member updated successfully.');
        }

        $this->redirect('members/' . $id);

    }



    public function delete(string $id): void

    {

        $this->model->delete((int) $id);

        AuditLog::log('delete', 'members', (int) $id);

        $this->flash('success', 'Member deleted.');

        $this->redirect('members/adults');

    }



    private function collectMemberData(bool $isCreate): array

    {

        $data = [

            'membership_id' => trim($this->input('membership_id', '')) ?: null,

            'first_name' => trim($this->input('first_name')),

            'last_name' => trim($this->input('last_name')),

            'middle_name' => trim($this->input('middle_name', '')) ?: null,

            'gender' => $this->input('gender', 'male'),

            'date_of_birth' => $this->nullableDate('date_of_birth'),

            'email' => trim($this->input('email', '')) ?: null,

            'phone' => trim($this->input('phone', '')) ?: null,

            'address' => trim($this->input('address', '')) ?: null,

            'city' => trim($this->input('city', '')) ?: null,

            'hometown' => trim($this->input('hometown', '')) ?: null,

            'languages' => trim($this->input('languages', '')) ?: null,

            'nationality' => trim($this->input('nationality', '')) ?: null,

            'occupation' => trim($this->input('occupation', '')) ?: null,

            'profession' => trim($this->input('profession', '')) ?: null,

            'marital_status' => $this->input('marital_status', 'single'),

            'marriage_type' => trim($this->input('marriage_type', '')) ?: null,

            'marriage_date' => $this->nullableDate('marriage_date'),

            'marriage_officiating_minister' => trim($this->input('marriage_officiating_minister', '')) ?: null,

            'family_member' => trim($this->input('family_member', '')) ?: null,

            'current_church_position' => trim($this->input('current_church_position', '')) ?: null,

            'previous_church_position' => trim($this->input('previous_church_position', '')) ?: null,

            'years_in_church' => trim($this->input('years_in_church', '')) ?: null,

            'family_id' => $this->input('family_id') ? (int) $this->input('family_id') : null,

            'membership_status' => $this->input('membership_status', 'active'),

            'registration_date' => trim($this->input('registration_date', '')) ?: null,

            'spouse_name' => trim($this->input('spouse_name', '')) ?: null,

            'spouse_dob' => $this->nullableDate('spouse_dob'),

            'spouse_contact_no' => trim($this->input('spouse_contact_no', '')) ?: null,

            'spouse_hometown' => trim($this->input('spouse_hometown', '')) ?: null,

            'spouse_place_worship' => trim($this->input('spouse_place_worship', '')) ?: null,

            'biological_children' => trim($this->input('biological_children', '')) ?: null,

            'biological_children_name' => trim($this->input('biological_children_name', '')) ?: null,

            'biological_children_dob' => trim($this->input('biological_children_dob', '')) ?: null,

            'currently_schooling' => trim($this->input('currently_schooling', '')) ?: null,

            'school_name' => trim($this->input('school_name', '')) ?: null,

            'completion_date' => $this->nullableDate('completion_date'),

            'educational_level' => trim($this->input('educational_level', '')) ?: null,

            'qualification' => trim($this->input('qualification', '')) ?: null,

            'baptism_status' => trim($this->input('baptism_status', '')) ?: null,

            'baptism_date' => $this->nullableDate('baptism_date'),

            'baptism_place' => trim($this->input('baptism_place', '')) ?: null,

            'baptism_minister' => trim($this->input('baptism_minister', '')) ?: null,

            'confirmation_status' => trim($this->input('confirmation_status', '')) ?: null,

            'confirmation_date' => $this->nullableDate('confirmation_date'),

            'confirmation_place' => trim($this->input('confirmation_place', '')) ?: null,

            'confirmation_minister' => trim($this->input('confirmation_minister', '')) ?: null,

            'emergency_contact_name' => trim($this->input('emergency_contact_name', '')) ?: null,

            'emergency_contact_phone' => trim($this->input('emergency_contact_phone', '')) ?: null,

            'emergency_contact_relation' => trim($this->input('emergency_contact_relation', '')) ?: null,

            'next_of_kin_residence' => trim($this->input('next_of_kin_residence', '')) ?: null,

            'biological_father_name' => trim($this->input('biological_father_name', '')) ?: null,

            'biological_father_status' => trim($this->input('biological_father_status', '')) ?: null,

            'biological_mother_name' => trim($this->input('biological_mother_name', '')) ?: null,

            'biological_mother_status' => trim($this->input('biological_mother_status', '')) ?: null,

            'notes' => trim($this->input('notes', '')) ?: null,

        ];



        if ($isCreate) {

            $data['member_code'] = generate_member_code();

            $data['qr_code'] = generate_qr_code();

        }



        return $data;

    }



    private function nullableDate(string $field): ?string

    {

        $value = trim($this->input($field, ''));

        return $value !== '' ? $value : null;

    }

}

