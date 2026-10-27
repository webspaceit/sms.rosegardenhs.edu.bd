<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Permission;

/**
 * Guards a route behind the staff roles and permissions that may use it.
 */
final class Guard
{
    /** Roles allowed to reach the administration modules. */
    public const STAFF_ROLES = ['admin', 'superadmin'];

    public static function isStaff(): bool
    {
        return Auth::check() && in_array(Auth::user()['role'] ?? '', self::STAFF_ROLES, true);
    }

    /**
     * Stop the request unless a signed in administrator is behind it.
     */
    public static function requireAdmin(): void
    {
        if (self::isStaff()) {
            return;
        }
        if (Auth::check()) {
            Toast::flash('You do not have permission to access this area.', 'error');
            header('Location: ' . Url::to('/dashboard'));
            exit;
        }
        Toast::flash('Please sign in to continue.', 'error');
        header('Location: ' . Url::to('/login'));
        exit;
    }

    /**
     * Whether the signed in user holds a permission.
     *
     * The super admin always holds every permission. When no permissions have
     * been defined yet the check passes for any staff member, so a fresh
     * installation is never locked out.
     */
    public static function can(string $slug): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        $role = (string) ($user['role'] ?? '');
        if ($role === 'superadmin') {
            return true;
        }

        if (!self::permissionsAreConfigured()) {
            return self::isStaff();
        }

        $cache = $_SESSION['_permissions'] ?? [];
        if (!isset($cache[$role]) || !is_array($cache[$role])) {
            $cache[$role] = Permission::slugsForRole($role);
            $_SESSION['_permissions'] = $cache;
        }

        return in_array($slug, $cache[$role], true);
    }

    /**
     * Stop the request unless the signed in user holds the permission.
     */
    public static function requirePermission(string $slug): void
    {
        if (Auth::check() && self::can($slug)) {
            return;
        }
        if (Auth::check()) {
            Toast::flash('You do not have permission to access this area.', 'error');
            header('Location: ' . Url::to('/dashboard'));
            exit;
        }
        Toast::flash('Please sign in to continue.', 'error');
        header('Location: ' . Url::to('/login'));
        exit;
    }

    /** Whether at least one permission has been defined. */
    private static function permissionsAreConfigured(): bool
    {
        static $configured = null;

        if ($configured === null) {
            $configured = (int) Database::connection()->query('SELECT COUNT(*) FROM permissions')->fetchColumn() > 0;
        }

        return $configured;
    }
}
