<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

class ModuleAccess
{
    public static function allModules(): array
    {
        return require config_path('modules.php');
    }

    public static function loadUserLinks(int $userId): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT link_key, is_enabled FROM user_link_access WHERE user_id = ?');
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();

            $links = [];
            foreach ($rows as $row) {
                $links[$row['link_key']] = (bool) $row['is_enabled'];
            }
            return $links;
        } catch (\PDOException $e) {
            return [];
        }
    }

    public static function hasExplicitAssignments(int $userId): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT COUNT(*) FROM user_link_access WHERE user_id = ?');
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public static function canAccess(string $linkKey): bool
    {
        if (!Auth::check()) {
            return false;
        }

        if (Auth::isSuperAdmin()) {
            return true;
        }

        $userLinks = session('user_links', []);

        if (!empty($userLinks)) {
            if (($userLinks[$linkKey] ?? false) === true) {
                return true;
            }

            // Legacy: a single Finance assignment grants all finance sub-modules
            if (str_starts_with($linkKey, 'finance_') && ($userLinks['finance'] ?? false) === true) {
                return true;
            }

            // Members sub-menu inherits from main Members link
            if (str_starts_with($linkKey, 'members_') && ($userLinks['members'] ?? false) === true) {
                return true;
            }

            // Attendance sub-menu inherits from main Attendance link
            if (str_starts_with($linkKey, 'attendance_') && ($userLinks['attendance'] ?? false) === true) {
                return true;
            }

            return false;
        }

        $module = self::findModule($linkKey);
        if (!$module) {
            return false;
        }

        if ($module['permission'] === null) {
            return in_array($linkKey, ['dashboard', 'settings']);
        }

        return Auth::can($module['permission']);
    }

    public static function getAccessibleModules(): array
    {
        $modules = self::allModules();
        $grouped = [];

        foreach ($modules as $module) {
            if (!empty($module['parent'])) {
                continue;
            }

            if (!self::canAccess($module['key'])) {
                continue;
            }

            $section = $module['section'];
            if (!isset($grouped[$section])) {
                $grouped[$section] = [];
            }

            $module['children'] = self::getAccessibleChildren($module['key']);
            $grouped[$section][] = $module;
        }

        return $grouped;
    }

    public static function getAccessibleChildren(string $parentKey): array
    {
        $children = [];

        foreach (self::allModules() as $module) {
            if (($module['parent'] ?? null) !== $parentKey) {
                continue;
            }

            if (!self::canAccess($module['key'])) {
                continue;
            }

            $children[] = $module;
        }

        return $children;
    }

    public static function findModule(string $key): ?array
    {
        foreach (self::allModules() as $module) {
            if ($module['key'] === $key) {
                return $module;
            }
        }
        return null;
    }

    public static function saveUserLinks(int $userId, array $enabledKeys, int $assignedBy): void
    {
        $db = Database::getInstance();
        $db->prepare('DELETE FROM user_link_access WHERE user_id = ?')->execute([$userId]);

        $stmt = $db->prepare(
            'INSERT INTO user_link_access (user_id, link_key, is_enabled, assigned_by) VALUES (?, ?, ?, ?)'
        );

        foreach (self::allModules() as $module) {
            $stmt->execute([
                $userId,
                $module['key'],
                in_array($module['key'], $enabledKeys) ? 1 : 0,
                $assignedBy,
            ]);
        }
    }

    public static function getUserLinkStates(int $userId): array
    {
        $explicit = self::loadUserLinks($userId);
        $states = [];

        foreach (self::allModules() as $module) {
            if (!empty($explicit)) {
                $states[$module['key']] = $explicit[$module['key']] ?? false;
            } else {
                $states[$module['key']] = $module['permission'] === null
                    ? in_array($module['key'], ['dashboard', 'settings'])
                    : Auth::canForUser($module['permission'], $userId);
            }
        }

        return $states;
    }

    public static function getRolePermissionsForUser(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT r.permissions FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return json_decode($row['permissions'] ?? '[]', true) ?? [];
    }

    public static function resolveLinkKeyFromUrl(string $url): ?string
    {
        $url = trim($url, '/');

        if ($url === '' || str_starts_with($url, 'dashboard')) {
            return 'dashboard';
        }

        $segment = explode('/', $url)[0];

        $aliases = [
            'audit-logs' => 'users',
            'members/adults' => 'members_adults',
            'members/junior-youth' => 'members_junior_youth',
            'members/children-service' => 'members_children_service',
        ];

        if (isset($aliases[$segment])) {
            return $aliases[$segment];
        }

        $financeRoutes = [
            'finance/income/export' => 'finance_income',
            'finance/income/import' => 'finance_income',
            'finance/income/template' => 'finance_income',
            'finance/income' => 'finance_income',
            'finance/expense/export' => 'finance_expense',
            'finance/expense/import' => 'finance_expense',
            'finance/expense/template' => 'finance_expense',
            'finance/expense' => 'finance_expense',
            'finance/assets' => 'finance_assets',
            'finance/liabilities' => 'finance_liabilities',
            'finance/receipt' => 'finance_income',
            'finance/tithe-reminders' => 'finance_income',
            'finance/sms-logs' => 'finance_income',
        ];

        foreach ($financeRoutes as $path => $key) {
            if ($url === $path || str_starts_with($url, $path . '/')) {
                return $key;
            }
        }

        $memberRoutes = [
            'members/adults' => 'members_adults',
            'members/junior-youth' => 'members_junior_youth',
            'members/children-service' => 'members_children_service',
        ];

        foreach ($memberRoutes as $path => $key) {
            if ($url === $path || str_starts_with($url, $path . '/')) {
                return $key;
            }
        }

        $attendanceRoutes = [
            'attendance/adults' => 'attendance_adults',
            'attendance/children-service' => 'attendance_children_service',
            'attendance/junior-youth' => 'attendance_junior_youth',
        ];

        foreach ($attendanceRoutes as $path => $key) {
            if ($url === $path || str_starts_with($url, $path . '/')) {
                return $key;
            }
        }

        if ($url === 'finance' || str_starts_with($url, 'finance/')) {
            return 'finance';
        }

        foreach (self::allModules() as $module) {
            if ($module['url'] === $segment || str_starts_with($url, $module['url'] . '/')) {
                return $module['key'];
            }
        }

        return null;
    }

    public static function isNavLinkActive(array $link, string $currentUrl): bool
    {
        $currentUrl = trim($currentUrl, '/');

        if ($link['key'] === 'dashboard') {
            return $currentUrl === '' || str_starts_with($currentUrl, 'dashboard');
        }

        if ($link['key'] === 'finance') {
            if ($currentUrl === 'finance') {
                return true;
            }
            if (!str_starts_with($currentUrl, 'finance/')) {
                return false;
            }

            $dedicatedPrefixes = [
                'finance/income',
                'finance/expense',
                'finance/assets',
                'finance/liabilities',
                'finance/receipt',
                'finance/tithe-reminders',
                'finance/sms-logs',
                'finance/income/export',
                'finance/expense/export',
            ];

            foreach ($dedicatedPrefixes as $prefix) {
                if ($currentUrl === $prefix || str_starts_with($currentUrl, $prefix . '/')) {
                    return false;
                }
            }

            return true;
        }

        if ($link['key'] === 'members') {
            $memberSubRoutes = [
                'members/adults',
                'members/junior-youth',
                'members/children-service',
            ];

            foreach ($memberSubRoutes as $route) {
                if ($currentUrl === $route || str_starts_with($currentUrl, $route . '/')) {
                    return true;
                }
            }

            return false;
        }

        if ($link['key'] === 'attendance') {
            return $currentUrl !== 'attendance' && str_starts_with($currentUrl, 'attendance/');
        }

        if (!empty($link['parent']) && $link['parent'] === 'members') {
            return $currentUrl === $link['url'] || str_starts_with($currentUrl, $link['url'] . '/');
        }

        if (!empty($link['parent']) && $link['parent'] === 'attendance') {
            return $currentUrl === $link['url'] || str_starts_with($currentUrl, $link['url'] . '/');
        }

        return $currentUrl === $link['url'] || str_starts_with($currentUrl, $link['url'] . '/');
    }
}
