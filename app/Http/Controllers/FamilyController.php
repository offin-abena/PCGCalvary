<?php



namespace App\Http\Controllers;



use App\Core\AuditLog;

use App\Core\Auth;

use App\Http\Controllers\Controller;

use App\Core\Model;

use App\Services\GenerationalGroupService;



class FamilyController extends Controller

{

    private GenerationalGroupService $graduationService;



    public function __construct()

    {

        $this->graduationService = new GenerationalGroupService();

    }



    public function index(): void

    {

        $db = new class extends Model { protected string $table = 'families'; };

        $families = $db->query(

            "SELECT f.*, (SELECT COUNT(*) FROM members m WHERE m.family_id = f.id) as member_count,

             CONCAT(h.first_name, ' ', h.last_name) as head_name

             FROM families f

             LEFT JOIN members h ON f.head_member_id = h.id

             ORDER BY f.sort_order, f.family_name"

        );



        $this->render('families/index', [

            'title' => 'Generational Groups',

            'families' => $families,

            'rules' => $this->graduationService->getRules(),

        ]);

    }



    public function graduation(): void

    {

        $graduationYear = (int) $this->input('year', (int) date('Y'));

        $onlyThisYear = $this->input('only_this_year') === '1';



        $data = $this->graduationService->getGraduationCandidates($graduationYear, $onlyThisYear);



        $this->render('families/graduation', [

            'title' => 'Annual Group Graduation',

            'rules' => $this->graduationService->getRules(),

            'candidates' => $data,

            'recent' => $this->graduationService->getRecentGraduations(),

            'graduationYear' => $graduationYear,

            'onlyThisYear' => $onlyThisYear,

        ]);

    }



    public function processGraduation(): void

    {

        $graduationYear = (int) $this->input('graduation_year', (int) date('Y'));

        $memberIds = $this->input('member_ids', []);



        if (!is_array($memberIds) || empty($memberIds)) {

            $this->flash('warning', 'Select at least one member to graduate.');

            $this->redirect('families/graduation?year=' . $graduationYear);

        }



        $results = $this->graduationService->graduateMembers($memberIds, $graduationYear, Auth::id());



        if ($results['graduated'] > 0) {

            AuditLog::log('graduation', 'generational_graduations', $graduationYear);

            $this->flash('success', $results['graduated'] . ' member(s) graduated to their next group.');

        }



        if (!empty($results['errors'])) {

            $this->flash('warning', implode(' ', array_slice($results['errors'], 0, 3)));

        }



        if ($results['graduated'] === 0 && empty($results['errors'])) {

            $this->flash('info', 'No members were graduated.');

        }



        $query = http_build_query([

            'year' => $graduationYear,

            'only_this_year' => $this->input('only_this_year') === '1' ? '1' : '0',

        ]);

        $this->redirect('families/graduation?' . $query);

    }



    public function create(): void

    {

        $db = new class extends Model { protected string $table = 'families'; };

        $members = $db->query("SELECT id, first_name, last_name FROM members ORDER BY last_name");

        $this->render('families/create', ['title' => 'Add Generational Group', 'members' => $members]);

    }



    public function store(): void

    {

        $db = new class extends Model { protected string $table = 'families'; };

        $id = $db->create([

            'family_name' => $this->input('family_name'),

            'head_member_id' => $this->input('head_member_id') ?: null,

            'address' => $this->input('address'),

            'city' => $this->input('city'),

            'phone' => $this->input('phone'),

        ]);



        AuditLog::log('create', 'families', $id);

        $this->flash('success', 'Generational group created successfully.');

        $this->redirect('families');

    }

}

