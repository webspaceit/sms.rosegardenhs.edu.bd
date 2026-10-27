<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BranchContext;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;

/**
 * Batch attendance.
 *
 * Picks a class, section and date, then marks every student in that class at
 * once. The form is prefilled with the records already saved for the date so
 * marking the same day twice does not duplicate anything.
 */
final class BatchAttendanceController
{
    private const STATUSES = ['present', 'absent', 'late', 'half_day', 'leave'];

    public function create(Request $request): void
    {
        Guard::requirePermission('attendance.manage');

        $classId = (int) $request->input('class_id', 0);
        $sectionId = (int) $request->input('section_id', 0);
        $date = (string) $request->input('date', date('Y-m-d'));

        View::render('attendance/batch', [
            'title' => 'Batch attendance',
            'user' => Auth::user(),
            'classes' => \App\Models\Catalogue::classes(),
            'sections' => \App\Models\Catalogue::sections(),
            'classId' => $classId,
            'sectionId' => $sectionId,
            'date' => $date !== '' ? $date : date('Y-m-d'),
            'students' => $this->students($classId, $sectionId),
            'existing' => $this->existing($classId, $sectionId, $date),
        ]);
    }

    public function store(Request $request): void
    {
        Guard::requirePermission('attendance.manage');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/batch-attendance'));
            exit;
        }

        $classId = (int) $request->input('class_id', 0);
        $sectionId = (int) $request->input('section_id', 0);
        $date = trim((string) $request->input('date', ''));
        $statuses = $request->input('status', []);
        $statuses = is_array($statuses) ? $statuses : [];

        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            Toast::flash('Choose a valid date before saving the attendance.', 'error');
            header('Location: ' . Url::to('/batch-attendance'));
            exit;
        }

        $students = $this->students($classId, $sectionId);
        if ($students === []) {
            Toast::flash('There are no students in the selected class and section.', 'error');
            header('Location: ' . Url::to('/batch-attendance'));
            exit;
        }

        $userId = (int) (Auth::user()['id'] ?? 0);

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $statement = $db->prepare(
                'INSERT INTO attendances (student_id, date, status, remarks, marked_by, branch_id)'
                . ' VALUES (:student_id, :date, :status, :remarks, :marked_by, :branch_id)'
                . ' ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), marked_by = VALUES(marked_by)'
            );

            $saved = 0;
            foreach ($students as $student) {
                $studentId = (int) $student['id'];
                $status = (string) ($statuses[$studentId] ?? 'present');
                if (!in_array($status, self::STATUSES, true)) {
                    $status = 'present';
                }
                $remarks = trim((string) ($_POST['remarks'][$studentId] ?? ''));

                $statement->execute([
                    'student_id' => $studentId,
                    'date' => $date,
                    'status' => $status,
                    'remarks' => $remarks !== '' ? $remarks : null,
                    'marked_by' => $userId > 0 ? $userId : null,
                    'branch_id' => BranchContext::id(),
                ]);
                $saved++;
            }

            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            \App\Core\ErrorLog::write($error);
            Toast::flash('The attendance could not be saved. Please try again.', 'error');
            header('Location: ' . Url::to('/batch-attendance'));
            exit;
        }

        Toast::flash('Attendance saved for ' . $saved . ' student(s) on ' . $date . '.');
        header('Location: ' . Url::to('/batch-attendance?class_id=' . $classId . '&section_id=' . $sectionId . '&date=' . urlencode($date)));
        exit;
    }

    /**
     * The students of a class and section.
     *
     * @return array<int, array<string, mixed>>
     */
    private function students(int $classId, int $sectionId): array
    {
        if ($classId <= 0) {
            return [];
        }

        $sql = 'SELECT id, name, student_id, roll_no FROM students WHERE class_id = :class_id AND branch_id=:branch';
        $bindings = ['class_id' => $classId, 'branch' => BranchContext::id()];

        if ($sectionId > 0) {
            $sql .= ' AND section_id = :section_id';
            $bindings['section_id'] = $sectionId;
        }

        $sql .= ' ORDER BY roll_no ASC';

        $statement = Database::connection()->prepare($sql);
        $statement->execute($bindings);
        return $statement->fetchAll();
    }

    /**
     * The attendance already recorded for a class, section and date.
     *
     * @return array<int, array<string, mixed>>
     */
    private function existing(int $classId, int $sectionId, string $date): array
    {
        if ($classId <= 0 || $date === '') {
            return [];
        }

        $sql = 'SELECT a.student_id, a.status, a.remarks FROM attendances a JOIN students s ON s.id = a.student_id AND s.branch_id=a.branch_id'
            . ' WHERE a.branch_id=:branch AND s.class_id = :class_id AND a.date = :date';
        $bindings = ['branch' => BranchContext::id(), 'class_id' => $classId, 'date' => $date];

        if ($sectionId > 0) {
            $sql .= ' AND s.section_id = :section_id';
            $bindings['section_id'] = $sectionId;
        }

        $statement = Database::connection()->prepare($sql);
        $statement->execute($bindings);

        $existing = [];
        foreach ($statement->fetchAll() as $row) {
            $existing[(int) $row['student_id']] = $row;
        }
        return $existing;
    }
}
