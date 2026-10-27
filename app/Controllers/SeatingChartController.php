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
use App\Models\Catalogue;

/**
 * The examination seating chart.
 *
 * Students are placed into halls and seats so that the invigilator knows
 * where everybody sits, and the chart can be printed and pinned up.
 */
final class SeatingChartController
{
    public function index(Request $request): void
    {
        Guard::requirePermission('exams.manage');

        $examId = (int) $request->input('exam', 0);
        $classId = (int) $request->input('class_id', 0);

        $exam = $this->exam($examId);
        $students = $classId > 0 ? $this->students($classId) : [];
        $seats = $examId > 0 ? $this->seats($examId) : [];

        View::render('results/seating-chart', [
            'title' => 'Seating chart',
            'user' => Auth::user(),
            'exams' => $this->examOptions(),
            'classes' => Catalogue::classes(),
            'exam' => $exam,
            'examId' => $examId,
            'classId' => $classId,
            'students' => $students,
            'seats' => $seats,
            'halls' => $this->halls($seats),
        ]);
    }

    /**
     * Build the seating plan for a class.
     */
    public function generate(Request $request): void
    {
        Guard::requirePermission('exams.manage');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/seating-chart'));
            exit;
        }

        $examId = (int) $request->input('exam', 0);
        $classId = (int) $request->input('class_id', 0);
        $columns = max(1, (int) $request->input('columns', 5));
        $rows = max(1, (int) $request->input('rows', 4));
        $hallName = trim((string) $request->input('hall', 'Main hall'));

        if ($this->exam($examId) === null || $classId <= 0) {
            Toast::flash('Choose an examination and a class first.', 'error');
            header('Location: ' . Url::to('/seating-chart'));
            exit;
        }

        $students = $this->students($classId);
        if ($students === []) {
            Toast::flash('There are no students in the selected class.', 'error');
            header('Location: ' . Url::to('/seating-chart?exam=' . $examId . '&class_id=' . $classId));
            exit;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $db->prepare('DELETE FROM exam_seats WHERE exam_id = :exam_id AND branch_id=:branch')->execute(['exam_id' => $examId, 'branch' => BranchContext::id()]);

            $insert = $db->prepare(
                'INSERT INTO exam_seats (exam_id, student_id, hall, seat_no, row_no, column_no, created_at, branch_id)'
                . ' VALUES (:exam_id, :student_id, :hall, :seat_no, :row_no, :column_no, NOW(), :branch)'
            );

            $perHall = $columns * $rows;
            $placed = 0;

            foreach ($students as $student) {
                $hallIndex = intdiv($placed, $perHall);
                $withinHall = $placed % $perHall;
                $row = intdiv($withinHall, $columns);
                $column = $withinHall % $columns;

                $insert->execute([
                    'exam_id' => $examId,
                    'student_id' => (int) $student['id'],
                    'hall' => $hallName . ' ' . chr(65 + $hallIndex),
                    'seat_no' => (string) ($withinHall + 1),
                    'row_no' => $row + 1,
                    'column_no' => $column + 1,
                    'branch' => BranchContext::id(),
                ]);
                $placed++;
            }

            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            \App\Core\ErrorLog::write($error);
            Toast::flash('The seating chart could not be built. Please try again.', 'error');
            header('Location: ' . Url::to('/seating-chart?exam=' . $examId . '&class_id=' . $classId));
            exit;
        }

        Toast::flash('Seating chart built for ' . $placed . ' student(s).');
        header('Location: ' . Url::to('/seating-chart?exam=' . $examId . '&class_id=' . $classId));
        exit;
    }

    /** Remove the plan of an examination. */
    public function clear(Request $request): void
    {
        Guard::requirePermission('exams.manage');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/seating-chart'));
            exit;
        }

        $examId = (int) $request->input('exam', 0);
        $statement = Database::connection()->prepare('DELETE FROM exam_seats WHERE exam_id = :exam_id AND branch_id=:branch');
        $statement->execute(['exam_id' => $examId, 'branch' => BranchContext::id()]);

        Toast::flash('The seating chart was cleared.');
        header('Location: ' . Url::to('/seating-chart?exam=' . $examId));
        exit;
    }

    /* ------------------------------------------------------------------
     * Helpers
     * --------------------------------------------------------------- */

    /** @return array<string, mixed>|null */
    private function exam(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $statement = Database::connection()->prepare('SELECT * FROM exams WHERE id = :id AND branch_id=:branch LIMIT 1');
        $statement->execute(['id' => $id, 'branch' => BranchContext::id()]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<int, string> */
    private function examOptions(): array
    {
        $rows = Database::connection()->query('SELECT id, name FROM exams WHERE branch_id=' . BranchContext::id() . ' ORDER BY start_date DESC, name')->fetchAll();
        $options = [];
        foreach ($rows as $row) {
            $options[(int) $row['id']] = (string) $row['name'];
        }
        return $options;
    }

    /**
     * The students of a class, in roll order.
     *
     * @return array<int, array<string, mixed>>
     */
    private function students(int $classId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, student_id, name, roll_no, section_id FROM students'
            . ' WHERE class_id = :class_id AND status = "active" AND branch_id=:branch ORDER BY roll_no'
        );
        $statement->execute(['class_id' => $classId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The seats already assigned for an examination.
     *
     * @return array<int, array<string, mixed>>
     */
    private function seats(int $examId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT es.*, s.name AS student_name, s.student_id, s.roll_no, sec.name AS section_name'
            . ' FROM exam_seats es JOIN students s ON s.id = es.student_id AND s.branch_id=es.branch_id'
            . ' LEFT JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id'
            . ' WHERE es.exam_id = :exam_id AND es.branch_id=:branch ORDER BY es.hall, es.row_no, es.column_no'
        );
        $statement->execute(['exam_id' => $examId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The seats grouped into halls.
     *
     * @param array<int, array<string, mixed>> $seats
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function halls(array $seats): array
    {
        $halls = [];
        foreach ($seats as $seat) {
            $halls[(string) $seat['hall']][] = $seat;
        }
        ksort($halls);
        return $halls;
    }
}
