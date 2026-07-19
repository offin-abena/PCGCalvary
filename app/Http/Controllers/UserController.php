<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Http\Controllers\Controller;
use App\Core\Model;
use App\Services\ModuleAccess;

class UserController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = new class extends Model { protected string $table = 'users'; };
        $users = $db->query(
            "SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC"
        );
        $roles = $db->query("SELECT * FROM roles ORDER BY name");

        $this->render('users/index', [
            'title' => 'User Management',
            'users' => $users,
            'roles' => $roles,
            'isSuperAdmin' => Auth::isSuperAdmin(),
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        if (!Auth::can('users') && !Auth::isSuperAdmin()) {
            $this->redirect('dashboard');
        }

        $db = new class extends Model { protected string $table = 'users'; };
        $roles = $db->query("SELECT * FROM roles ORDER BY name");
        $members = $db->query("SELECT id, first_name, last_name FROM members ORDER BY last_name");

        $this->render('users/create', ['title' => 'Create User', 'roles' => $roles, 'members' => $members]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        if (!Auth::can('users') && !Auth::isSuperAdmin()) {
            $this->redirect('dashboard');
        }

        $errors = $this->validate([
            'username' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($errors) {
            $this->flash('danger', reset($errors));
            $this->redirect('users/create');
        }

        $db = new class extends Model { protected string $table = 'users'; };
        $id = $db->create([
            'role_id' => $this->input('role_id'),
            'member_id' => $this->input('member_id') ?: null,
            'username' => $this->input('username'),
            'email' => $this->input('email'),
            'password_hash' => password_hash($this->input('password'), PASSWORD_DEFAULT),
            'is_active' => 1,
        ]);

        AuditLog::log('create', 'users', $id);
        $this->flash('success', 'User created successfully.');
        $this->redirect('users');
    }

    public function assignLinks(string $id): void
    {
        Auth::requireSuperAdmin();

        $db = new class extends Model { protected string $table = 'users'; };
        $user = $db->queryOne(
            "SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?",
            [$id]
        );

        if (!$user) {
            $this->redirect('users');
        }

        $this->render('users/assign-links', [
            'title' => 'Assign Links — ' . $user['username'],
            'user' => $user,
            'modules' => ModuleAccess::allModules(),
            'linkStates' => ModuleAccess::getUserLinkStates((int) $id),
            'hasExplicit' => ModuleAccess::hasExplicitAssignments((int) $id),
        ]);
    }

    public function saveLinks(string $id): void
    {
        Auth::requireSuperAdmin();

        $db = new class extends Model { protected string $table = 'users'; };
        $user = $db->find((int) $id);

        if (!$user) {
            $this->redirect('users');
        }

        $enabledKeys = $this->input('links') ?? [];
        if (!is_array($enabledKeys)) {
            $enabledKeys = [];
        }

        // Dashboard always enabled
        if (!in_array('dashboard', $enabledKeys)) {
            $enabledKeys[] = 'dashboard';
        }

        ModuleAccess::saveUserLinks((int) $id, $enabledKeys, Auth::id());

        AuditLog::log('assign_links', 'users', (int) $id, ['links' => $enabledKeys]);
        $this->flash('success', 'Module links assigned for ' . $user['username'] . '. They must sign in again for changes to take effect.');
        $this->redirect('users');
    }

    public function toggleStatus(string $id): void
    {
        Auth::requireSuperAdmin();

        if ((int) $id === Auth::id()) {
            $this->flash('danger', 'You cannot deactivate your own account.');
            $this->redirect('users');
        }

        $db = new class extends Model { protected string $table = 'users'; };
        $user = $db->find((int) $id);

        if ($user) {
            $db->update((int) $id, ['is_active' => $user['is_active'] ? 0 : 1]);
            AuditLog::log('toggle_status', 'users', (int) $id);
            $this->flash('success', 'User ' . ($user['is_active'] ? 'deactivated' : 'activated') . '.');
        }

        $this->redirect('users');
    }

    public function auditLogs(): void
    {
        $db = new class extends Model { protected string $table = 'audit_logs'; };
        $logs = $db->query(
            "SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 100"
        );

        $this->render('users/audit', ['title' => 'Audit Logs', 'logs' => $logs]);
    }
}
