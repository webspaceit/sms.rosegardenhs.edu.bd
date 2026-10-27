<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BranchContext;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;
use App\Models\Person;
use App\Models\PortalData;

/**
 * The teacher, student and parent portals.
 *
 * Which portal a person sees is decided by the role on their account and the
 * person record it is linked to.
 */
final class PortalController
{
    public function index(Request $request): void
    {
        $user = Auth::user();
        if ($user === null) {
            Toast::flash('Please sign in to continue.', 'error');
            header('Location: ' . Url::to('/login'));
            exit;
        }

        $route = match ($user['role'] ?? '') {
            'teacher' => '/portal/teacher',
            'student' => '/portal/student',
            'parent' => '/portal/parent',
            default => '/dashboard',
        };

        header('Location: ' . Url::to($route));
        exit;
    }

    /* ------------------------------------------------------------------
     * Student portal
     * --------------------------------------------------------------- */

    public function student(Request $request): void
    {
        $student = $this->requireStudent();
        $month = date('Y-m');

        View::render('portal/student', [
            'layout' => 'portal',
            'title' => 'My school',
            'student' => $student,
            'portalMenu' => $this->menu('student'),
            'attendance' => PortalData::studentAttendance((int) $student['id']),
            'monthAttendance' => PortalData::studentAttendance((int) $student['id'], date('Y-m-01')),
            'reports' => PortalData::studentReports((int) $student['id']),
            'fees' => PortalData::studentFees((int) $student['id']),
            'exams' => PortalData::availableExams((int) $student['id']),
            'routine' => PortalData::routineForClass((int) $student['class_id'], date('l')),
            'notices' => PortalData::latestNotices(),
        ]);
    }

    public function results(Request $request): void
    {
        $student = $this->requireStudent();
        $reportId = (int) $request->input('report', 0);

        $reports = PortalData::studentReports((int) $student['id']);
        $selected = null;
        foreach ($reports as $report) {
            if ((int) $report['id'] === $reportId) {
                $selected = $report;
            }
        }
        $selected ??= $reports[0] ?? null;

        View::render('portal/results', [
            'layout' => 'portal',
            'title' => 'My results',
            'student' => $student,
            'portalMenu' => $this->menu('student'),
            'reports' => $reports,
            'selected' => $selected,
            'marks' => $selected === null ? [] : PortalData::reportMarks((int) $selected['id']),
        ]);
    }

    public function attendance(Request $request): void
    {
        $student = $this->requireStudent();
        $month = (string) $request->input('month', date('Y-m'));

        View::render('portal/attendance', [
            'layout' => 'portal',
            'title' => 'My attendance',
            'student' => $student,
            'portalMenu' => $this->menu('student'),
            'month' => $month,
            'days' => PortalData::studentAttendanceDays((int) $student['id'], $month),
            'summary' => PortalData::studentAttendance((int) $student['id']),
            'monthSummary' => PortalData::studentAttendance((int) $student['id'], $month . '-01'),
        ]);
    }

    public function fees(Request $request): void
    {
        $student = $this->requireStudent();
        $invoices = PortalData::studentFees((int) $student['id']);

        $paid = 0.0;
        $due = 0.0;
        foreach ($invoices as $invoice) {
            if ($invoice['status'] === 'paid') {
                $paid += (float) $invoice['amount'];
            } else {
                $due += (float) $invoice['amount'];
            }
        }

        View::render('portal/fees', [
            'layout' => 'portal',
            'title' => 'My fees',
            'student' => $student,
            'portalMenu' => $this->menu('student'),
            'invoices' => $invoices,
            'paid' => $paid,
            'due' => $due,
        ]);
    }

    /* ------------------------------------------------------------------
     * Online examinations
     * --------------------------------------------------------------- */

    public function exams(Request $request): void
    {
        $student = $this->requireStudent();

        View::render('portal/exams', [
            'layout' => 'portal',
            'title' => 'Online examinations',
            'student' => $student,
            'portalMenu' => $this->menu('student'),
            'exams' => PortalData::availableExams((int) $student['id']),
        ]);
    }

