<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Http\Controllers\Controller;
use App\Core\Database;
use App\Core\Model;
use App\Models\Finance;
use App\Models\FinancialYear;

class SettingsController extends Controller
{
    private function requireSettingsManager(): void
    {
        Auth::requireAuth();
        if (Auth::isSuperAdmin() || Auth::can('users') || Auth::canManageFinancialYear()) {
            return;
        }

        http_response_code(403);
        \App\Core\View::render('errors/403', ['title' => 'Access Denied']);
        exit;
    }

    public function index(): void
    {
        $config = config('church');
        $smsConfig = config('sms');
        $finance = new Finance();

        $financialYearModel = new FinancialYear();

        $this->render('settings/index', [
            'title' => 'Settings',
            'config' => $config,
            'smsConfig' => $smsConfig,
            'incomeTypes' => $finance->query("SELECT * FROM income_types ORDER BY is_active DESC, name"),
            'expenseTypes' => $finance->query("SELECT * FROM expense_types ORDER BY is_active DESC, name"),
            'duplicateIncomeTypeNames' => $finance->getDuplicateIncomeTypeNames(),
            'duplicateExpenseTypeNames' => $finance->getDuplicateExpenseTypeNames(),
            'financialYears' => $financialYearModel->getAll(),
            'currentFinancialYear' => $financialYearModel->getCurrent(),
            'canManageFinancialYear' => Auth::canManageFinancialYear(),
        ]);
    }

