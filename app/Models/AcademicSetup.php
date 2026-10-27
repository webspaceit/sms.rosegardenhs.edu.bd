<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\BranchContext;

final class AcademicSetup
{
    public static function years(): array
    {
        return Database::connection()->query('SELECT id, name, start_date, end_date, is_current FROM academic_years WHERE branch_id=' . BranchContext::id() . ' ORDER BY start_date DESC')->fetchAll();
    }

    public static function classes(): array
    {
        return Database::connection()->query("SELECT c.id, c.name, c.numeric_level, c.shift, COUNT(s.id) AS section_count, GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ', ') AS section_names FROM classes c LEFT JOIN sections s ON s.class_id = c.id AND s.branch_id=c.branch_id WHERE c.branch_id=" . BranchContext::id() . " GROUP BY c.id, c.name, c.numeric_level, c.shift ORDER BY c.numeric_level")->fetchAll();
    }

    public static function createYear(string $name, string $startDate, string $endDate): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO academic_years (name, start_date, end_date, is_current, branch_id) VALUES (:name, :start_date, :end_date, 0, :branch)');
        $stmt->execute(['name' => $name, 'start_date' => $startDate, 'end_date' => $endDate, 'branch' => BranchContext::id()]);
    }

    public static function setCurrentYear(int $id): bool
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $exists = $db->prepare('SELECT id FROM academic_years WHERE id = :id AND branch_id=:branch LIMIT 1');
            $exists->execute(['id' => $id, 'branch' => BranchContext::id()]);
            if (!$exists->fetchColumn()) {
                $db->rollBack();
                return false;
            }
            $reset = $db->prepare('UPDATE academic_years SET is_current = 0 WHERE branch_id=:branch');
            $reset->execute(['branch' => BranchContext::id()]);
            $current = $db->prepare('UPDATE academic_years SET is_current = 1 WHERE id = :id AND branch_id=:branch');
            $current->execute(['id' => $id, 'branch' => BranchContext::id()]);
            $db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $error;
        }
    }

    public static function createClass(string $name, int $level, string $shift): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO classes (name, numeric_level, shift, branch_id) VALUES (:name, :level, :shift, :branch)');
        $stmt->execute(['name' => $name, 'level' => $level, 'shift' => $shift, 'branch' => BranchContext::id()]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function createSection(int $classId, string $name, ?string $room): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO sections (class_id, name, room_no, branch_id) VALUES (:class_id, :name, :room, :branch)');
        $stmt->execute(['class_id' => $classId, 'name' => $name, 'room' => $room, 'branch' => BranchContext::id()]);
    }
}
