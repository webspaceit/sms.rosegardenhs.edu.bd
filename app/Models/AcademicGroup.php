<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BranchContext;
use App\Core\Database;
use PDO;

final class AcademicGroup
{
    public static function all(): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ag.id, ag.name, COUNT(s.id) AS student_count '
            . 'FROM academic_groups ag LEFT JOIN students s '
            . 'ON s.academic_group = ag.name AND s.branch_id = ag.branch_id '
            . 'WHERE ag.branch_id = :branch GROUP BY ag.id, ag.name ORDER BY ag.name'
        );
        $stmt->execute(['branch' => BranchContext::id()]);
        return $stmt->fetchAll();
    }

    /** @return array<int, string> */
    public static function names(): array
    {
        $stmt = Database::connection()->prepare('SELECT name FROM academic_groups WHERE branch_id = :branch ORDER BY name');
        $stmt->execute(['branch' => BranchContext::id()]);
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function exists(string $name): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM academic_groups WHERE branch_id = :branch AND name = :name LIMIT 1');
        $stmt->execute(['branch' => BranchContext::id(), 'name' => $name]);
        return (bool) $stmt->fetchColumn();
    }

    public static function create(string $name): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO academic_groups (name, branch_id) VALUES (:name, :branch)');
        $stmt->execute(['name' => $name, 'branch' => BranchContext::id()]);
    }

    public static function rename(int $id, string $name): bool
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $find = $db->prepare('SELECT name FROM academic_groups WHERE id = :id AND branch_id = :branch FOR UPDATE');
            $find->execute(['id' => $id, 'branch' => BranchContext::id()]);
            $oldName = $find->fetchColumn();
            if ($oldName === false) {
                $db->rollBack();
                return false;
            }
            $updateStudents = $db->prepare('UPDATE students SET academic_group = :new_name WHERE branch_id = :branch AND academic_group = :old_name');
            $updateStudents->execute(['new_name' => $name, 'branch' => BranchContext::id(), 'old_name' => $oldName]);
            $update = $db->prepare('UPDATE academic_groups SET name = :name, updated_at = NOW() WHERE id = :id AND branch_id = :branch');
            $update->execute(['name' => $name, 'id' => $id, 'branch' => BranchContext::id()]);
            $db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
        }
    }

    public static function delete(int $id): string
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT name FROM academic_groups WHERE id = :id AND branch_id = :branch LIMIT 1');
        $stmt->execute(['id' => $id, 'branch' => BranchContext::id()]);
        $name = $stmt->fetchColumn();
        if ($name === false) return 'missing';

        $used = $db->prepare('SELECT COUNT(*) FROM students WHERE branch_id = :branch AND academic_group = :name');
        $used->execute(['branch' => BranchContext::id(), 'name' => $name]);
        if ((int) $used->fetchColumn() > 0) return 'in_use';

        $remove = $db->prepare('DELETE FROM academic_groups WHERE id = :id AND branch_id = :branch');
        $remove->execute(['id' => $id, 'branch' => BranchContext::id()]);
        return $remove->rowCount() > 0 ? 'deleted' : 'missing';
    }
}