    public function takeExam(Request $request): void
    {
        $student = $this->requireStudent();
        $exam = $this->onlineExam((int) $request->input('exam', 0));

        if ($exam === null) {
            Toast::flash('That examination is not available.', 'error');
            header('Location: ' . Url::to('/portal/exams'));
            exit;
        }

        $statement = Database::connection()->prepare(
            'SELECT * FROM exam_questions WHERE online_exam_id = :exam_id AND branch_id=:branch ORDER BY id'
        );
        $statement->execute(['exam_id' => (int) $exam['id'], 'branch' => BranchContext::id()]);

        $questions = $statement->fetchAll();
        $options = [];
        foreach ($questions as $question) {
            $options[$question['id']] = $question['options'] !== null
                ? (array) json_decode((string) $question['options'], true)
                : [];
        }

        $submission = $this->submission((int) $exam['id'], (int) $student['id']);

        View::render('portal/exam-take', [
            'layout' => 'portal',
            'title' => (string) $exam['title'],
            'student' => $student,
            'portalMenu' => $this->menu('student'),
            'exam' => $exam,
            'questions' => $questions,
            'options' => $options,
            'submission' => $submission,
        ]);
    }

    public function submitExam(Request $request): void
    {
        $student = $this->requireStudent();

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/portal/exams'), true, 419);
            exit;
        }

        $examId = (int) $request->input('exam', 0);
        $exam = $this->onlineExam($examId);
        if ($exam === null) {
            Toast::flash('That examination is not available.', 'error');
            header('Location: ' . Url::to('/portal/exams'));
            exit;
        }

        if ($this->submission($examId, (int) $student['id']) !== null) {
            Toast::flash('You have already sat this examination.', 'error');
            header('Location: ' . Url::to('/portal/exams'));
            exit;
        }

        $answers = $request->input('answer', []);
        $answers = is_array($answers) ? $answers : [];

        $statement = Database::connection()->prepare(
            'SELECT * FROM exam_questions WHERE online_exam_id = :exam_id AND branch_id=:branch'
        );
        $statement->execute(['exam_id' => $examId, 'branch' => BranchContext::id()]);
        $questions = $statement->fetchAll();

        // Only the multiple choice answers can be marked, a written answer is
        // left for a teacher to mark.
        $score = 0.0;
        $marks = 0.0;
        foreach ($questions as $question) {
            $marks += (float) $question['marks'];
            if ($question['type'] !== 'mcq') {
                continue;
            }
            $given = trim((string) ($answers[$question['id']] ?? ''));
            $correct = trim((string) ($question['correct_answer'] ?? ''));
            if ($given !== '' && strcasecmp($given, $correct) === 0) {
                $score += (float) $question['marks'];
            }
        }

        $db = Database::connection();
        $insert = $db->prepare(
            'INSERT INTO exam_submissions (online_exam_id, student_id, answers, score, started_at, submitted_at, created_at, branch_id)'
            . ' VALUES (:exam_id, :student_id, :answers, :score, NOW(), NOW(), NOW(), :branch)'
        );
        $insert->execute([
            'exam_id' => $examId,
            'student_id' => (int) $student['id'],
            'answers' => json_encode($answers),
            'score' => $score,
            'branch' => BranchContext::id(),
        ]);