    public function storeFinancialYear(): void
    {
        Auth::requireFinancialYearManager();

        $errors = $this->validate([
            'name' => 'required',
            'year_label' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);

        if ($errors) {
            $this->flash('danger', reset($errors));
            $this->redirect('settings#financial-years');
        }

        $yearLabel = (int) $this->input('year_label');
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');

        if ($startDate > $endDate) {
            $this->flash('danger', 'Start date must be before end date.');
            $this->redirect('settings#financial-years');
        }

        $model = new FinancialYear();
        if ($model->findByYearLabel($yearLabel)) {
            $this->flash('danger', 'A financial year with that year label already exists.');
            $this->redirect('settings#financial-years');
        }

        $id = $model->createYear([
            'name' => trim($this->input('name')),
            'year_label' => $yearLabel,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'set_current' => $this->input('set_current') === '1',
        ], Auth::id());

        AuditLog::log('create', 'financial_years', $id);
        $this->flash('success', 'Financial year created successfully.');
        $this->redirect('settings#financial-years');
    }

    public function setCurrentFinancialYear(string $id): void
    {
        Auth::requireFinancialYearManager();

        try {
            (new FinancialYear())->setCurrent((int) $id);
            AuditLog::log('set_current', 'financial_years', (int) $id);
            $this->flash('success', 'Financial year set as current.');
        } catch (\RuntimeException $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('settings#financial-years');
    }

    public function closeFinancialYear(string $id): void
    {
        Auth::requireFinancialYearManager();

        try {
            (new FinancialYear())->closeYear((int) $id, Auth::id());
            AuditLog::log('close', 'financial_years', (int) $id);
            $this->flash('success', 'Financial year closed successfully.');
        } catch (\RuntimeException $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('settings#financial-years');
    }

    public function storeIncomeType(): void
    {
        $this->requireSettingsManager();
        $errors = $this->validate(['name' => 'required']);
        if ($errors) {
            $this->flash('danger', reset($errors));
            $this->redirect('settings#income-types');
        }

        $name = trim($this->input('name'));
        $finance = new Finance();
        if ($finance->incomeTypeNameExists($name)) {
            $this->flash('danger', "An income type named '{$name}' already exists.");
            $this->redirect('settings#income-types');
        }

        $db = new class extends Model { protected string $table = 'income_types'; };
        $id = $db->create([
            'name' => $name,
            'description' => trim($this->input('description', '')),
            'is_active' => 1,
        ]);

        AuditLog::log('create', 'income_types', $id);
        $this->flash('success', 'Income type added successfully.');
        $this->redirect('settings#income-types');
    }

    public function storeExpenseType(): void
    {
        $this->requireSettingsManager();
        $errors = $this->validate(['name' => 'required']);
        if ($errors) {
            $this->flash('danger', reset($errors));
            $this->redirect('settings#expense-types');
        }

        $name = trim($this->input('name'));
        $finance = new Finance();
        if ($finance->expenseTypeNameExists($name)) {
            $this->flash('danger', "An expense type named '{$name}' already exists.");
            $this->redirect('settings#expense-types');
        }

        $db = new class extends Model { protected string $table = 'expense_types'; };
        $id = $db->create([
            'name' => $name,
            'description' => trim($this->input('description', '')),
            'is_active' => 1,
        ]);

        AuditLog::log('create', 'expense_types', $id);
        $this->flash('success', 'Expense type added successfully.');
        $this->redirect('settings#expense-types');
    }

    public function updateIncomeType(string $id): void
    {
        $this->requireSettingsManager();
        $db = new class extends Model { protected string $table = 'income_types'; };
        $type = $db->find((int) $id);
        if (!$type) {
            $this->redirect('settings#income-types');
        }

        $name = trim($this->input('name', $type['name']));
        if ($name === '') {
            $this->flash('danger', 'Name is required.');
            $this->redirect('settings#income-types');
        }

        $finance = new Finance();
        if ($finance->incomeTypeNameExists($name, (int) $id)) {
            $this->flash('danger', "An income type named '{$name}' already exists.");
            $this->redirect('settings#income-types');
        }

        $db->update((int) $id, [
            'name' => $name,
            'description' => trim($this->input('description', '')),
        ]);

        AuditLog::log('update', 'income_types', (int) $id);
        $this->flash('success', 'Income type updated.');
        $this->redirect('settings#income-types');
    }

    public function updateExpenseType(string $id): void
    {
        $this->requireSettingsManager();
        $db = new class extends Model { protected string $table = 'expense_types'; };
        $type = $db->find((int) $id);
        if (!$type) {
            $this->redirect('settings#expense-types');
        }

        $name = trim($this->input('name', $type['name']));
        if ($name === '') {
            $this->flash('danger', 'Name is required.');
            $this->redirect('settings#expense-types');
        }

        $finance = new Finance();
        if ($finance->expenseTypeNameExists($name, (int) $id)) {
            $this->flash('danger', "An expense type named '{$name}' already exists.");
            $this->redirect('settings#expense-types');
        }

        $db->update((int) $id, [
            'name' => $name,
            'description' => trim($this->input('description', '')),
        ]);

        AuditLog::log('update', 'expense_types', (int) $id);
        $this->flash('success', 'Expense type updated.');
        $this->redirect('settings#expense-types');
    }

    public function toggleIncomeType(string $id): void
    {
        $this->requireSettingsManager();
        $db = new class extends Model { protected string $table = 'income_types'; };
        $type = $db->find((int) $id);
        if ($type) {
            $db->update((int) $id, ['is_active' => $type['is_active'] ? 0 : 1]);
            AuditLog::log('toggle', 'income_types', (int) $id);
            $this->flash('success', 'Income type ' . ($type['is_active'] ? 'deactivated' : 'activated') . '.');
        }
        $this->redirect('settings#income-types');
    }

    public function toggleExpenseType(string $id): void
    {
        $this->requireSettingsManager();
        $db = new class extends Model { protected string $table = 'expense_types'; };
        $type = $db->find((int) $id);
        if ($type) {
            $db->update((int) $id, ['is_active' => $type['is_active'] ? 0 : 1]);
            AuditLog::log('toggle', 'expense_types', (int) $id);
            $this->flash('success', 'Expense type ' . ($type['is_active'] ? 'deactivated' : 'activated') . '.');
        }
        $this->redirect('settings#expense-types');
    }

    public function changePassword(): void
    {
        Auth::requireAuth();

        $errors = $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required',
        ]);

        if ($errors) {
            $this->flash('danger', reset($errors));
            $this->redirect('settings#change-password');
        }

        if ($this->input('new_password') !== $this->input('confirm_password')) {
            $this->flash('danger', 'New password and confirmation do not match.');
            $this->redirect('settings#change-password');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($this->input('current_password'), $user['password_hash'])) {
            $this->flash('danger', 'Current password is incorrect.');
            $this->redirect('settings#change-password');
        }

        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([
            password_hash($this->input('new_password'), PASSWORD_DEFAULT),
            Auth::id(),
        ]);

        AuditLog::log('change_password', 'auth', Auth::id());

        Auth::logout();
        session_start();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password changed successfully. Please sign in with your new password.'];
        header('Location: ' . url('login'));
        exit;
    }
}
