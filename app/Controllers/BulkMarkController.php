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
 * Mark entry for a whole class at once.
 *
 * Entering the marks of forty students one screen at a time is slow, so this
 * screen puts the whole class on one page. The teacher can type a single
 * total per subject, or the written, MCQ, practical and continuous
 * assessment marks separately.
 */
final class BulkMarkController
{
    public function index(Request $request): void
    {
        Guard::requirePermission('reports.view');

        $classId = (int) $request->input('class_id', 0);
        $examName = trim((string) $request->input('exam', ''));
        $academicYear = trim((string) $request->input('year', ''));
        $mode = (string) $request->input('mode', 'total') === 'breakdown' ? 'breakdown' : 'total';

        $students = $this->students($classId);
        $subjects = $this->subjects($classId);
        $marks = $this->marks($classId, $examName, $academicYear);

        View::render('results/bulk-marks', [
            'title' => 'Mark entry',
            'user' => Auth::user(),
            'classes' => Catalogue::classes(),
            'classId' => $classId,
            'examName' => $examName,
            'academicYear' => $academicYear,
            'mode' => $mode,
            'students' => $students,
            'subjects' => $subjects,
            'marks' => $marks,
            'totals' => $this->totals($marks),
        ]);
    }

    /**
     * Save the marks of the whole class.
     */
    public function store(Request $request): void
    {
        Guard::requirePermission('reports.view');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/marks'));
            exit;
        }

        $classId = (int) $request->input('class_id', 0);
        $examName = trim((string) $request->input('exam', ''));
        $academicYear = trim((string) $request->input('year', ''));
        $mode = (string) $request->input('mode', 'total') === 'breakdown' ? 'breakdown' : 'total';
        $input = $request->input('marks', []);
        $input = is_array($input) ? $input : [];

        if ($classId <= 0 || $examName === '' || $academicYear === '') {
            Toast::flash('A class, an exam and an academic year are all required.', 'error');
            header('Location: ' . Url::to('/marks'));
            exit;
        }

        $examId = $this->examId($examName, $academicYear);
        if ($examId <= 0) {
            Toast::flash('Select an exam configured for the chosen academic year.', 'error');
            header('Location: ' . Url::to('/marks'));
            exit;
        }

        $students = $this->students($classId);
        $subjects = $this->subjects($classId);

        if ($students === [] || $subjects === []) {
            Toast::flash('The selected class has no students or no subjects.', 'error');
            header('Location: ' . Url::to('/marks'));
            exit;
        }

        $db = Database::connection();
        $db->beginTransaction();
        $saved = 0;

