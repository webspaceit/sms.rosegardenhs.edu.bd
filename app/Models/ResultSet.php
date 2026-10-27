<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\BranchContext;

/**
 * The computed results of a class for an exam.
 *
 * This is the shared engine behind the report card, the tabulation sheet,
 * the merit list, the section result and the fail list. It loads the marks
 * once and works out the totals, the grade points and the class positions
 * using the grading scale of the National Education Policy.
 */
final class ResultSet
{
    /** Marks below this are a fail. */
    public const PASS_MARKS = 33;

    /**
     * The grade points for a mark, following the Bangladesh grading scale.
     *
     * @return array{0: string, 1: float} Letter grade and grade point.
     */
    public static function gradeFor(float $marks): array
    {
        return match (true) {
            $marks >= 80 => ['A+', 5.0],
            $marks >= 70 => ['A', 4.0],
            $marks >= 60 => ['A-', 3.5],
            $marks >= 50 => ['B', 3.0],
            $marks >= 40 => ['C', 2.0],
            $marks >= self::PASS_MARKS => ['D', 1.0],
            default => ['F', 0.0],
        };
    }

    /** The overall letter grade for an average grade point. */
    public static function overallGrade(float $average, bool $hasFailed): string
    {
        if ($hasFailed || $average < 1.0) {
            return 'F';
        }

        return match (true) {
            $average >= 5.0 => 'A+',
            $average >= 4.0 => 'A',
            $average >= 3.5 => 'A-',
            $average >= 3.0 => 'B',
            $average >= 2.0 => 'C',
            default => 'D',
        };
    }

    /**
     * Load the results of a class for an exam.
     *
     * @return array<int, array<string, mixed>> One entry per student.
     */
    public static function forClass(int $classId, string $section, string $examName, string $academicYear, bool $includeStudentDetails = false): array
    {
        $examLookup = Database::connection()->prepare('SELECT e.id FROM exams e LEFT JOIN academic_years ay ON ay.id = e.academic_year_id AND ay.branch_id=e.branch_id WHERE e.name = :name AND e.branch_id=:branch AND (e.academic_year_id IS NULL OR ay.name = :year) ORDER BY (e.academic_year_id IS NULL), e.id LIMIT 1');
        $examLookup->execute(['name' => $examName, 'year' => $academicYear, 'branch' => BranchContext::id()]);
        $examId = (int) ($examLookup->fetchColumn() ?: 0);
        $sql = 'SELECT ar.id, ar.student_id, ar.class_position, ar.working_days, ar.present_days, ar.absent_days,'
            . ' ar.moral_evaluation, ar.co_curricular, ar.comments,'
            . ' s.name AS student_name, s.student_id AS student_code, s.roll_no, c.name AS class_name';
        if ($includeStudentDetails) {
            $sql .= ', s.name_bn AS student_name_bn, s.photo, sec.name AS section_name'
                . ', (SELECT g.name FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
                . " WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id AND g.relation = 'Father' ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS father_name"
                . ', (SELECT g.name FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
                . " WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id AND g.relation = 'Mother' ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS mother_name"
                . ', (SELECT g.name FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
                . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_name'
                . ', (SELECT g.phone FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
                . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_phone';
        }
        $sql .= ' FROM academic_reports ar'
            . ' JOIN students s ON s.id = ar.student_id AND s.branch_id=ar.branch_id'
            . ' JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id'
            . ' LEFT JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id'
            . ' WHERE ar.branch_id=:branch AND ((:exam_id_check > 0 AND ar.exam_id = :exam_id) OR (ar.exam_id IS NULL AND ar.exam_name = :exam_name AND ar.academic_year = :academic_year))'
            . ' AND s.class_id = :class_id';

        $bindings = [
            'exam_id_check' => $examId,
            'exam_id' => $examId,
            'exam_name' => $examName,
            'academic_year' => $academicYear,
            'class_id' => $classId,
            'branch' => BranchContext::id(),
        ];

        if ($section !== '') {
            $sql .= ' AND sec.name = :section';
            $bindings['section'] = $section;
        }

        $sql .= ' ORDER BY s.roll_no ASC';

        $statement = Database::connection()->prepare($sql);
        $statement->execute($bindings);
        $rows = $statement->fetchAll();

        if ($rows === []) {
            return [];
        }

        $reportIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $marks = self::marksFor($reportIds);

        $results = [];
        foreach ($rows as $row) {
            $reportId = (int) $row['id'];
            $subjects = $marks[$reportId] ?? [];

            $total = 0.0;
            $gradePoints = 0.0;
            $hasFailed = false;
            $failedSubjects = [];

            foreach ($subjects as $subject) {
                $subjectTotal = (float) $subject['total'];
                $total += $subjectTotal;
                [$letter, $point] = self::gradeFor($subjectTotal);
                $subject['letter_grade'] = $letter;
                $subject['grade_point'] = $point;
                $subjects[$subject['id']] = $subject;

                if ($letter === 'F') {
                    $hasFailed = true;
                    $failedSubjects[] = $subject['subject_name'] . ' (' . (int) $subjectTotal . ')';
                } else {
                    $gradePoints += $point;
                }
            }

            $average = $subjects === [] ? 0.0 : round($gradePoints / count($subjects), 2);

            $results[] = [
                'report_id' => $reportId,
                'student_id' => (int) $row['student_id'],
                'student_name' => (string) $row['student_name'],
                'student_name_bn' => (string) ($row['student_name_bn'] ?? ''),
                'photo' => (string) ($row['photo'] ?? ''),
                'student_code' => (string) $row['student_code'],
                'roll_no' => (int) $row['roll_no'],
                'class_name' => (string) $row['class_name'],
                'section_name' => (string) ($row['section_name'] ?? ''),
                'father_name' => (string) ($row['father_name'] ?? ''),
                'mother_name' => (string) ($row['mother_name'] ?? ''),
                'guardian_name' => (string) ($row['guardian_name'] ?? ''),
                'guardian_phone' => (string) ($row['guardian_phone'] ?? ''),
                'working_days' => (int) $row['working_days'],
                'present_days' => (int) $row['present_days'],
                'absent_days' => (int) $row['absent_days'],
                'moral_evaluation' => (string) ($row['moral_evaluation'] ?? ''),
                'co_curricular' => (string) ($row['co_curricular'] ?? ''),
                'comments' => (string) ($row['comments'] ?? ''),
                'subjects' => array_values($subjects),
                'total' => (int) round($total),
                'gpa' => $hasFailed ? 0.0 : $average,
                'grade' => self::overallGrade($average, $hasFailed),
                'has_failed' => $hasFailed,
                'failed_subjects' => $failedSubjects,
                'position' => 0,
            ];
        }

        self::assignPositions($results);

        return $results;
    }

