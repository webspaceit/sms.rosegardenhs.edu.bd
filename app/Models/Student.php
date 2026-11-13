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
            'SELECT s.id, s.student_id, s.name, s.roll_no, s.photo, s.gender, s.status, s.class_id,'
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
            'SELECT g.id, g.name, g.relation, g.phone, g.email, g.occupation, g.address, g.photo, sg.is_primary,'
            . ' (SELECT COUNT(*) FROM student_guardian sg2 WHERE sg2.guardian_id = g.id AND sg2.branch_id = g.branch_id) AS linked_students'
            . ' FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = :student_id AND sg.branch_id=:branch ORDER BY sg.is_primary DESC, g.name'
        );
        $statement->execute(['student_id' => $studentId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function primaryGuardian(int $studentId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT g.id, g.name, g.relation, g.phone, g.email, g.occupation, g.address, g.photo,'
            . ' (SELECT COUNT(*) FROM student_guardian sg2 WHERE sg2.guardian_id = g.id AND sg2.branch_id = g.branch_id) AS linked_students'
            . ' FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id = sg.branch_id'
            . ' WHERE sg.student_id = :student_id AND sg.branch_id = :branch'
            . ' ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1'
        );
        $statement->execute(['student_id' => $studentId, 'branch' => BranchContext::id()]);
        $guardian = $statement->fetch();
        return $guardian ?: null;
    }

    public static function updateGuardian(int $guardianId, array $data): void
    {
        $data['id'] = $guardianId;
        $data['branch_id'] = BranchContext::id();
        Database::connection()->prepare(
            'UPDATE guardians SET name = :name, relation = :relation, phone = :phone,'
            . ' email = :email, occupation = :occupation, address = :address, photo = :photo, updated_at = NOW()'
            . ' WHERE id = :id AND branch_id = :branch_id'
        )->execute($data);
    }

    public static function deleteGuardianLink(int $studentId, int $guardianId): void
    {
        Database::connection()->prepare(
            'DELETE FROM student_guardian WHERE student_id = :student_id AND guardian_id = :guardian_id AND branch_id = :branch'
        )->execute(['student_id' => $studentId, 'guardian_id' => $guardianId, 'branch' => BranchContext::id()]);
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
        $statement = Database::connection()->prepare('INSERT INTO guardians (name, relation, phone, email, occupation, address, photo, created_at, branch_id) VALUES (:name, :relation, :phone, :email, :occupation, :address, :photo, NOW(), :branch_id)');
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
            $where .= ' AND (s.name LIKE :name_search'
                . ' OR s.student_id LIKE :id_search OR CAST(s.roll_no AS CHAR) LIKE :roll_search)';
            $needle = '%' . $search . '%';
            $params += ['name_search' => $needle, 'id_search' => $needle, 'roll_search' => $needle];
        }
        return [$where, $params];
    }

    public static function listing(string $search = '', int $classId = 0, string $status = '', int $limit = 25, int $offset = 0, int $yearId = 0): array
    {
        $sql = 'SELECT s.id, s.student_id, s.name, s.roll_no, s.gender, s.status, s.archive_reason, s.academic_group,
                       c.name AS class_name, sec.name AS section_name, ay.name AS year_name
                FROM students s
                INNER JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id
                INNER JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id
                INNER JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id=s.branch_id
                WHERE s.branch_id=:branch_id';
        [$filters, $params] = self::listingFilters($search, $classId, $status, $yearId);
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

    public static function countListing(string $search = '', int $classId = 0, string $status = '', int $yearId = 0): int
    {
        $sql = 'SELECT COUNT(*) FROM students s'
            . ' INNER JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id'
            . ' INNER JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id'
            . ' INNER JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id=s.branch_id'
            . ' WHERE s.branch_id=:branch_id';
        [$filters, $params] = self::listingFilters($search, $classId, $status, $yearId);
        $params['branch_id'] = BranchContext::id();
        $stmt = Database::connection()->prepare($sql . $filters);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private static function listingFilters(string $search, int $classId, string $status, int $yearId = 0): array
    {
        $filters = '';
        $params = [];
        if ($search !== '') {
            $filters .= ' AND (s.name LIKE :name_search OR s.student_id LIKE :id_search OR CAST(s.roll_no AS CHAR) LIKE :roll_search)';
            $needle = '%' . $search . '%';
            $params['name_search'] = $needle;
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
        if ($yearId > 0) {
            $filters .= ' AND s.academic_year_id = :listing_year_id';
            $params['listing_year_id'] = $yearId;
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
            'SELECT s.*, c.name AS class_name, sec.name AS section_name, ay.name AS year_name, sh.name AS house_name,'
            . ' tr.name AS transport_route_name, ts.name AS transport_stop_name, hr.room_no AS hostel_room_no,'
            . ' (SELECT g.name FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_name,'
            . ' (SELECT g.relation FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_relation,'
            . ' (SELECT g.phone FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_phone,'
            . ' (SELECT g.email FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_email'
            . ', (SELECT g.occupation FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_occupation'
            . ', (SELECT g.address FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_address'
            . ', (SELECT g.photo FROM student_guardian sg JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE sg.student_id = s.id AND sg.branch_id=s.branch_id ORDER BY sg.is_primary DESC, sg.id ASC LIMIT 1) AS guardian_photo'
            . ' FROM students s JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id'
            . ' JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id'
            . ' JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id=s.branch_id'
            . ' LEFT JOIN student_houses sh ON sh.id = s.house_id'
            . ' LEFT JOIN transport_routes tr ON tr.id = s.transport_route_id'
            . ' LEFT JOIN transport_stops ts ON ts.id = s.transport_stop_id AND ts.transport_route_id = s.transport_route_id'
            . ' LEFT JOIN hostel_rooms hr ON hr.id = s.hostel_room_id'
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

    /** @return array<int, array<string, mixed>> */
    public static function documents(int $studentId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, title, file_path, created_at FROM student_documents WHERE student_id = :student_id AND branch_id = :branch ORDER BY id ASC'
        );
        $stmt->execute(['student_id' => $studentId, 'branch' => BranchContext::id()]);
        return $stmt->fetchAll();
    }

    public static function guardianById(int $guardianId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT g.*, (SELECT COUNT(*) FROM student_guardian sg WHERE sg.guardian_id = g.id AND sg.branch_id = g.branch_id) AS linked_students'
            . ' FROM guardians g WHERE g.id = :id AND g.branch_id = :branch LIMIT 1'
        );
        $stmt->execute(['id' => $guardianId, 'branch' => BranchContext::id()]);
        $guardian = $stmt->fetch();
        return $guardian ?: null;
    }

    public static function guardianLinkedToStudent(int $studentId, int $guardianId): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM student_guardian WHERE student_id = :student_id AND guardian_id = :guardian_id AND branch_id = :branch LIMIT 1');
        $stmt->execute(['student_id' => $studentId, 'guardian_id' => $guardianId, 'branch' => BranchContext::id()]);
        return (bool) $stmt->fetchColumn();
    }

    public static function document(int $studentId, int $documentId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, title, file_path FROM student_documents WHERE id = :id AND student_id = :student_id AND branch_id = :branch LIMIT 1'
        );
        $stmt->execute(['id' => $documentId, 'student_id' => $studentId, 'branch' => BranchContext::id()]);
        $document = $stmt->fetch();
        return $document ?: null;
    }

    public static function updateDocument(int $studentId, int $documentId, string $title, ?string $filePath = null): void
    {
        if ($filePath === null) {
            $stmt = Database::connection()->prepare('UPDATE student_documents SET title = :title, updated_at = NOW() WHERE id = :id AND student_id = :student_id AND branch_id = :branch');
            $stmt->execute(['title' => $title, 'id' => $documentId, 'student_id' => $studentId, 'branch' => BranchContext::id()]);
            return;
        }
        $stmt = Database::connection()->prepare('UPDATE student_documents SET title = :title, file_path = :file_path, updated_at = NOW() WHERE id = :id AND student_id = :student_id AND branch_id = :branch');
        $stmt->execute(['title' => $title, 'file_path' => $filePath, 'id' => $documentId, 'student_id' => $studentId, 'branch' => BranchContext::id()]);
    }

    public static function deleteDocument(int $studentId, int $documentId): void
    {
        Database::connection()->prepare('DELETE FROM student_documents WHERE id = :id AND student_id = :student_id AND branch_id = :branch')
            ->execute(['id' => $documentId, 'student_id' => $studentId, 'branch' => BranchContext::id()]);
    }

    public static function recentReports(int $studentId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT e.name AS exam_name, ay.name AS academic_year, ar.class_position,'
            . ' COALESCE(SUM(m.obtained), 0) AS obtained, COALESCE(SUM(m.full_marks), 0) AS full_marks'
            . ' FROM academic_reports ar LEFT JOIN academic_report_marks m ON m.academic_report_id = ar.id'
            . ' LEFT JOIN exams e ON e.id = ar.exam_id'
            . ' LEFT JOIN academic_years ay ON ay.id = e.academic_year_id'
            . ' WHERE ar.student_id = :student_id AND ar.branch_id=:branch'
            . ' GROUP BY ar.id, e.name, ay.name, ar.class_position, ar.created_at'
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
        return Database::connection()->query('SELECT id, name, numeric_level, shift FROM classes WHERE branch_id=' . BranchContext::id() . ' ORDER BY numeric_level')->fetchAll();
    }

    public static function classLevel(int $classId): ?int
    {
        $stmt = Database::connection()->prepare('SELECT numeric_level FROM classes WHERE id=:id AND branch_id=:branch LIMIT 1');
        $stmt->execute(['id' => $classId, 'branch' => BranchContext::id()]);
        $level = $stmt->fetchColumn();
        return $level === false ? null : (int) $level;
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

    public static function nextRollNumber(int $classId, int $sectionId, int $yearId): int
    {
        $statement = Database::connection()->prepare(
            'SELECT COALESCE(MAX(roll_no), 0) + 1 FROM students WHERE class_id=:class_id AND section_id=:section_id AND academic_year_id=:year_id AND branch_id=:branch'
        );
        $statement->execute([
            'class_id' => $classId,
            'section_id' => $sectionId,
            'year_id' => $yearId,
            'branch' => BranchContext::id(),
        ]);
        return (int) $statement->fetchColumn();
    }

    private const EXTRA_COLUMNS = [
        'academic_group', 'religion', 'caste', 'mobile_number', 'email', 'blood_group', 'height', 'weight',
        'measurement_date', 'medical_history', 'student_behaviour', 'current_address', 'permanent_address',
        'national_id', 'local_id', 'note', 'house_id', 'transport_route_id', 'transport_stop_id',
        'transport_fees_month', 'hostel_room_id', 'father_name', 'father_phone', 'father_occupation',
        'father_photo', 'mother_name', 'mother_phone', 'mother_occupation', 'mother_photo',
    ];

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO students (student_id, name, class_id, section_id, shift, academic_year_id, roll_no, gender, status, photo, date_of_birth, birth_registration_no, admission_date, previous_school, '
            . implode(', ', self::EXTRA_COLUMNS) . ', branch_id) VALUES (:student_id, :name, :class_id, :section_id, :shift, :year_id, :roll_no, :gender, :status, :photo, :date_of_birth, :birth_registration_no, :admission_date, :previous_school, :'
            . implode(', :', self::EXTRA_COLUMNS) . ', :branch_id)'
        );
        $stmt->execute($data + ['branch_id' => BranchContext::id()]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        if (array_key_exists('year_id', $data)) {
            $data['academic_year_id'] = $data['year_id'];
            unset($data['year_id']);
        }
        $allowed = array_merge(
            ['name', 'class_id', 'section_id', 'shift', 'academic_year_id', 'roll_no', 'gender', 'status', 'photo', 'date_of_birth', 'birth_registration_no', 'admission_date', 'previous_school'],
            self::EXTRA_COLUMNS
        );
        $data = array_intersect_key($data, array_flip($allowed));
        if ($data === []) return;
        $set = implode(', ', array_map(static fn (string $column): string => $column . ' = :' . $column, array_keys($data)));
        $stmt = Database::connection()->prepare('UPDATE students SET ' . $set . ' WHERE id = :id AND branch_id = :branch_id');
        $stmt->execute($data + ['id' => $id, 'branch_id' => BranchContext::id()]);
    }

    public static function addDocument(int $studentId, string $title, string $filePath): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO student_documents (student_id, title, file_path, created_at, branch_id) VALUES (:student_id, :title, :file_path, NOW(), :branch)');
        $stmt->execute(['student_id' => $studentId, 'title' => $title, 'file_path' => $filePath, 'branch' => BranchContext::id()]);
    }

    public static function archive(int $id, string $reason): void
    {
        $stmt = Database::connection()->prepare("UPDATE students SET status = 'inactive', archive_reason = :reason WHERE id = :id AND status = 'active' AND branch_id=:branch");
        $stmt->execute(['reason' => $reason, 'id' => $id, 'branch' => BranchContext::id()]);
    }

    /** @return array{0: int, 1: array<int, string>} Number deleted and owned upload paths to remove after commit. */
    public static function deleteMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) return [0, []];

        $database = Database::connection();
        $branchId = BranchContext::id();
        $holders = [];
        $parameters = ['branch_id' => $branchId];
        foreach ($ids as $index => $id) {
            $holder = 'student_' . $index;
            $holders[] = ':' . $holder;
            $parameters[$holder] = $id;
        }
        $in = implode(', ', $holders);

        $database->beginTransaction();
        try {
            $studentsQuery = $database->prepare('SELECT id, photo, father_photo, mother_photo FROM students WHERE branch_id = :branch_id AND id IN (' . $in . ') FOR UPDATE');
            $studentsQuery->execute($parameters);
            $students = $studentsQuery->fetchAll();
            if ($students === []) {
                $database->commit();
                return [0, []];
            }

            $validIds = array_map(static fn (array $student): int => (int) $student['id'], $students);
            $filePaths = [];
            foreach ($students as $student) {
                foreach (['photo', 'father_photo', 'mother_photo'] as $field) {
                    if (!empty($student[$field])) $filePaths[] = (string) $student[$field];
                }
            }

            $validHolders = [];
            $documentParameters = ['branch_id' => $branchId];
            foreach ($validIds as $index => $id) {
                $holder = 'document_student_' . $index;
                $validHolders[] = ':' . $holder;
                $documentParameters[$holder] = $id;
            }
            $documentsQuery = $database->prepare('SELECT file_path FROM student_documents WHERE branch_id = :branch_id AND student_id IN (' . implode(', ', $validHolders) . ')');
            $documentsQuery->execute($documentParameters);
            foreach ($documentsQuery->fetchAll(PDO::FETCH_COLUMN) as $path) {
                if (is_string($path) && $path !== '') $filePaths[] = $path;
            }

            $delete = $database->prepare('DELETE FROM students WHERE branch_id = :branch_id AND id IN (' . implode(', ', $validHolders) . ')');
            $delete->execute($documentParameters);
            $deletedCount = $delete->rowCount();
            $database->commit();

            return [$deletedCount, array_values(array_unique($filePaths))];
        } catch (\Throwable $error) {
            if ($database->inTransaction()) $database->rollBack();
            throw $error;
        }
    }
}
