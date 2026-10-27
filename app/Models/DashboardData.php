<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\BranchContext;

final class DashboardData
{
    public static function summary(): array
    {
        $database = Database::connection();
        $branch = BranchContext::id();
        $year = $database->query('SELECT id, name FROM academic_years WHERE is_current = 1 AND branch_id=' . $branch . ' ORDER BY id DESC LIMIT 1')->fetch();
        $students = (int) $database->query("SELECT COUNT(*) FROM students WHERE status = 'active' AND branch_id=" . $branch)->fetchColumn();
        $classes = (int) $database->query('SELECT COUNT(*) FROM classes WHERE branch_id=' . $branch)->fetchColumn();
        $sections = (int) $database->query('SELECT COUNT(*) FROM sections WHERE branch_id=' . $branch)->fetchColumn();
        $teachingStaff = (int) $database->query('SELECT COUNT(*) FROM teachers WHERE branch_id=' . $branch)->fetchColumn();
        $attendance = $database->query(
            "SELECT COUNT(CASE WHEN status <> 'leave' THEN 1 END) AS marked,"
            . " COUNT(CASE WHEN status IN ('present', 'late', 'half_day') THEN 1 END) AS attended"
            . ' FROM attendances WHERE date = CURDATE() AND branch_id=' . $branch
        )->fetch();
        $attendanceMarked = (int) ($attendance['marked'] ?? 0);
        $attendanceRate = $attendanceMarked > 0
            ? (int) round(((int) $attendance['attended'] / $attendanceMarked) * 100)
            : null;
        $fees = $database->query(
            "SELECT COALESCE(SUM(CASE WHEN status = 'paid' AND paid_on >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND paid_on < DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY) THEN amount ELSE 0 END), 0) AS collected_this_month,"
            . " COALESCE(SUM(CASE WHEN status IN ('due', 'partial') AND due_date < CURDATE() THEN amount ELSE 0 END), 0) AS overdue_amount,"
            . " COUNT(CASE WHEN status IN ('due', 'partial') AND due_date < CURDATE() THEN 1 END) AS overdue_count"
            . ' FROM fee_collections WHERE branch_id=' . $branch
        )->fetch();
        $departments = $database->query(
            "SELECT COALESCE(NULLIF(TRIM(department), ''), 'Unassigned') AS name, COUNT(*) AS total"
            . " FROM teachers WHERE branch_id=" . $branch . " GROUP BY COALESCE(NULLIF(TRIM(department), ''), 'Unassigned')"
            . ' ORDER BY total DESC, name ASC LIMIT 8'
        )->fetchAll();
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
        $newAdmissions = (int) $database->query(
            'SELECT COUNT(*) FROM students WHERE branch_id=' . $branch . ' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)'
        )->fetchColumn();
        $classEnrollment = $database->query(
            "SELECT c.name, c.numeric_level, COUNT(s.id) AS total"
            . " FROM classes c LEFT JOIN students s ON s.class_id = c.id AND s.branch_id=c.branch_id AND s.status = 'active'"
            . ' WHERE c.branch_id=' . $branch
            . ' GROUP BY c.id, c.name, c.numeric_level ORDER BY c.numeric_level'
        )->fetchAll();
        $recent = $database->query('SELECT students.student_id, students.name, classes.name AS class_name, sections.name AS section_name, students.roll_no FROM students INNER JOIN classes ON classes.id = students.class_id AND classes.branch_id=students.branch_id INNER JOIN sections ON sections.id = students.section_id AND sections.branch_id=students.branch_id WHERE students.branch_id=' . $branch . ' ORDER BY students.id DESC LIMIT 5')->fetchAll();
        $trendStart = (new \DateTimeImmutable('first day of this month'))->modify('-5 months');
        $trendQuery = $database->prepare('SELECT DATE_FORMAT(created_at, \'%Y-%m\') AS month_key, COUNT(*) AS total FROM students WHERE branch_id=:branch AND created_at >= :start GROUP BY month_key ORDER BY month_key');
        $trendQuery->execute(['branch' => $branch, 'start' => $trendStart->format('Y-m-d 00:00:00')]);
        $monthlyAdmissions = [];
        foreach ($trendQuery->fetchAll() as $row) {
            $monthlyAdmissions[$row['month_key']] = (int) $row['total'];
        }
        $admissionsTrend = [];
        for ($offset = 0; $offset < 6; $offset++) {
            $month = $trendStart->modify('+' . $offset . ' months');
            $monthKey = $month->format('Y-m');
            $admissionsTrend[] = ['label' => $month->format('M'), 'total' => $monthlyAdmissions[$monthKey] ?? 0];
        }

        return [
            'academicYear' => $year['name'] ?? date('Y'),
            'studentCount' => $students,
            'classCount' => $classes,
            'sectionCount' => $sections,
            'teachingStaffCount' => $teachingStaff,
            'attendanceRate' => $attendanceRate,
            'attendanceMarked' => $attendanceMarked,
            'feeCollectedThisMonth' => (float) ($fees['collected_this_month'] ?? 0),
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
