<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BranchContext;

/**
 * Resolves a signed in account to the person it belongs to.
 *
 * An account carries a link type and a link id, so a teacher account points
 * at a teacher record, a student account at a student record, and a parent
 * account at a guardian record. The portals use this to work out whose
 * data to show.
 */
final class Person
{
    /** The student record behind the signed in account. */
    public static function student(?array $user = null): ?array
    {
        $user = $user ?? Auth::user();
        if ($user === null || ($user['link_type'] ?? null) !== 'student' || (int) ($user['link_id'] ?? 0) <= 0) {
            return null;
        }
        return self::linked('students', (int) $user['link_id']);
    }

    /** The teacher record behind the signed in account. */
    public static function teacher(?array $user = null): ?array
    {
        $user = $user ?? Auth::user();
        if ($user === null || ($user['link_type'] ?? null) !== 'teacher' || (int) ($user['link_id'] ?? 0) <= 0) {
            return null;
        }
        return self::linked('teachers', (int) $user['link_id']);
    }

    /** The guardian record behind the signed in account. */
    public static function guardian(?array $user = null): ?array
    {
        $user = $user ?? Auth::user();
        if ($user === null || ($user['link_type'] ?? null) !== 'guardian' || (int) ($user['link_id'] ?? 0) <= 0) {
            return null;
        }
        return self::linked('guardians', (int) $user['link_id']);
    }

    /**
     * The students a guardian account looks after.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function children(int $guardianId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.id, s.student_id, s.name, s.name_bn, s.roll_no, s.photo,'
            . ' c.name AS class_name, sec.name AS section_name, sg.is_primary'
            . ' FROM student_guardian sg'
            . ' JOIN students s ON s.id = sg.student_id AND s.branch_id=sg.branch_id'
            . ' JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id'
            . ' JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id'
            . ' WHERE sg.guardian_id = :guardian_id AND sg.branch_id=:branch'
            . ' ORDER BY sg.is_primary DESC, s.roll_no ASC'
        );
        $stmt->execute(['guardian_id' => $guardianId, 'branch' => BranchContext::id()]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    private static function linked(string $table, int $id): ?array
    {
        $allowed = ['students', 'teachers', 'guardians'];
        if (!in_array($table, $allowed, true)) {
            return null;
        }

        $stmt = Database::connection()->prepare('SELECT * FROM ' . $table . ' WHERE id = :id AND branch_id=:branch LIMIT 1');
        $stmt->execute(['id' => $id, 'branch' => BranchContext::id()]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<int, string> id => label */
    public static function studentOptions(): array
    {
        return Catalogue::students();
    }

    /** @return array<int, string> id => label */
    public static function teacherOptions(): array
    {
        return Catalogue::teachers();
    }

    /** @return array<int, string> id => label */
    public static function guardianOptions(): array
    {
        $rows = Database::connection()->query('SELECT id, name FROM guardians WHERE branch_id=' . BranchContext::id() . ' ORDER BY name')->fetchAll();
        $options = [];
        foreach ($rows as $row) {
            $options[(int) $row['id']] = (string) $row['name'] . ' · ' . (string) $row['phone'];
        }
        return $options;
    }
}