        Toast::flash('Your answers were submitted. You scored ' . $score . ' of ' . $marks . '.');
        header('Location: ' . Url::to('/portal/exams'));
        exit;
    }

    /* ------------------------------------------------------------------
     * Teacher portal
     * --------------------------------------------------------------- */

    public function teacher(Request $request): void
    {
        $teacher = $this->requireTeacher();
        $classes = PortalData::teacherClasses((int) $teacher['id']);

        $classId = (int) $request->input('class_id', 0);
        if ($classId <= 0 && $classes !== []) {
            $classId = (int) $classes[0]['id'];
        }

        View::render('portal/teacher', [
            'layout' => 'portal',
            'title' => 'My teaching',
            'teacher' => $teacher,
            'portalMenu' => $this->menu('teacher'),
            'classes' => $classes,
            'classId' => $classId,
            'subjects' => $classId > 0 ? PortalData::teacherSubjects((int) $teacher['id'], $classId) : [],
            'students' => $classId > 0 ? PortalData::teacherStudents((int) $teacher['id'], $classId) : [],
            'routine' => PortalData::routineForTeacher((int) $teacher['id'], date('l')),
            'leaves' => PortalData::pendingLeaveRequests(),
            'notices' => PortalData::latestNotices(),
        ]);
    }

    /* ------------------------------------------------------------------
     * Parent portal
     * --------------------------------------------------------------- */

    public function parent(Request $request): void
    {
        $guardian = $this->requireGuardian();
        $children = Person::children((int) $guardian['id']);

        $childId = (int) $request->input('child', 0);
        $selected = null;
        foreach ($children as $child) {
            if ((int) $child['id'] === $childId) {
                $selected = $child;
            }
        }
        $selected ??= $children[0] ?? null;

        $attendance = $selected === null ? [] : PortalData::studentAttendance((int) $selected['id']);
        $fees = $selected === null ? [] : PortalData::studentFees((int) $selected['id']);
        $reports = $selected === null ? [] : PortalData::studentReports((int) $selected['id']);

        $due = 0.0;
        foreach ($fees as $invoice) {
            if ($invoice['status'] !== 'paid') {
                $due += (float) $invoice['amount'];
            }
        }

        View::render('portal/parent', [
            'layout' => 'portal',
            'title' => 'My children',
            'guardian' => $guardian,
            'portalMenu' => $this->menu('parent'),
            'children' => $children,
            'selected' => $selected,
            'attendance' => $attendance,
            'fees' => $fees,
            'reports' => $reports,
            'due' => $due,
            'notices' => PortalData::latestNotices(),
        ]);
    }

    /* ------------------------------------------------------------------
     * Helpers
     * --------------------------------------------------------------- */

    /** @return array<string, mixed> */
    private function requireStudent(): array
    {
        $student = Person::student();
        if ($student !== null) {
            return $student;
        }

        Toast::flash('This account is not linked to a student record yet. Ask the office to link it.', 'error');
        header('Location: ' . Url::to('/portal'));
        exit;
    }

    /** @return array<string, mixed> */
    private function requireTeacher(): array
    {
        $teacher = Person::teacher();
        if ($teacher !== null) {
            return $teacher;
        }

        Toast::flash('This account is not linked to a teacher record yet. Ask the office to link it.', 'error');
        header('Location: ' . Url::to('/portal'));
        exit;
    }

    /** @return array<string, mixed> */
    private function requireGuardian(): array
    {
        $guardian = Person::guardian();
        if ($guardian !== null) {
            return $guardian;
        }

        Toast::flash('This account is not linked to a guardian record yet. Ask the office to link it.', 'error');
        header('Location: ' . Url::to('/portal'));
        exit;
    }

    /** @return array<string, mixed>|null */
    private function onlineExam(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $statement = Database::connection()->prepare('SELECT * FROM online_exams WHERE id = :id AND status = "active" AND branch_id=:branch LIMIT 1');
        $statement->execute(['id' => $id, 'branch' => BranchContext::id()]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<string, mixed>|null */
    private function submission(int $examId, int $studentId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM exam_submissions WHERE online_exam_id = :exam_id AND student_id = :student_id AND branch_id=:branch LIMIT 1'
        );
        $statement->execute(['exam_id' => $examId, 'student_id' => $studentId, 'branch' => BranchContext::id()]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /**
     * The menu of a role's portal.
     *
     * @return array<int, array{title: string, href: string}>
     */
    private function menu(string $role): array
    {
        return match ($role) {
            'student' => [
                ['title' => 'Home', 'href' => '/portal/student'],
                ['title' => 'My attendance', 'href' => '/portal/attendance'],
                ['title' => 'My results', 'href' => '/portal/results'],
                ['title' => 'My fees', 'href' => '/portal/fees'],
                ['title' => 'Examinations', 'href' => '/portal/exams'],
            ],
            'teacher' => [
                ['title' => 'Home', 'href' => '/portal/teacher'],
                ['title' => 'Administration', 'href' => '/dashboard'],
            ],
            'parent' => [
                ['title' => 'My children', 'href' => '/portal/parent'],
            ],
            default => [],
        };
    }
}
