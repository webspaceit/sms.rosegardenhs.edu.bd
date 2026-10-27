<?php
declare(strict_types=1);

namespace App\Core;

/** Current campus context for a request; administrators may switch campuses. */
final class BranchContext
{
    public static function id(): int
    {
        $user = Auth::user();
        $canSwitch = $user !== null && in_array((string) ($user['role'] ?? ''), ['admin', 'superadmin'], true);
        if ($user !== null && !$canSwitch) {
            $assignedBranch = (int) ($user['branch_id'] ?? 0);
            return $assignedBranch > 0 && self::isActive($assignedBranch) ? $assignedBranch : 0;
        }
        $branchId = $canSwitch ? (int) ($_SESSION['active_branch_id'] ?? 0) : 0;
        if ($branchId > 0 && self::isActive($branchId)) return $branchId;
        $main = Database::connection()->query('SELECT id FROM branches WHERE status=\'active\' ORDER BY is_main DESC,id LIMIT 1')->fetchColumn();
        return max(1, (int) $main);
    }

    public static function activeBranches(): array
    {
        return Database::connection()->query('SELECT id,name,is_main FROM branches WHERE status=\'active\' ORDER BY is_main DESC,name')->fetchAll();
    }

    public static function canSwitch(): bool
    {
        return in_array(Auth::user()['role'] ?? '', ['admin', 'superadmin'], true)
            && Guard::can('branches.manage');
    }

    public static function isActive(int $id): bool
    {
        $statement = Database::connection()->prepare('SELECT 1 FROM branches WHERE id=:id AND status=\'active\' LIMIT 1');
        $statement->execute(['id' => $id]);
        return (bool) $statement->fetchColumn();
    }
}
