<?php

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Http\Controllers\Controller;
use App\Models\Dashboard;

class DashboardController extends Controller
{
    public function index(): void
    {
        $dashboard = new Dashboard();
        $data = $dashboard->getAdminOverview();
        $config = config('church');

        $this->render('dashboard/index', [
            'title' => 'Admin Dashboard',
            'user' => Auth::user(),
            'config' => $config,
            'members' => $data['members'],
            'finance' => $data['finance'],
            'attendance' => $data['attendance'],
            'operations' => $data['operations'],
            'charts' => $data['charts'],
            'alerts' => $data['alerts'],
            'recent' => $data['recent'],
            'lists' => $data['lists'],
        ]);
    }
}
