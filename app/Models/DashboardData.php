<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\BranchContext;

final class DashboardData
{
    /** @return array{count:int,students:array<int,array<string,mixed>>} */
    public static function pendingStudentRegistrations(): array
    {
        $database = Database::connection();
        $branch = BranchContext::id();
        $countQuery = $database->prepare("SELECT COUNT(*) FROM students WHERE branch_id=:branch AND registration_status='pending'");
        $countQuery->execute(['branch' => $branch]);
        $studentsQuery = $database->prepare(
            "SELECT s.id, s.student_id, s.name, s.created_at, c.name AS class_name, sec.name AS section_name, ay.name AS year_name"
            . ' FROM students s'
            . ' JOIN classes c ON c.id=s.class_id AND c.branch_id=s.branch_id'
            . ' JOIN sections sec ON sec.id=s.section_id AND sec.branch_id=s.branch_id'
            . ' JOIN academic_years ay ON ay.id=s.academic_year_id AND ay.branch_id=s.branch_id'
            . " WHERE s.branch_id=:branch AND s.registration_status='pending'"
            . ' ORDER BY s.created_at DESC, s.id DESC LIMIT 100'
        );
        $studentsQuery->execute(['branch' => $branch]);
        return ['count' => (int) $countQuery->fetchColumn(), 'students' => $studentsQuery->fetchAll()];
    }

