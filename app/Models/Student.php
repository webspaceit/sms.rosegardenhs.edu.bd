<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\BranchContext;
use PDO;

final class Student
{
    public static function admitCardListing(int $yearId, int $classId, string $search, int $limit, int $offset): array
    {
        [$where, $params] = self::admitCardFilters($yearId, $classId, $search);
        $sql = 'SELECT s.id, s.student_id, s.name, s.roll_no, s.photo, c.name AS class_name,'
            . ' sec.name AS section_name, ay.name AS year_name, parents.father_name, parents.mother_name'
            . ' FROM students s JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id'
            . ' JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id'
            . ' JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id=s.branch_id'
            . ' LEFT JOIN (SELECT sg.student_id,'
            . " MAX(CASE WHEN g.relation = 'Father' THEN g.name END) AS father_name,"
            . " MAX(CASE WHEN g.relation = 'Mother' THEN g.name END) AS mother_name"
            . ' FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id WHERE sg.branch_id=' . BranchContext::id() . ' GROUP BY sg.student_id) parents'
            . ' ON parents.student_id = s.id WHERE s.branch_id=' . BranchContext::id() . ' AND s.status = \'active\' AND ' . $where
            . ' ORDER BY c.numeric_level, sec.name, s.roll_no, s.name LIMIT :limit OFFSET :offset';
        $statement = Database::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public static function countAdmitCards(int $yearId, int $classId, string $search): int
    {
        [$where, $params] = self::admitCardFilters($yearId, $classId, $search);
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM students s WHERE s.branch_id=' . BranchContext::id() . ' AND s.status = \'active\' AND ' . $where
        );
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    public static function admitCardDetails(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.id, s.student_id, s.name, s.name_bn, s.roll_no, s.photo, s.gender, s.status, s.class_id,'
            . ' c.name AS class_name, sec.name AS section_name, ay.id AS academic_year_id, ay.name AS year_name,'
            . ' parents.father_name, parents.mother_name, parents.guardian_name'
            . ' FROM students s JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id'
            . ' JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id'
            . ' JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id=s.branch_id'
            . ' LEFT JOIN (SELECT sg.student_id,'
            . " MAX(CASE WHEN g.relation = 'Father' THEN g.name END) AS father_name,"
            . " MAX(CASE WHEN g.relation = 'Mother' THEN g.name END) AS mother_name,"
            . ' MAX(CASE WHEN sg.is_primary = 1 THEN g.name END) AS guardian_name'
            . ' FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id WHERE sg.branch_id=' . BranchContext::id() . ' GROUP BY sg.student_id) parents'
            . ' ON parents.student_id = s.id WHERE s.id = :id AND s.branch_id=:branch LIMIT 1'
        );
        $statement->execute(['id' => $id, 'branch' => BranchContext::id()]);
        $student = $statement->fetch();
        return $student ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function guardians(int $studentId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT g.id, g.name, g.relation, g.phone, g.email, sg.is_primary'
            . ' FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = :student_id AND sg.branch_id=:branch ORDER BY sg.is_primary DESC, g.name'
        );
        $statement->execute(['student_id' => $studentId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function guardianOptions(): array
    {
        return Database::connection()->query('SELECT id, name, relation, phone FROM guardians WHERE branch_id=' . BranchContext::id() . ' ORDER BY name')->fetchAll();
    }

    public static function guardianExists(int $guardianId): bool
    {
        $statement = Database::connection()->prepare('SELECT 1 FROM guardians WHERE id = :id AND branch_id=:branch LIMIT 1');
        $statement->execute(['id' => $guardianId, 'branch' => BranchContext::id()]);
        return (bool) $statement->fetchColumn();
    }

    /** Create a guardian record and return its id. */
    public static function createGuardian(array $data): int
    {
        $statement = Database::connection()->prepare('INSERT INTO guardians (name, name_bn, relation, phone, email, created_at, branch_id) VALUES (:name, :name_bn, :relation, :phone, :email, NOW(), :branch_id)');
        $statement->execute($data + ['branch_id' => BranchContext::id()]);
        return (int) Database::connection()->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public static function unlinkedGuardians(int $studentId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT g.id, g.name, g.relation, g.phone'
            . ' FROM guardians g'
            . ' WHERE g.branch_id=:guardian_branch AND g.id NOT IN (SELECT guardian_id FROM student_guardian WHERE student_id = :student_id AND branch_id=:link_branch)'
            . ' ORDER BY g.name'
        );
        $statement->execute(['student_id' => $studentId, 'guardian_branch' => BranchContext::id(), 'link_branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    public static function linkGuardian(int $studentId, int $guardianId, bool $primary = false): void
    {
        if ($primary) {
            Database::connection()->prepare('UPDATE student_guardian SET is_primary = 0 WHERE student_id = :student_id AND branch_id=:branch')->execute(['student_id' => $studentId, 'branch' => BranchContext::id()]);
        }
        $statement = Database::connection()->prepare(
            'INSERT INTO student_guardian (student_id, guardian_id, is_primary, created_at, branch_id)'
            . ' VALUES (:student_id, :guardian_id, :is_primary, NOW(), :branch_id)'
            . ' ON DUPLICATE KEY UPDATE is_primary = :is_primary2'
        );
        $statement->execute([
            'student_id' => $studentId,
            'guardian_id' => $guardianId,
            'is_primary' => $primary ? 1 : 0,
            'is_primary2' => $primary ? 1 : 0,
            'branch_id' => BranchContext::id(),
        ]);
    }

    public static function unlinkGuardian(int $studentId, int $guardianId): void
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM student_guardian WHERE student_id = :student_id AND guardian_id = :guardian_id AND branch_id=:branch'
        );
        $statement->execute(['student_id' => $studentId, 'guardian_id' => $guardianId, 'branch' => BranchContext::id()]);
    }

    public static function setPrimaryGuardian(int $studentId, int $guardianId): void
    {
        $pdo = Database::connection();
        $pdo->prepare('UPDATE student_guardian SET is_primary = 0 WHERE student_id = :student_id AND branch_id=:branch')->execute(['student_id' => $studentId, 'branch' => BranchContext::id()]);
        $pdo->prepare('UPDATE student_guardian SET is_primary = 1 WHERE student_id = :student_id AND guardian_id = :guardian_id AND branch_id=:branch')->execute(['student_id' => $studentId, 'guardian_id' => $guardianId, 'branch' => BranchContext::id()]);
    }

    public static function academicYears(): array
    {
        return Database::connection()->query(
            'SELECT id, name, is_current FROM academic_years WHERE branch_id=' . BranchContext::id() . ' ORDER BY is_current DESC, name DESC'
        )->fetchAll();
    }

    private static function admitCardFilters(int $yearId, int $classId, string $search): array
    {
        $where = 's.branch_id = :branch_id';
        $params = ['branch_id' => BranchContext::id()];
        if ($yearId > 0) {
            $where .= ' AND s.academic_year_id = :year_id';
            $params['year_id'] = $yearId;
        }
        if ($classId > 0) {
            $where .= ' AND s.class_id = :class_id';
            $params['class_id'] = $classId;
        }
        if ($search !== '') {
            $where .= ' AND (s.name LIKE :name_search OR s.name_bn LIKE :bangla_search'
                . ' OR s.student_id LIKE :id_search OR CAST(s.roll_no AS CHAR) LIKE :roll_search)';
            $needle = '%' . $search . '%';
            $params += ['name_search' => $needle, 'bangla_search' => $needle, 'id_search' => $needle, 'roll_search' => $needle];
        }
        return [$where, $params];
    }

    public static function listing(string $search = '', int $classId = 0, string $status = '', int $limit = 25, int $offset = 0): array
    {
        $sql = 'SELECT s.id, s.student_id, s.name, s.name_bn, s.roll_no, s.gender, s.status,
                       c.name AS class_name, sec.name AS section_name, ay.name AS year_name
                FROM students s
                INNER JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id
                INNER JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id
                INNER JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id=s.branch_id
                WHERE s.branch_id=:branch_id';
        [$filters, $params] = self::listingFilters($search, $classId, $status);
        $params['branch_id'] = BranchContext::id();
        $sql .= $filters . ' ORDER BY c.numeric_level, sec.name, s.roll_no, s.name LIMIT :limit OFFSET :offset';
        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countListing(string $search = '', int $classId = 0, string $status = ''): int
    {
        $sql = 'SELECT COUNT(*) FROM students s'
            . ' INNER JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id'
            . ' INNER JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id'
            . ' INNER JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id=s.branch_id'
            . ' WHERE s.branch_id=:branch_id';
        [$filters, $params] = self::listingFilters($search, $classId, $status);
        $params['branch_id'] = BranchContext::id();
        $stmt = Database::connection()->prepare($sql . $filters);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private static function listingFilters(string $search, int $classId, string $status): array
    {
        $filters = '';
        $params = [];
        if ($search !== '') {
            $filters .= ' AND (s.name LIKE :name_search OR s.name_bn LIKE :bangla_search OR s.student_id LIKE :id_search OR CAST(s.roll_no AS CHAR) LIKE :roll_search)';
            $needle = '%' . $search . '%';
            $params['name_search'] = $needle;
            $params['bangla_search'] = $needle;
            $params['id_search'] = $needle;
            $params['roll_search'] = $needle;
        }
        if ($classId > 0) {
            $filters .= ' AND s.class_id = :class_id';
            $params['class_id'] = $classId;
        }
        if (in_array($status, ['active', 'inactive', 'graduated'], true)) {
            $filters .= ' AND s.status = :status';
            $params['status'] = $status;
        }
        return [$filters, $params];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM students WHERE id = :id AND branch_id=:branch LIMIT 1');
        $stmt->execute(['id' => $id, 'branch' => BranchContext::id()]);
        $student = $stmt->fetch();
        return $student ?: null;
    }

    public static function details(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.*, c.name AS class_name, sec.name AS section_name, ay.name AS year_name,'
            . ' (SELECT g.name FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_name,'
            . ' (SELECT g.relation FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_relation,'
            . ' (SELECT g.phone FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_phone,'
            . ' (SELECT g.email FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_email'
            . ' FROM students s JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id'
            . ' JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id'
            . ' JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id=s.branch_id'
            . ' WHERE s.id = :id AND s.branch_id=:branch LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'branch' => BranchContext::id()]);
        $student = $stmt->fetch();
        return $student ?: null;
    }

    public static function attendanceSummary(int $studentId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) AS total,"
            . " COALESCE(SUM(status IN ('present', 'late', 'half_day')), 0) AS attended,"
            . " COALESCE(SUM(status = 'absent'), 0) AS absent,"
            . " COALESCE(SUM(status = 'late'), 0) AS late"
            . ' FROM attendances WHERE student_id = :student_id AND branch_id=:branch'
        );
        $stmt->execute(['student_id' => $studentId, 'branch' => BranchContext::id()]);
        return $stmt->fetch() ?: ['total' => 0, 'attended' => 0, 'absent' => 0, 'late' => 0];
    }

    public static function recentAttendance(int $studentId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT date, status, remarks FROM attendances WHERE student_id = :student_id AND branch_id=:branch ORDER BY date DESC LIMIT 8'
        );
        $stmt->execute(['student_id' => $studentId, 'branch' => BranchContext::id()]);
        return $stmt->fetchAll();
    }

    public static function feeHistory(int $studentId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT fc.invoice_no, fc.amount, fc.status, fc.due_date, fc.paid_on, h.name AS fee_head'
            . ' FROM fee_collections fc JOIN fee_heads h ON h.id = fc.fee_head_id'
            . ' WHERE fc.student_id = :student_id AND fc.branch_id=:branch ORDER BY fc.id DESC LIMIT 8'
        );
        $stmt->execute(['student_id' => $studentId, 'branch' => BranchContext::id()]);
        return $stmt->fetchAll();
    }

    public static function recentReports(int $studentId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(e.name, ar.exam_name) AS exam_name, ar.academic_year, ar.class_position,'
            . ' COALESCE(SUM(m.obtained), 0) AS obtained, COALESCE(SUM(m.full_marks), 0) AS full_marks'
            . ' FROM academic_reports ar LEFT JOIN academic_report_marks m ON m.academic_report_id = ar.id'
            . ' LEFT JOIN exams e ON e.id = ar.exam_id'
            . ' WHERE ar.student_id = :student_id AND ar.branch_id=:branch'
            . ' GROUP BY ar.id, ar.exam_name, ar.academic_year, ar.class_position, ar.created_at'
            . ' ORDER BY ar.created_at DESC, ar.id DESC LIMIT 6'
        );
        $stmt->execute(['student_id' => $studentId, 'branch' => BranchContext::id()]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function enrollmentHistory(int $studentId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT ay.name AS year_name,c.name AS class_name,sec.name AS section_name,h.roll_no,h.status,h.recorded_at '
            . 'FROM student_enrollment_history h JOIN academic_years ay ON ay.id=h.academic_year_id '
            . 'JOIN classes c ON c.id=h.class_id JOIN sections sec ON sec.id=h.section_id '
            . 'WHERE h.student_id=:id AND h.branch_id=:branch ORDER BY ay.start_date DESC,h.id DESC'
        );
        $statement->execute(['id' => $studentId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    public static function classes(): array
    {
        return Database::connection()->query('SELECT id, name, numeric_level FROM classes WHERE branch_id=' . BranchContext::id() . ' ORDER BY numeric_level')->fetchAll();
    }

    public static function sections(int $classId): array
    {
        $stmt = Database::connection()->prepare('SELECT id, name FROM sections WHERE class_id = :class_id AND branch_id=:branch ORDER BY name');
        $stmt->execute(['class_id' => $classId, 'branch' => BranchContext::id()]);
        return $stmt->fetchAll();
    }

    public static function allSections(): array
    {
        return Database::connection()->query('SELECT sections.id, sections.class_id, sections.name, classes.name AS class_name FROM sections INNER JOIN classes ON classes.id = sections.class_id AND classes.branch_id=sections.branch_id WHERE sections.branch_id=' . BranchContext::id() . ' ORDER BY classes.numeric_level, sections.name')->fetchAll();
    }

    public static function currentYear(): ?array
    {
        $year = Database::connection()->query('SELECT id, name FROM academic_years WHERE is_current = 1 AND branch_id=' . BranchContext::id() . ' ORDER BY id DESC LIMIT 1')->fetch();
        return $year ?: null;
    }

    public static function yearById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name FROM academic_years WHERE id = :id AND branch_id=:branch LIMIT 1');
        $stmt->execute(['id' => $id, 'branch' => BranchContext::id()]);
        $year = $stmt->fetch();
        return $year ?: null;
    }

    public static function nextStudentId(string $year): string
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) + 1 FROM students WHERE academic_year_id = (SELECT id FROM academic_years WHERE name = :name AND branch_id=:year_branch LIMIT 1) AND branch_id=:student_branch');
        $stmt->execute(['name' => $year, 'year_branch' => BranchContext::id(), 'student_branch' => BranchContext::id()]);
        return sprintf('STU-%s-%03d', $year, (int) $stmt->fetchColumn());
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO students (student_id, name, name_bn, class_id, section_id, academic_year_id, roll_no, gender, status, photo, date_of_birth, birth_registration_no, admission_date, previous_school, address, branch_id) VALUES (:student_id, :name, :name_bn, :class_id, :section_id, :year_id, :roll_no, :gender, :status, :photo, :date_of_birth, :birth_registration_no, :admission_date, :previous_school, :address, :branch_id)');
        $stmt->execute($data + ['branch_id' => BranchContext::id()]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = Database::connection()->prepare('UPDATE students SET name = :name, name_bn = :name_bn, class_id = :class_id, section_id = :section_id, academic_year_id = :year_id, roll_no = :roll_no, gender = :gender, status = :status, photo = :photo, date_of_birth = :date_of_birth, birth_registration_no = :birth_registration_no, admission_date = :admission_date, previous_school = :previous_school, address = :address WHERE id = :id AND branch_id=:branch_id');
        $stmt->execute($data + ['branch_id' => BranchContext::id()]);
    }

    public static function archive(int $id): void
    {
        $stmt = Database::connection()->prepare("UPDATE students SET status = 'inactive' WHERE id = :id AND status = 'active' AND branch_id=:branch");
        $stmt->execute(['id' => $id, 'branch' => BranchContext::id()]);
    }
}