        try {
            foreach ($students as $student) {
                $studentId = (int) $student['id'];
                $reportId = $this->reportId($studentId, $examName, $academicYear);

                if ($reportId === 0) {
                    $insert = $db->prepare(
                        'INSERT INTO academic_reports (student_id, exam_id, working_days, present_days, absent_days, created_at, branch_id)'
                        . ' VALUES (:student_id, :exam_id, 0, 0, 0, NOW(), :branch)'
                    );
                    $insert->execute([
                        'student_id' => $studentId,
                        'exam_id' => $examId,
                        'branch' => BranchContext::id(),
                    ]);
                    $reportId = (int) $db->lastInsertId();
                }

                foreach ($subjects as $subject) {
                    $subjectId = (int) $subject['id'];
                    $values = $input[$studentId][$subjectId] ?? [];
                    $values = is_array($values) ? $values : [];

                    $written = (int) ($values['written_marks'] ?? 0);
                    $mcq = (int) ($values['mcq_marks'] ?? 0);
                    $practical = (int) ($values['practical_marks'] ?? 0);
                    $ca = (int) ($values['ca_marks'] ?? 0);
                    $obtained = (int) ($values['obtained'] ?? 0);

                    // In the single total mode the four parts stay at zero and
                    // the figure the teacher typed is kept on its own.
                    if ($mode === 'total') {
                        $written = 0;
                        $mcq = 0;
                        $practical = 0;
                        $ca = 0;
                    } else {
                        $obtained = 0;
                    }

                    $full = (int) ($values['full_marks'] ?? $subject['full_marks'] ?? 100);

                    $find = $db->prepare(
                        'SELECT id FROM academic_report_marks WHERE academic_report_id = :report_id AND subject_id = :subject_id AND branch_id=:branch LIMIT 1'
                    );
                    $find->execute(['report_id' => $reportId, 'subject_id' => $subjectId, 'branch' => BranchContext::id()]);
                    $markId = $find->fetchColumn();

                    if ($markId === false) {
                        $insert = $db->prepare(
                            'INSERT INTO academic_report_marks (academic_report_id, subject_id, full_marks, highest_marks,'
                            . ' written_marks, mcq_marks, practical_marks, ca_marks, obtained, created_at, branch_id)'
                            . ' VALUES (:report_id, :subject_id, :full_marks, :highest_marks, :written_marks, :mcq_marks,'
                            . ' :practical_marks, :ca_marks, :obtained, NOW(), :branch)'
                        );
                        $insert->execute([
                            'report_id' => $reportId,
                            'subject_id' => $subjectId,
                            'full_marks' => $full,
                            'highest_marks' => (int) ($values['highest_marks'] ?? 0),
                            'written_marks' => $written,
                            'mcq_marks' => $mcq,
                            'practical_marks' => $practical,
                            'ca_marks' => $ca,
                            'obtained' => $obtained,
                            'branch' => BranchContext::id(),
                        ]);
                    } else {
                        $update = $db->prepare(
                            'UPDATE academic_report_marks SET full_marks = :full_marks, highest_marks = :highest_marks,'
                            . ' written_marks = :written_marks, mcq_marks = :mcq_marks, practical_marks = :practical_marks,'
                            . ' ca_marks = :ca_marks, obtained = :obtained WHERE id = :id AND branch_id=:branch'
                        );
                        $update->execute([
                            'id' => (int) $markId,
                            'full_marks' => $full,
                            'highest_marks' => (int) ($values['highest_marks'] ?? 0),
                            'written_marks' => $written,
                            'mcq_marks' => $mcq,
                            'practical_marks' => $practical,
                            'ca_marks' => $ca,
                            'obtained' => $obtained,
                            'branch' => BranchContext::id(),
                        ]);
                    }
                    $saved++;
                }
            }

            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            \App\Core\ErrorLog::write($error);
            Toast::flash('The marks could not be saved. Please try again.', 'error');
            header('Location: ' . Url::to('/marks'));
            exit;
        }