    public static function summary(): array
    {
        $database = Database::read();
        $branch = BranchContext::id();
        $year = \App\Core\AcademicYearContext::selected();
        $yearId = (int) ($year['id'] ?? 0);
        $yearStart = (string) ($year['start_date'] ?? '1000-01-01');
        $yearEnd = (string) ($year['end_date'] ?? date('Y-m-d'));
        $enrollments = 'SELECT s.id AS student_id, s.class_id, s.status FROM students s WHERE s.branch_id = :current_branch AND s.academic_year_id = :current_year'
            . ' UNION SELECT h.student_id, h.class_id, h.status FROM student_enrollment_history h WHERE h.branch_id = :history_branch AND h.academic_year_id = :history_year';
        if ($yearId > 0) {
            $studentCountQuery = $database->prepare(
                'SELECT COUNT(DISTINCT e.student_id) FROM (' . $enrollments . ") e WHERE e.status = 'active'"
            );
            $studentCountQuery->execute([
                'current_branch' => $branch, 'current_year' => $yearId,
                'history_branch' => $branch, 'history_year' => $yearId,
            ]);
            $students = (int) $studentCountQuery->fetchColumn();
        } else {
            $students = (int) $database->query("SELECT COUNT(*) FROM students WHERE status = 'active' AND branch_id=" . $branch)->fetchColumn();
        }
        $classes = (int) $database->query('SELECT COUNT(*) FROM classes WHERE branch_id=' . $branch)->fetchColumn();
        $sections = (int) $database->query('SELECT COUNT(*) FROM sections WHERE branch_id=' . $branch)->fetchColumn();
        $teacherQuery = $database->prepare('SELECT COUNT(*) FROM teachers WHERE branch_id = :branch AND COALESCE(joining_date, DATE(created_at)) <= :year_end');
        $teacherQuery->execute(['branch' => $branch, 'year_end' => $yearEnd]);
        $teachingStaff = (int) $teacherQuery->fetchColumn();
        $attendanceQuery = $database->prepare(
            "SELECT COUNT(CASE WHEN a.status <> 'leave' THEN 1 END) AS marked,"
            . " COUNT(CASE WHEN a.status IN ('present', 'late', 'half_day') THEN 1 END) AS attended"
            . ' FROM attendances a INNER JOIN students s ON s.id = a.student_id AND s.branch_id = :student_branch'
            . ' WHERE a.date >= :session_start AND a.date <= :session_end'
            . ' AND (s.academic_year_id = :current_year OR EXISTS (SELECT 1 FROM student_enrollment_history eh'
            . ' WHERE eh.student_id = s.id AND eh.branch_id = :history_branch AND eh.academic_year_id = :history_year))'
        );
        $attendanceQuery->execute([
            'student_branch' => $branch, 'session_start' => $yearStart, 'session_end' => $yearEnd,
            'current_year' => $yearId, 'history_branch' => $branch, 'history_year' => $yearId,
        ]);
        $attendance = $attendanceQuery->fetch();
        $attendanceMarked = (int) ($attendance['marked'] ?? 0);
        $attendanceRate = $attendanceMarked > 0
            ? (int) round(((int) $attendance['attended'] / $attendanceMarked) * 100)
            : null;
        $feeQuery = $database->prepare(
            "SELECT COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) AS collected_this_session,"
            . " COALESCE(SUM(CASE WHEN status IN ('due', 'partial') AND due_date < CURDATE() THEN amount ELSE 0 END), 0) AS overdue_amount,"
            . " COUNT(CASE WHEN status IN ('due', 'partial') AND due_date < CURDATE() THEN 1 END) AS overdue_count"
            . ' FROM fee_collections WHERE branch_id = :branch AND academic_year_id = :year'
        );
        $feeQuery->execute(['branch' => $branch, 'year' => $yearId]);
        $fees = $feeQuery->fetch();
        $departmentQuery = $database->prepare(
            "SELECT COALESCE(NULLIF(dept.name, ''), 'Unassigned') AS name, COUNT(*) AS total"
            . " FROM teachers t LEFT JOIN departments dept ON dept.id = t.department_id AND dept.branch_id = t.branch_id"
            . " WHERE t.branch_id = :branch AND COALESCE(t.joining_date, DATE(t.created_at)) <= :year_end"
            . " GROUP BY COALESCE(NULLIF(dept.name, ''), 'Unassigned')"
            . ' ORDER BY total DESC, name ASC LIMIT 8'
        );
        $departmentQuery->execute(['branch' => $branch, 'year_end' => $yearEnd]);
        $departments = $departmentQuery->fetchAll();
        $upcomingEvents = $database->query(
            'SELECT title, start_date, end_date, description FROM events'
            . ' WHERE branch_id=' . $branch . ' AND COALESCE(end_date, start_date) >= CURDATE()'
            . ' ORDER BY start_date ASC, id ASC LIMIT 6'
        )->fetchAll();
        $activeNotices = $database->query(
            'SELECT title, type, published_date FROM notices'
            . ' WHERE branch_id=' . $branch . ' AND is_published = 1 AND published_date <= CURDATE()'
            . ' AND (expires_date IS NULL OR expires_date >= CURDATE())'
            . ' ORDER BY published_date DESC, id DESC LIMIT 3'
        )->fetchAll();
        $pendingComplaints = (int) $database->query(
            "SELECT COUNT(*) FROM complaints WHERE branch_id=" . $branch . " AND status IN ('pending', 'investigating')"
        )->fetchColumn();
        $admissionQuery = $database->prepare(
            'SELECT COUNT(*) FROM students WHERE branch_id = :branch AND academic_year_id = :year AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND created_at <= :year_end'
        );
        $admissionQuery->execute(['branch' => $branch, 'year' => $yearId, 'year_end' => $yearEnd]);
        $newAdmissions = (int) $admissionQuery->fetchColumn();
        $classQuery = $database->prepare(
            "SELECT c.name, c.numeric_level, COUNT(DISTINCT e.student_id) AS total"
            . ' FROM classes c LEFT JOIN (' . $enrollments . ") e ON e.class_id = c.id AND e.status = 'active'"
            . ' WHERE c.branch_id = :class_branch'
            . ' GROUP BY c.id, c.name, c.numeric_level ORDER BY c.numeric_level'
        );
        $classQuery->execute([
            'current_branch' => $branch, 'current_year' => $yearId,
            'history_branch' => $branch, 'history_year' => $yearId, 'class_branch' => $branch,
        ]);
        $classEnrollment = $classQuery->fetchAll();
        $recentQuery = $database->prepare(
            'SELECT s.student_id, s.name, c.name AS class_name, sec.name AS section_name, e.roll_no'
            . ' FROM (SELECT id AS student_id, class_id, section_id, roll_no, created_at AS enrollment_at FROM students WHERE branch_id = :current_branch AND academic_year_id = :current_year'
            . ' UNION ALL SELECT student_id, class_id, section_id, roll_no, recorded_at AS enrollment_at FROM student_enrollment_history WHERE branch_id = :history_branch AND academic_year_id = :history_year) e'
            . ' INNER JOIN students s ON s.id = e.student_id AND s.branch_id = :student_branch'
            . ' INNER JOIN classes c ON c.id = e.class_id AND c.branch_id = :class_branch'
            . ' INNER JOIN sections sec ON sec.id = e.section_id AND sec.branch_id = :section_branch'
            . ' ORDER BY e.enrollment_at DESC, e.student_id DESC LIMIT 5'
        );
        $recentQuery->execute([
            'current_branch' => $branch, 'current_year' => $yearId,
            'history_branch' => $branch, 'history_year' => $yearId, 'student_branch' => $branch,
            'class_branch' => $branch, 'section_branch' => $branch,
        ]);
        $recent = $recentQuery->fetchAll();
        $trendEnd = new \DateTimeImmutable(min($yearEnd, date('Y-m-d')));
        $trendStart = $trendEnd->modify('first day of this month')->modify('-5 months');
        $sessionStart = new \DateTimeImmutable($yearStart);
        if ($trendStart < $sessionStart) {
            $trendStart = $sessionStart->modify('first day of this month');
        }
        $trendQuery = $database->prepare('SELECT DATE_FORMAT(created_at, \'%Y-%m\') AS month_key, COUNT(*) AS total FROM students WHERE branch_id=:branch AND academic_year_id=:year AND created_at >= :start AND created_at <= :end GROUP BY month_key ORDER BY month_key');
        $trendQuery->execute(['branch' => $branch, 'year' => $yearId, 'start' => $trendStart->format('Y-m-d 00:00:00'), 'end' => $trendEnd->format('Y-m-t 23:59:59')]);
        $monthlyAdmissions = [];
        foreach ($trendQuery->fetchAll() as $row) {
            $monthlyAdmissions[$row['month_key']] = (int) $row['total'];
        }
        $admissionsTrend = [];
        $lastTrendMonth = $trendEnd->modify('first day of this month');
        $trendMonths = (((int) $trendStart->format('Y') - (int) $lastTrendMonth->format('Y')) * -12)
            + (int) $lastTrendMonth->format('n') - (int) $trendStart->format('n') + 1;
        for ($offset = 0; $offset < min(6, max(1, $trendMonths)); $offset++) {
            $month = $trendStart->modify('+' . $offset . ' months');
            $monthKey = $month->format('Y-m');
            $admissionsTrend[] = ['label' => $month->format('M'), 'total' => $monthlyAdmissions[$monthKey] ?? 0];
        }

        return [
            'academicYear' => $year['name'] ?? date('Y'),
            'academicYearIsCurrent' => (bool) ($year['is_current'] ?? false),
            'studentCount' => $students,
            'classCount' => $classes,
            'sectionCount' => $sections,
            'teachingStaffCount' => $teachingStaff,
            'attendanceRate' => $attendanceRate,
            'attendanceMarked' => $attendanceMarked,
            'feeCollectedThisMonth' => (float) ($fees['collected_this_session'] ?? 0),
            'overdueFeeAmount' => (float) ($fees['overdue_amount'] ?? 0),
            'overdueFeeCount' => (int) ($fees['overdue_count'] ?? 0),
            'departments' => $departments,
            'upcomingEvents' => $upcomingEvents,
            'activeNotices' => $activeNotices,
            'pendingComplaints' => $pendingComplaints,
            'newAdmissions' => $newAdmissions,
            'classEnrollment' => $classEnrollment,
            'recentStudents' => $recent,
            'admissionsTrend' => $admissionsTrend,
        ];
    }
}
