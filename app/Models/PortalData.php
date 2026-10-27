<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\BranchContext;
use PDO;

/**
 * The figures each portal shows.
 *
 * Every method takes the id of the person the data belongs to, so the same
 * queries serve the student, the teacher and the parent views.
 */
final class PortalData
{
    /**
     * A student's attendance for a period.
     *
     * @return array{total: int, present: int, absent: int, late: int, leave: int, percent: float}
     */
    public static function studentAttendance(int $studentId, ?string $since = null): array
    {
        $sql = 'SELECT'
            . " SUM(status IN ('present','late','half_day')) AS present_count,"
            . " SUM(status = 'absent') AS absent_count,"
            . " SUM(status = 'late') AS late_count,"
            . " SUM(status = 'leave') AS leave_count,"
            . ' COUNT(*) AS total_count'
            . ' FROM attendances WHERE student_id = :student_id AND branch_id=:branch_id';
        $bindings = ['student_id' => $studentId, 'branch_id' => BranchContext::id()];

        if ($since !== null) {
            $sql .= ' AND date >= :since';
            $bindings['since'] = $since;
        }

        $row = Database::read()->prepare($sql);
        $row->execute($bindings);
        $counts = $row->fetch() ?: [];

        $total = (int) ($counts['total_count'] ?? 0);
        $present = (int) ($counts['present_count'] ?? 0);

        return [
            'total' => $total,
            'present' => $present,
            'absent' => (int) ($counts['absent_count'] ?? 0),
            'late' => (int) ($counts['late_count'] ?? 0),
            'leave' => (int) ($counts['leave_count'] ?? 0),
            'percent' => $total > 0 ? round($present / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * The attendance marks of a student for one month, keyed by day.
     *
     * @return array<int, string>
     */
    public static function studentAttendanceDays(int $studentId, string $month): array
    {
        $statement = Database::read()->prepare(
            'SELECT date, status FROM attendances WHERE student_id = :student_id AND branch_id=:branch_id AND DATE_FORMAT(date, "%Y-%m") = :month'
        );
        $statement->execute(['student_id' => $studentId, 'month' => $month, 'branch_id' => BranchContext::id()]);

        $days = [];
        foreach ($statement->fetchAll() as $row) {
            $days[(int) substr((string) $row['date'], 8, 2)] = (string) $row['status'];
        }
        return $days;
    }

    /**
     * A student's report cards.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function studentReports(int $studentId): array
    {
        $statement = Database::read()->prepare(
            'SELECT ar.id, e.name AS exam_name, ay.name AS academic_year, ar.working_days, ar.present_days,'
            . ' ar.absent_days, ar.moral_evaluation, ar.comments'
            . ' FROM academic_reports ar'
            . ' LEFT JOIN exams e ON e.id = ar.exam_id AND e.branch_id = ar.branch_id'
            . ' LEFT JOIN academic_years ay ON ay.id = e.academic_year_id AND ay.branch_id = e.branch_id'
            . ' WHERE ar.student_id = :student_id AND ar.branch_id=:branch_id'
            . ' ORDER BY ay.name DESC, e.name DESC'
        );
        $statement->execute(['student_id' => $studentId, 'branch_id' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The marks of one report card, with the totals worked out.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function reportMarks(int $reportId): array
    {
        $statement = Database::read()->prepare(
            'SELECT arm.*, s.name AS subject_name FROM academic_report_marks arm'
            . ' LEFT JOIN subjects s ON s.id = arm.subject_id AND s.branch_id = arm.branch_id'
            . ' WHERE arm.academic_report_id = :id AND arm.branch_id=:branch ORDER BY arm.id'
        );
        $statement->execute(['id' => $reportId, 'branch' => BranchContext::id()]);

        $marks = [];
        foreach ($statement->fetchAll() as $row) {
            $total = (int) $row['written_marks'] + (int) $row['mcq_marks']
                + (int) $row['practical_marks'] + (int) $row['ca_marks']
                + (int) ($row['obtained'] ?? 0);
            [$letter, $point] = ResultSet::gradeFor((float) $total);
            $marks[] = [
                'subject_id' => (int) ($row['subject_id'] ?? 0),
                'subject_name' => (string) ($row['subject_name'] ?? ''),
                'full_marks' => (int) $row['full_marks'],
                'highest_marks' => (int) $row['highest_marks'],
                'written_marks' => (int) $row['written_marks'],
                'mcq_marks' => (int) $row['mcq_marks'],
                'practical_marks' => (int) $row['practical_marks'],
                'ca_marks' => (int) $row['ca_marks'],
                'obtained' => (int) ($row['obtained'] ?? 0),
                'total' => $total,
                'letter_grade' => $letter,
                'grade_point' => $point,
            ];
        }
        return $marks;
    }

    /**
     * A student's fee invoices.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function studentFees(int $studentId): array
    {
        $statement = Database::read()->prepare(
            'SELECT fc.*, h.name AS head_name FROM fee_collections fc'
            . ' JOIN fee_heads h ON h.id = fc.fee_head_id AND h.branch_id=fc.branch_id'
            . ' WHERE fc.student_id = :student_id AND fc.branch_id=:branch ORDER BY fc.id DESC'
        );
        $statement->execute(['student_id' => $studentId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The classes a teacher takes.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function teacherClasses(int $teacherId): array
    {
        $statement = Database::read()->prepare(
            'SELECT DISTINCT c.id, c.name, c.numeric_level,'
            . ' (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id AND s.branch_id=c.branch_id AND s.status = "active") AS student_count'
            . ' FROM teacher_subject ts JOIN classes c ON c.id = ts.class_id AND c.branch_id=ts.branch_id'
            . ' WHERE ts.teacher_id = :teacher_id AND ts.branch_id=:branch ORDER BY c.numeric_level'
        );
        $statement->execute(['teacher_id' => $teacherId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The subjects a teacher takes in a class.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function teacherSubjects(int $teacherId, int $classId): array
    {
        $statement = Database::read()->prepare(
            'SELECT ts.subject_id, s.name, ts.is_class_teacher FROM teacher_subject ts'
            . ' JOIN subjects s ON s.id = ts.subject_id AND s.branch_id=ts.branch_id'
            . ' WHERE ts.teacher_id = :teacher_id AND ts.class_id = :class_id AND ts.branch_id=:branch ORDER BY s.name'
        );
        $statement->execute(['teacher_id' => $teacherId, 'class_id' => $classId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The students a teacher is responsible for.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function teacherStudents(int $teacherId, int $classId): array
    {
        $statement = Database::read()->prepare(
            'SELECT s.id, s.student_id, s.name, s.name_bn, s.roll_no, sec.name AS section_name'
            . ' FROM students s JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id'
            . ' WHERE s.class_id = :class_id AND s.status = "active" AND s.branch_id=:branch ORDER BY s.roll_no'
        );
        $statement->execute(['class_id' => $classId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The routine of a class for one day.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function routineForClass(int $classId, string $day): array
    {
        $statement = Database::read()->prepare(
            'SELECT cr.*, s.name AS subject_name, t.name AS teacher_name FROM class_routines cr'
            . ' JOIN subjects s ON s.id = cr.subject_id AND s.branch_id=cr.branch_id'
            . ' LEFT JOIN teachers t ON t.id = cr.teacher_id AND t.branch_id=cr.branch_id'
            . ' WHERE cr.class_id = :class_id AND cr.day_of_week = :day AND cr.branch_id=:branch ORDER BY cr.start_time'
        );
        $statement->execute(['class_id' => $classId, 'day' => $day, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The routine of a teacher for one day.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function routineForTeacher(int $teacherId, string $day): array
    {
        $statement = Database::read()->prepare(
            'SELECT cr.*, s.name AS subject_name, c.name AS class_name FROM class_routines cr'
            . ' JOIN subjects s ON s.id = cr.subject_id AND s.branch_id=cr.branch_id'
            . ' JOIN classes c ON c.id = cr.class_id AND c.branch_id=cr.branch_id'
            . ' WHERE cr.teacher_id = :teacher_id AND cr.day_of_week = :day AND cr.branch_id=:branch ORDER BY cr.start_time'
        );
        $statement->execute(['teacher_id' => $teacherId, 'day' => $day, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /**
     * The online exams a student may sit.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function availableExams(int $studentId): array
    {
        // The student appears twice in the query, and a native prepared
        // statement cannot reuse the same named placeholder, so it is given
        // one name per use.
        $statement = Database::read()->prepare(
            'SELECT oe.*, c.name AS class_name, s.name AS subject_name,'
            . ' (SELECT COUNT(*) FROM exam_questions q WHERE q.online_exam_id = oe.id AND q.branch_id=oe.branch_id) AS question_count,'
            . ' (SELECT COUNT(*) FROM exam_submissions sub WHERE sub.online_exam_id = oe.id AND sub.student_id = :attempt_student AND sub.branch_id=oe.branch_id) AS attempted'
            . ' FROM online_exams oe'
            . ' LEFT JOIN classes c ON c.id = oe.class_id AND c.branch_id=oe.branch_id'
            . ' LEFT JOIN subjects s ON s.id = oe.subject_id AND s.branch_id=oe.branch_id'
            . ' WHERE oe.branch_id=:branch AND oe.status = "active" AND (oe.class_id IS NULL OR oe.class_id = (SELECT class_id FROM students WHERE id = :class_student AND branch_id=oe.branch_id))'
            . ' ORDER BY oe.id DESC'
        );
        $statement->execute([
            'attempt_student' => $studentId,
            'class_student' => $studentId,
            'branch' => BranchContext::id(),
        ]);
        return $statement->fetchAll();
    }

    /**
     * The leave requests waiting for a decision.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function pendingLeaveRequests(): array
    {
        return Database::read()->query(
            'SELECT l.*, t.name AS teacher_name FROM leave_requests l'
            . ' JOIN teachers t ON t.id = l.teacher_id AND t.branch_id=l.branch_id'
            . " WHERE l.branch_id=" . BranchContext::id() . " AND l.status = 'pending' ORDER BY l.start_date ASC"
        )->fetchAll();
    }

    /**
     * The latest published notices.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function latestNotices(int $limit = 5, ?string $role = null): array
    {
        $limit = max(1, min(50, $limit));

        $sql = 'SELECT n.title, n.content, n.type, n.published_date FROM notices n'
            . ' WHERE n.branch_id=' . BranchContext::id() . ' AND n.is_published = 1'
            . ' AND (n.expires_date IS NULL OR n.expires_date >= CURDATE())';

        if ($role !== null && $role !== '') {
            $sql .= ' AND (NOT EXISTS (SELECT 1 FROM notice_roles nr WHERE nr.notice_id = n.id)'
                . ' OR EXISTS (SELECT 1 FROM notice_roles nr2 WHERE nr2.notice_id = n.id AND nr2.role = :role))';
        }

        $sql .= ' ORDER BY n.published_date DESC LIMIT ' . $limit;
        $statement = Database::read()->prepare($sql);
        $statement->execute($role !== null && $role !== '' ? ['role' => $role] : []);
        return $statement->fetchAll();
    }
}