    /**
     * Rank the students, best first, and record their position.
     *
     * @param array<int, array<string, mixed>> $results
     */
    private static function assignPositions(array &$results): void
    {
        $order = $results;
        usort($order, static function (array $a, array $b): int {
            if ($a['total'] !== $b['total']) {
                return $b['total'] <=> $a['total'];
            }
            return $a['roll_no'] <=> $b['roll_no'];
        });

        $position = 0;
        $previousTotal = null;
        $previousPosition = 0;

        foreach ($order as $entry) {
            $position++;
            if ($previousTotal !== null && $entry['total'] === $previousTotal) {
                $entry['position'] = $previousPosition;
            } else {
                $entry['position'] = $position;
                $previousPosition = $position;
            }
            $previousTotal = $entry['total'];

            foreach ($results as $index => $result) {
                if ($result['report_id'] === $entry['report_id']) {
                    $results[$index]['position'] = $entry['position'];
                }
            }
        }
    }

    /**
     * The subject marks of the given reports, keyed by report id.
     *
     * @param array<int, int> $reportIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private static function marksFor(array $reportIds): array
    {
        if ($reportIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($reportIds), '?'));

        $statement = Database::connection()->prepare(
            'SELECT * FROM academic_report_marks WHERE branch_id=' . BranchContext::id() . ' AND academic_report_id IN (' . $placeholders . ') ORDER BY id ASC'
        );
        $statement->execute($reportIds);

        $marks = [];
        foreach ($statement->fetchAll() as $row) {
            $reportId = (int) $row['academic_report_id'];
            $marks[$reportId][(int) $row['id']] = [
                'id' => (int) $row['id'],
                'subject_name' => (string) $row['subject_name'],
                'full_marks' => (int) $row['full_marks'],
                'highest_marks' => (int) $row['highest_marks'],
                'written_marks' => (int) $row['written_marks'],
                'mcq_marks' => (int) $row['mcq_marks'],
                'practical_marks' => (int) $row['practical_marks'],
                'ca_marks' => (int) $row['ca_marks'],
                'total' => (int) $row['written_marks'] + (int) $row['mcq_marks']
                    + (int) $row['practical_marks'] + (int) $row['ca_marks']
                    + (int) ($row['obtained'] ?? 0),
            ];
        }

        return $marks;
    }
}
