<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function check(): bool { return self::user() !== null; }
    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if (!$user || !(bool) $user['is_active'] || !password_verify($password, $user['password'])) {
            return false;
        }
        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
        return true;
    }
    public static function logout(): void { unset($_SESSION['user']); session_regenerate_id(true); }

    /**
     * Where a person lands after signing in.
     *
     * A teacher, a student or a parent goes to their own portal; everyone
     * else goes to the administration dashboard.
     */
    public static function homeRoute(): string
    {
        $role = self::user()['role'] ?? '';

        return match ($role) {
            'teacher', 'student', 'parent' => '/portal',
            default => '/dashboard',
        };
    }
}