        Toast::flash('Saved ' . $saved . ' mark(s) for ' . count($students) . ' student(s).');
        header('Location: ' . Url::to('/marks?class_id=' . $classId . '&exam=' . urlencode($examName) . '&year=' . urlencode($academicYear) . '&mode=' . $mode));
        exit;
    }

    /**
     * The printable mark sheet of a class.
     */
    public function sheet(Request $request): void
    {
        Guard::requirePermission('reports.view');

        $classId = (int) $request->input('class_id', 0);
        $examName = trim((string) $request->input('exam', ''));
        $academicYear = trim((string) $request->input('year', ''));

        $students = $this->students($classId);
        $subjects = $this->subjects($classId);
        $marks = $this->marks($classId, $examName, $academicYear);

        $rows = [];
        foreach ($students as $student) {
            $studentId = (int) $student['id'];
            $total = 0;
            $row = [];
            foreach ($subjects as $subject) {
                $mark = $marks[$studentId][(int) $subject['id']] ?? null;
                $obtained = $mark === null
                    ? 0
                    : (int) $mark['written_marks'] + (int) $mark['mcq_marks'] + (int) $mark['practical_marks']
                        + (int) $mark['ca_marks'] + (int) ($mark['obtained'] ?? 0);
                $row[(int) $subject['id']] = $obtained;
                $total += $obtained;
            }
            $rows[] = ['student' => $student, 'marks' => $row, 'total' => $total];
        }

        View::render('results/mark-sheet', [
            'title' => 'Mark sheet',
            'user' => Auth::user(),
            'classId' => $classId,
            'className' => $this->className($classId),
            'examName' => $examName,
            'academicYear' => $academicYear,
            'subjects' => $subjects,
            'rows' => $rows,
        ]);
    }

    /* ------------------------------------------------------------------
     * Helpers
     * --------------------------------------------------------------- */

    /**
     * @return array<int, array<string, mixed>>
     */
    private function students(int $classId): array
    {
        if ($classId <= 0) {
            return [];
        }
        $statement = Database::connection()->prepare(
            'SELECT id, student_id, name, roll_no FROM students WHERE class_id = :class_id AND branch_id=:branch ORDER BY roll_no'
        );
        $statement->execute(['class_id' => $classId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The subjects the class sits, with the marks each one is out of.
     *
     * @return array<int, array<string, mixed>>
     */
    private function subjects(int $classId): array
    {
        if ($classId <= 0) {
            return [];
        }
        $statement = Database::connection()->prepare(
            'SELECT s.id, s.name, cs.full_marks FROM class_subject cs JOIN subjects s ON s.id = cs.subject_id AND s.branch_id=cs.branch_id'
            . ' WHERE cs.class_id = :class_id AND cs.branch_id=:branch ORDER BY cs.sort_order, s.name'
        );
        $statement->execute(['class_id' => $classId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The marks already entered for the class.
     *
     * @return array<int, array<string, array<string, mixed>>>
     */
    private function marks(int $classId, string $examName, string $academicYear): array
    {
        if ($classId <= 0 || $examName === '' || $academicYear === '') {
            return [];
        }

        $examId = $this->examId($examName, $academicYear);
        if ($examId <= 0) return [];

        $statement = Database::connection()->prepare(
            'SELECT ar.student_id, m.*, sub.name AS subject_name FROM academic_reports ar'
            . ' JOIN academic_report_marks m ON m.academic_report_id = ar.id AND m.branch_id=ar.branch_id'
            . ' JOIN students st ON st.id = ar.student_id AND st.branch_id=ar.branch_id'
            . ' LEFT JOIN subjects sub ON sub.id = m.subject_id AND sub.branch_id = m.branch_id'
            . ' WHERE ar.branch_id=:branch AND ar.exam_id = :exam_id AND st.class_id = :class_id'
        );
        $statement->execute([
            'exam_id' => $examId,
            'class_id' => $classId,
            'branch' => BranchContext::id(),
        ]);

        $marks = [];
        foreach ($statement->fetchAll() as $row) {
            $marks[(int) $row['student_id']][(int) $row['subject_id']] = $row;
        }
        return $marks;
    }

    /**
     * The running total of each student.
     *
     * @param array<int, array<string, array<string, mixed>>> $marks
     * @return array<int, int>
     */
    private function totals(array $marks): array
    {
        $totals = [];
        foreach ($marks as $studentId => $studentMarks) {
            $total = 0;
            foreach ($studentMarks as $mark) {
                $total += (int) $mark['written_marks'] + (int) $mark['mcq_marks']
                    + (int) $mark['practical_marks'] + (int) $mark['ca_marks'] + (int) ($mark['obtained'] ?? 0);
            }
            $totals[$studentId] = $total;
        }
        return $totals;
    }

    private function reportId(int $studentId, string $examName, string $academicYear): int
    {
        $examId = $this->examId($examName, $academicYear);
        $statement = Database::connection()->prepare(
            'SELECT id FROM academic_reports WHERE student_id = :student_id AND branch_id=:branch AND exam_id = :exam_id LIMIT 1'
        );
        $statement->execute([
            'student_id' => $studentId,
            'exam_id' => $examId,
            'branch' => BranchContext::id(),
        ]);
        $id = $statement->fetchColumn();
        return $id === false ? 0 : (int) $id;
    }

    private function examId(string $examName, string $academicYear): int
    {
        $statement = Database::connection()->prepare('SELECT e.id FROM exams e LEFT JOIN academic_years ay ON ay.id = e.academic_year_id AND ay.branch_id=e.branch_id WHERE e.name = :name AND e.branch_id=:branch AND (e.academic_year_id IS NULL OR ay.name = :year) ORDER BY (e.academic_year_id IS NULL), e.id LIMIT 1');
        $statement->execute(['name' => $examName, 'year' => $academicYear, 'branch' => BranchContext::id()]);
        return (int) ($statement->fetchColumn() ?: 0);
    }

    private function className(int $classId): string
    {
        if ($classId <= 0) {
            return '';
        }
        $statement = Database::connection()->prepare('SELECT name FROM classes WHERE id = :id AND branch_id=:branch LIMIT 1');
        $statement->execute(['id' => $classId, 'branch' => BranchContext::id()]);
        $name = $statement->fetchColumn();
        return $name === false ? '' : (string) $name;
    }
}
