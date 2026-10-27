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
use App\Models\ReportConfig;
use App\Models\ResultSet;

/**
 * The result screens: report cards, tabulation sheets, merit lists, section
 * results, fail lists and admit cards.
 *
 * All six read the same marks and share the grading engine in ResultSet.
 */
final class ResultController
{
    /* ------------------------------------------------------------------
     * Report cards
     * --------------------------------------------------------------- */

    public function reports(Request $request): void
    {
        Guard::requirePermission('reports.view');

        $examName = trim((string) $request->input('exam', ''));
        $academicYear = trim((string) $request->input('year', ''));
        $classId = (int) $request->input('class_id', 0);
        $section = trim((string) $request->input('section', ''));

        $results = $this->results($classId, $section, $examName, $academicYear);

        View::render('results/reports', [
            'title' => 'Report cards',
            'user' => Auth::user(),
            'classes' => Catalogue::classes(),
            'sections' => Catalogue::sections(),
            'classId' => $classId,
            'section' => $section,
            'examName' => $examName,
            'academicYear' => $academicYear,
            'results' => $results,
            'exams' => $this->examNames(),
            'years' => $this->yearNames(),
        ]);
    }

    public function reportForm(Request $request): void
    {
        Guard::requirePermission('reports.view');

        $studentId = (int) $request->input('student_id', 0);
        $examName = trim((string) $request->input('exam', ''));
        $academicYear = trim((string) $request->input('year', ''));

        $student = Database::connection()
            ->query('SELECT s.*, c.name AS class_name, sec.name AS section_name FROM students s'
                . ' JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id WHERE s.id = ' . $studentId . ' AND s.branch_id=' . BranchContext::id())
            ->fetch();

        if ($student === false) {
            Toast::flash('Student record not found.', 'error');
            header('Location: ' . Url::to('/reports'));
            exit;
        }

        $report = $this->findReport($studentId, $examName, $academicYear);

        View::render('results/report-form', [
            'title' => 'Edit report card',
            'user' => Auth::user(),
            'student' => $student,
            'report' => $report,
            'marks' => $report === null ? [] : $this->marks((int) $report['id']),
            'examName' => $examName,
            'academicYear' => $academicYear,
            'subjects' => $this->classSubjects((int) $student['class_id']),
        ]);
    }

    public function reportSave(Request $request): void
    {
        Guard::requirePermission('reports.view');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/reports'));
            exit;
        }

        $studentId = (int) $request->input('student_id', 0);
        $examName = trim((string) $request->input('exam', ''));
        $academicYear = trim((string) $request->input('year', ''));

        if ($studentId <= 0 || $examName === '' || $academicYear === '') {
            Toast::flash('A student, an exam and an academic year are all required.', 'error');
            header('Location: ' . Url::to('/reports'));
            exit;
        }

        $examId = $this->examId($examName, $academicYear);
        if ($examId <= 0) {
            Toast::flash('Select an exam configured for the chosen academic year.', 'error');
            header('Location: ' . Url::to('/reports'));
            exit;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $reportId = $this->findReportId($studentId, $examName, $academicYear);

            $fields = [
                'student_id' => $studentId,
                'exam_id' => $examId,
                'class_position' => (int) $request->input('class_position', 0) ?: null,
                'working_days' => (int) $request->input('working_days', 0),
                'present_days' => (int) $request->input('present_days', 0),
                'absent_days' => (int) $request->input('absent_days', 0),
                'moral_evaluation' => trim((string) $request->input('moral_evaluation', '')) ?: null,
                'co_curricular' => trim((string) $request->input('co_curricular', '')) ?: null,
                'comments' => trim((string) $request->input('comments', '')) ?: null,
                'branch_id' => BranchContext::id(),
            ];

            if ($reportId > 0) {
                $assignments = implode(', ', array_map(static fn (string $c): string => $c . ' = :' . $c, array_keys($fields)));
                unset($fields['branch_id']);
                $statement = $db->prepare('UPDATE academic_reports SET ' . $assignments . ' WHERE id = :id AND branch_id=:branch');
                $statement->execute($fields + ['id' => $reportId, 'branch' => BranchContext::id()]);
            } else {
                $columns = array_keys($fields);
                $statement = $db->prepare(
                    'INSERT INTO academic_reports (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')'
                );
                $statement->execute($fields);
                $reportId = (int) $db->lastInsertId();
            }

            $marks = $request->input('marks', []);
            $marks = is_array($marks) ? $marks : [];

            $delete = $db->prepare('DELETE FROM academic_report_marks WHERE academic_report_id = :report_id AND branch_id=:branch');
            $delete->execute(['report_id' => $reportId, 'branch' => BranchContext::id()]);

