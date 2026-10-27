<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BranchContext;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Core\View;
use App\Models\Catalogue;

/**
 * The monthly attendance register.
 *
 * One sheet for a class and a month: every student down the side, every school
 * day across the top, and the attendance percentage of each student at the
 * end.
 */
final class AttendanceRegisterController
{
    public function index(Request $request): void
    {
        Guard::requirePermission('attendance.manage');

        $classId = (int) $request->input('class_id', 0);
        $sectionId = (int) $request->input('section_id', 0);
        $month = (string) $request->input('month', date('Y-m'));

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        [$students, $marks] = $this->sheet($classId, $sectionId, $month);
        $days = $this->daysInMonth($month);

        View::render('attendance/register', [
            'title' => 'Attendance register',
            'user' => Auth::user(),
            'classes' => Catalogue::classes(),
            'sections' => Catalogue::sections(),
            'classId' => $classId,
            'sectionId' => $sectionId,
            'month' => $month,
            'days' => $days,
            'students' => $students,
            'marks' => $marks,
        ]);
    }

    /**
     * The students of the class and the marks recorded against each day.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<int, string>>}
     */
    private function sheet(int $classId, int $sectionId, string $month): array
    {
        if ($classId <= 0) {
            return [[], []];
        }

        $sql = 'SELECT id, student_id, name, roll_no FROM students WHERE class_id = :class_id AND branch_id=:branch';
        $bindings = ['class_id' => $classId, 'branch' => BranchContext::id()];
        if ($sectionId > 0) {
            $sql .= ' AND section_id = :section_id';
            $bindings['section_id'] = $sectionId;
        }
        $sql .= ' ORDER BY roll_no';

        $statement = Database::connection()->prepare($sql);
        $statement->execute($bindings);
        $students = $statement->fetchAll();

        if ($students === []) {
            return [[], []];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $students);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $marks = Database::connection()->prepare(
            'SELECT student_id, date, status FROM attendances'
            . ' WHERE branch_id=? AND student_id IN (' . $placeholders . ') AND DATE_FORMAT(date, "%Y-%m") = ?'
        );
        $marks->execute(array_merge([BranchContext::id()], $ids, [$month]));

        $byStudent = [];
        foreach ($marks->fetchAll() as $row) {
            $byStudent[(int) $row['student_id']][(int) substr((string) $row['date'], 8, 2)] = (string) $row['status'];
        }

        return [$students, $byStudent];
    }

    /**
     * The weekdays of a month, so the sheet only asks about school days.
     *
     * @return array<int, bool>
     */
    private function daysInMonth(string $month): array
    {
        $first = new \DateTimeImmutable($month . '-01');
        $days = [];
        $cursor = $first;

        while ($cursor->format('Y-m') === $month) {
            $weekday = (int) $cursor->format('N');
            $days[(int) $cursor->format('j')] = $weekday <= 5;
            $cursor = $cursor->modify('+1 day');
        }

        return $days;
    }
}
