<?php

namespace App\Core;

use Illuminate\Http\Exceptions\HttpResponseException;

class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT u.*, r.name as role_name, r.permissions 
            FROM users u JOIN roles r ON u.role_id = r.id 
            WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session()->regenerate();
            unset($user['password_hash']);
            session([
                'user' => $user,
                'permissions' => json_decode($user['permissions'], true) ?? [],
                'user_links' => \App\Services\ModuleAccess::loadUserLinks((int) $user['id']),
            ]);
            self::touchSession();

            $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
            AuditLog::log('login', 'auth', $user['id']);

            return true;
        }

        return false;
    }

    public static function check(): bool
    {
        if (!session()->has('user')) {
            return false;
        }

        if (self::isSessionExpired()) {
            self::logoutDueToInactivity();

            return false;
        }

        return true;
    }

    public static function user(): ?array
    {
        return session('user');
    }

    public static function id(): ?int
    {
        return session('user.id');
    }

    public static function logout(): void
    {
        if (session()->has('user')) {
            AuditLog::log('logout', 'auth', session('user.id'));
        }

        session()->invalidate();
        session()->regenerateToken();
    }

    public static function logoutDueToInactivity(): void
    {
        if (session()->has('user')) {
            AuditLog::log('logout', 'auth', session('user.id'), ['reason' => 'inactivity']);
        }

        session()->invalidate();
        session()->regenerateToken();
        session([
            'flash' => [
                'type' => 'warning',
                'message' => 'You were logged out after 5 minutes of inactivity.',
            ],
        ]);
    }

    public static function touchSession(): void
    {
        session(['last_activity' => time()]);
    }

    public static function isSessionExpired(): bool
    {
        if (!session()->has('last_activity')) {
            return false;
        }

        return (time() - (int) session('last_activity')) > self::sessionTimeoutSeconds();
    }

    public static function sessionTimeoutSeconds(): int
    {
        return max(60, (int) config('church.session_timeout', 300));
    }

    public static function can(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }

        $permissions = session('permissions', []);

        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    public static function requireAuth(): void
    {
        if (!session()->has('user')) {
            throw new HttpResponseException(redirect(church_url('login')));
        }

        if (self::isSessionExpired()) {
            self::logoutDueToInactivity();
            throw new HttpResponseException(redirect(church_url('login')));
        }

        self::touchSession();
    }

    public static function isSuperAdmin(): bool
    {
        if (!self::check()) {
            return false;
        }

        $permissions = session('permissions', []);

        return in_array('*', $permissions) || (session('user.role_name') ?? '') === 'Super Admin';
    }

    public static function requireSuperAdmin(): void
    {
        self::requireAuth();
        if (!self::isSuperAdmin()) {
            View::render('errors/403', ['title' => 'Access Denied']);
        }
    }

    public static function canForUser(string $permission, int $userId): bool
    {
        $perms = \App\Services\ModuleAccess::getRolePermissionsForUser($userId);

        return in_array('*', $perms) || in_array($permission, $perms);
    }

    public static function canManageFinancialYear(): bool
    {
        if (!self::check()) {
            return false;
        }

        if (self::isSuperAdmin()) {
            return true;
        }

        return (session('user.role_name') ?? '') === 'Pastor';
    }

    public static function requireFinancialYearManager(): void
    {
        self::requireAuth();
        if (!self::canManageFinancialYear()) {
            View::render('errors/403', ['title' => 'Access Denied']);
        }
    }
}