            $insert = $db->prepare(
                    'INSERT INTO academic_report_marks (academic_report_id, subject_id, full_marks, highest_marks,'
                . ' written_marks, mcq_marks, practical_marks, ca_marks, obtained, branch_id)'
                . ' VALUES (:report_id, :subject_id, :full_marks, :highest_marks, :written_marks, :mcq_marks, :practical_marks, :ca_marks, :obtained, :branch)'
            );

            foreach ($marks as $subjectId => $values) {
                $values = is_array($values) ? $values : [];
                $insert->execute([
                    'report_id' => $reportId,
                    'subject_id' => (int) $subjectId,
                    'full_marks' => (int) ($values['full_marks'] ?? 100),
                    'highest_marks' => (int) ($values['highest_marks'] ?? 0),
                    'written_marks' => (int) ($values['written_marks'] ?? 0),
                    'mcq_marks' => (int) ($values['mcq_marks'] ?? 0),
                    'practical_marks' => (int) ($values['practical_marks'] ?? 0),
                    'ca_marks' => (int) ($values['ca_marks'] ?? 0),
                    'obtained' => (int) ($values['obtained'] ?? 0),
                    'branch' => BranchContext::id(),
                ]);
            }

            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            \App\Core\ErrorLog::write($error);
            Toast::flash('The report card could not be saved. Please try again.', 'error');
            header('Location: ' . Url::to('/reports'));
            exit;
        }

        Toast::flash('Report card saved.');
        header('Location: ' . Url::to('/reports?exam=' . urlencode($examName) . '&year=' . urlencode($academicYear) . '&class_id=' . (int) $request->input('class_id', 0)));
        exit;
    }

    /* ------------------------------------------------------------------
     * The configured result screens
     * --------------------------------------------------------------- */

    public function tabulationSheets(Request $request): void
    {
        $this->configuredScreen($request, 'tabulation', 'Tabulation sheet', 'results/tabulation');
    }

    public function meritLists(Request $request): void
    {
        $this->configuredScreen($request, 'merit-list', 'Merit list', 'results/merit');
    }

    public function sectionResults(Request $request): void
    {
        $this->configuredScreen($request, 'section-result', 'Section result', 'results/section-result');
    }

    public function failLists(Request $request): void
    {
        $this->configuredScreen($request, 'fail-list', 'Fail list', 'results/fail-list');
    }

    public function admitCards(Request $request): void
    {
        Guard::requirePermission('reports.view');

        $years = \App\Models\Student::academicYears();
        $yearId = max(0, (int) $request->input('year_id', 0));
        if ($yearId === 0) {
            foreach ($years as $year) {
                if ((int) $year['is_current'] === 1) {
                    $yearId = (int) $year['id'];
                    break;
                }
            }
        }
        $classId = max(0, (int) $request->input('class_id', 0));
        $search = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;
        $total = \App\Models\Student::countAdmitCards($yearId, $classId, $search);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($totalPages, max(1, (int) $request->input('page', 1)));
        $settings = [];
        foreach (Database::connection()->query('SELECT `key`, value FROM school_settings WHERE branch_id=' . BranchContext::id())->fetchAll() as $setting) {
            $settings[(string) $setting['key']] = (string) ($setting['value'] ?? '');
        }

        View::render('results/admit-card-list', [
            'title' => 'Admit cards',
            'user' => Auth::user(),
            'students' => \App\Models\Student::admitCardListing($yearId, $classId, $search, $perPage, ($page - 1) * $perPage),
            'years' => $years,
            'yearId' => $yearId,
            'classes' => Catalogue::classes(),
            'classId' => $classId,
            'search' => $search,
            'perPage' => $perPage,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'schoolSettings' => $settings,
        ]);
    }

    public function printAdmitCard(Request $request): void
    {
        Guard::requirePermission('reports.view');
        $student = \App\Models\Student::admitCardDetails((int) $request->input('id', 0));
        if ($student === null) {
            Toast::flash('Student record not found.', 'error');
            header('Location: ' . Url::to('/admit-cards'));
            exit;
        }

        $statement = Database::connection()->prepare(
            'SELECT e.name AS exam_name, s.name AS subject_name, es.date, es.start_time, es.end_time, es.hall'
            . ' FROM exam_schedules es JOIN exams e ON e.id = es.exam_id AND e.branch_id=es.branch_id JOIN subjects s ON s.id = es.subject_id AND s.branch_id=es.branch_id'
            . ' WHERE es.branch_id=:branch AND (e.academic_year_id IS NULL OR e.academic_year_id = :year_id)'
            . ' AND (es.class_id IS NULL OR es.class_id = :class_id)'
            . ' ORDER BY es.date, es.start_time, s.name'
        );
        $statement->execute(['year_id' => (int) $student['academic_year_id'], 'class_id' => (int) $student['class_id'], 'branch' => BranchContext::id()]);
        $scheduleRows = $statement->fetchAll();
        $examNames = array_values(array_unique(array_map(static fn (array $row): string => (string) $row['exam_name'], $scheduleRows)));
        $examName = count($examNames) === 1 ? $examNames[0] : 'Scheduled Examinations';
        $schedule = $scheduleRows;
        $schoolSettings = [];
        foreach (Database::connection()->query('SELECT `key`, value FROM school_settings WHERE branch_id=' . BranchContext::id())->fetchAll() as $setting) {
            $schoolSettings[(string) $setting['key']] = (string) ($setting['value'] ?? '');
        }

        View::render('results/admit-card-print', [
            'title' => 'Admit card · ' . (string) $student['name'],
            'user' => Auth::user(),
            'student' => $student,
            'examName' => $examName,
            'schedule' => $schedule,
            'schoolSettings' => $schoolSettings,
        ]);
    }

    /**
     * A result screen that is set up by a configuration row.
     */
    private function configuredScreen(Request $request, string $type, string $title, string $view): void
    {
        Guard::requirePermission('reports.view');

        $configId = (int) $request->input('config', 0);
        $config = $configId > 0 ? ReportConfig::find($configId) : null;

        if ($config === null) {
            $configs = ReportConfig::forType($type);
            $config = $configs[0] ?? null;
        }

        $results = [];
        $schoolSettings = [];
        $admitSchedule = [];
        $classId = 0;
        if ($config !== null) {
            $classId = ReportConfig::classId((string) $config['class_name']);
            $results = $this->results(
                $classId,
                (string) $config['section'],
                (string) $config['exam_name'],
                (string) $config['academic_year'],
                $type === 'admit-card'
            );
        }

        if ($type === 'admit-card') {
            foreach (Database::connection()->query('SELECT `key`, value FROM school_settings WHERE branch_id=' . BranchContext::id())->fetchAll() as $setting) {
                $schoolSettings[(string) $setting['key']] = (string) ($setting['value'] ?? '');
            }

            if ($config !== null && $classId > 0) {
                $scheduleStatement = Database::connection()->prepare(
                    'SELECT s.name AS subject_name, es.date, es.start_time, es.end_time, es.hall'
                    . ' FROM exam_schedules es'
                    . ' JOIN exams e ON e.id = es.exam_id AND e.branch_id=es.branch_id'
                    . ' JOIN subjects s ON s.id = es.subject_id AND s.branch_id=es.branch_id'
                    . ' WHERE es.branch_id=:branch AND e.name = :exam_name'
                    . ' AND (e.academic_year_id IS NULL OR EXISTS ('
                    . 'SELECT 1 FROM academic_years ay WHERE ay.id = e.academic_year_id AND ay.branch_id=e.branch_id AND ay.name = :academic_year))'
                    . ' AND (es.class_id IS NULL OR es.class_id = :class_id)'
                    . ' ORDER BY es.date ASC, es.start_time ASC, s.name ASC'
                );
                $scheduleStatement->execute([
                    'exam_name' => (string) $config['exam_name'],
                    'academic_year' => (string) $config['academic_year'],
                    'class_id' => $classId,
                    'branch' => BranchContext::id(),
                ]);
                $admitSchedule = $scheduleStatement->fetchAll();
            }
        }

        View::render($view, [
            'title' => $title,
            'user' => Auth::user(),
            'type' => $type,
            'configs' => ReportConfig::forType($type),
            'config' => $config,
            'results' => $results,
            'classes' => Catalogue::classes(),
            'sections' => Catalogue::sections(),
            'schoolSettings' => $schoolSettings,
            'admitSchedule' => $admitSchedule,
        ]);
    }

    /* ------------------------------------------------------------------
     * Configuration forms
     * --------------------------------------------------------------- */

    public function configForm(Request $request): void
    {
        Guard::requirePermission('reports.view');

        $type = (string) $request->input('type', 'report');
        if (!isset(ReportConfig::TYPES[$type])) {
            $type = 'report';
        }

        $id = (int) $request->input('id', 0);
        $config = $id > 0 ? ReportConfig::find($id) : null;

        View::render('results/config-form', [
            'title' => 'Result screen setup',
            'user' => Auth::user(),
            'type' => $type,
            'typeLabel' => ReportConfig::TYPES[$type],
            'config' => $config,
            'classes' => Catalogue::classes(),
            'sections' => Catalogue::sections(),
            'exams' => $this->examNames(),
            'years' => $this->yearNames(),
        ]);
    }

    public function configSave(Request $request): void
    {
        Guard::requirePermission('reports.view');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/reports'));
            exit;
        }

        $type = (string) $request->input('type', 'report');
        if (!isset(ReportConfig::TYPES[$type])) {
            $type = 'report';
        }

        $data = [
            'type' => $type,
            'class_name' => trim((string) $request->input('class_name', '')),
            'section' => trim((string) $request->input('section', '')),
            'exam_name' => trim((string) $request->input('exam_name', '')),
            'academic_year' => trim((string) $request->input('academic_year', '')),
            'config' => trim((string) $request->input('config', '')) ?: null,
        ];

        if ($data['class_name'] === '' || $data['exam_name'] === '' || $data['academic_year'] === '') {
            Toast::flash('A class, an exam and an academic year are all required.', 'error');
            header('Location: ' . Url::to('/reports/config?type=' . urlencode($type)));
            exit;
        }

        $id = ReportConfig::save((int) $request->input('id', 0), $data);
        Toast::flash('The ' . strtolower(ReportConfig::TYPES[$type]) . ' setup was saved.');
        header('Location: ' . Url::to($this->routeForType($type) . '?config=' . $id));
        exit;
    }

    public function configDelete(Request $request): void
    {
        Guard::requirePermission('reports.view');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/reports'));
            exit;
        }

        $type = (string) $request->input('type', 'report');
        ReportConfig::delete((int) $request->input('id', 0));
        Toast::flash('The setup was removed.');
        header('Location: ' . Url::to($this->routeForType($type)));
        exit;
    }

    private function routeForType(string $type): string
    {
        return match ($type) {
            'tabulation' => '/tabulation-sheets',
            'merit-list' => '/merit-lists',
            'section-result' => '/section-results',
            'fail-list' => '/fail-lists',
            'admit-card' => '/admit-cards',
            default => '/reports',
        };
    }

    /* ------------------------------------------------------------------
     * Helpers
     * --------------------------------------------------------------- */

    /**
     * @return array<int, array<string, mixed>>
     */
    private function results(int $classId, string $section, string $examName, string $academicYear, bool $includeStudentDetails = false): array
    {
        if ($classId <= 0 || $examName === '' || $academicYear === '') {
            return [];
        }
        return ResultSet::forClass($classId, $section, $examName, $academicYear, $includeStudentDetails);
    }

    /** @return array<string, mixed>|null */
    private function findReport(int $studentId, string $examName, string $academicYear): ?array
    {
        $examId = $this->examId($examName, $academicYear);
        if ($examId <= 0) {
            return null;
        }
        $statement = Database::connection()->prepare(
            'SELECT * FROM academic_reports WHERE student_id = :student_id AND branch_id=:branch AND exam_id = :exam_id LIMIT 1'
        );
        $statement->execute([
            'student_id' => $studentId,
            'exam_id' => $examId,
            'branch' => BranchContext::id(),
        ]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    private function examId(string $examName, string $academicYear): int
    {
        $statement = Database::connection()->prepare('SELECT e.id FROM exams e LEFT JOIN academic_years ay ON ay.id = e.academic_year_id AND ay.branch_id=e.branch_id WHERE e.name = :name AND e.branch_id=:branch AND (e.academic_year_id IS NULL OR ay.name = :year) ORDER BY (e.academic_year_id IS NULL), e.id LIMIT 1');
        $statement->execute(['name' => $examName, 'year' => $academicYear, 'branch' => BranchContext::id()]);
        return (int) ($statement->fetchColumn() ?: 0);
    }

    private function findReportId(int $studentId, string $examName, string $academicYear): int
    {
        $report = $this->findReport($studentId, $examName, $academicYear);
        return $report === null ? 0 : (int) $report['id'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function marks(int $reportId): array
    {
        $statement = Database::connection()->prepare('SELECT * FROM academic_report_marks WHERE academic_report_id = :id AND branch_id=:branch ORDER BY id');
        $statement->execute(['id' => $reportId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The subjects a class sits, used to pre-fill the report card.
     *
     * @return array<int, array<string, mixed>>
     */
    private function classSubjects(int $classId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.id, s.name, cs.full_marks FROM class_subject cs JOIN subjects s ON s.id = cs.subject_id AND s.branch_id=cs.branch_id'
            . ' WHERE cs.class_id = :class_id AND cs.branch_id=:branch ORDER BY cs.sort_order, s.name'
        );
        $statement->execute(['class_id' => $classId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /** @return array<int, string> */
    private function examNames(): array
    {
        $rows = Database::connection()->query('SELECT name AS exam_name FROM exams WHERE branch_id=' . BranchContext::id() . ' ORDER BY exam_name DESC')->fetchAll();
        $names = [];
        foreach ($rows as $row) {
            $names[] = (string) $row['exam_name'];
        }
        return $names;
    }

    /** @return array<int, string> */
    private function yearNames(): array
    {
        $rows = Database::connection()->query('SELECT name AS academic_year FROM academic_years WHERE branch_id=' . BranchContext::id() . ' ORDER BY academic_year DESC')->fetchAll();
        $years = [];
        foreach ($rows as $row) {
            $years[] = (string) $row['academic_year'];
        }
        return $years;
    }
}
