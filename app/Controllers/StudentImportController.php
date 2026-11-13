<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\BranchContext;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;
use App\Models\Student;
use App\Models\AcademicGroup;

/**
 * Bulk student import from a CSV file.
 *
 * The file needs a header row. The recognised columns are name, student_id,
 * class, academic_group, section, roll_no, gender, guardian_name, guardian_phone
 * and status. Anything that cannot be read is reported back to the
 * administrator rather than silently dropped.
 */
final class StudentImportController
{
    public function show(Request $request): void
    {
        Guard::requirePermission('students.create');

        View::render('students/import', [
            'title' => 'Import students',
            'user' => Auth::user(),
            'classes' => \App\Models\Catalogue::classes(),
            'sections' => \App\Models\Catalogue::sections(),
            'report' => $_SESSION['_import_report'] ?? null,
        ]);
        unset($_SESSION['_import_report']);
    }

    public function store(Request $request): void
    {
        Guard::requirePermission('students.create');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/students/import'));
            exit;
        }

        $file = $_FILES['csv'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || (int) ($file['size'] ?? 0) > 5242880 || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            Toast::flash('Choose a valid CSV file no larger than 5 MB.', 'error');
            header('Location: ' . Url::to('/students/import'));
            exit;
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            Toast::flash('Only CSV files can be imported.', 'error');
            header('Location: ' . Url::to('/students/import'));
            exit;
        }

        try {
            $rows = $this->readCsv((string) $file['tmp_name']);
        } catch (\RuntimeException $error) {
            Toast::flash($error->getMessage(), 'error');
            header('Location: ' . Url::to('/students/import'));
            exit;
        }
        if ($rows === []) {
            Toast::flash('The file appears to be empty.', 'error');
            header('Location: ' . Url::to('/students/import'));
            exit;
        }

        $report = $this->import($rows);
        $_SESSION['_import_report'] = $report;
        AuditLog::record('students.imported', 'students', null, ['count' => (int) $report['imported']]);

        Toast::flash($report['imported'] . ' student(s) imported, ' . $report['skipped'] . ' skipped.');
        header('Location: ' . Url::to('/students/import'));
        exit;
    }

    /**
     * Read a CSV file into a list of associative rows.
     *
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        $rows = [];
        $header = null;

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === false) {
                continue;
            }
            if ($header === null) {
                $header = array_map(
                    static fn (mixed $column): string => preg_replace('/[\s-]+/', '_', strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $column)))) ?? '',
                    $line
                );
                $allowed = ['name', 'student_id', 'class', 'academic_group', 'section', 'roll_no', 'gender', 'guardian_name', 'guardian_phone', 'status'];
                if (!in_array('name', $header, true) || !in_array('class', $header, true)
                    || !in_array('section', $header, true) || !in_array('roll_no', $header, true)
                    || array_diff($header, $allowed) !== [] || count($header) !== count(array_unique($header))) {
                    fclose($handle);
                    throw new \RuntimeException('CSV headers must include name, class, section and roll_no, with only supported columns.');
                }
                continue;
            }
            if (count($rows) >= 5000) {
                fclose($handle);
                throw new \RuntimeException('A single import can contain up to 5,000 student rows.');
            }
            if (count($line) < count($header)) {
                continue;
            }
            $row = [];
            foreach ($header as $index => $column) {
                $row[$column] = trim((string) ($line[$index] ?? ''));
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Create the students a file describes.
     *
     * @param array<int, array<string, string>> $rows
     * @return array{imported: int, skipped: int, problems: array<int, string>}
     */
    private function import(array $rows): array
    {
        $db = Database::connection();
        $year = Student::currentYear();
        $yearId = (int) ($year['id'] ?? 0);
        $academicGroups = AcademicGroup::names();

        $classIds = [];
        foreach ($db->query('SELECT id, name, numeric_level FROM classes WHERE branch_id=' . BranchContext::id()) as $row) {
            $classIds[strtolower((string) $row['name'])] = [
                'id' => (int) $row['id'],
                'level' => (int) $row['numeric_level'],
            ];
        }

        $sectionIds = [];
        foreach ($db->query('SELECT s.id, c.name AS class_name, s.name AS section_name FROM sections s JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id WHERE s.branch_id=' . BranchContext::id()) as $row) {
            $sectionIds[strtolower((string) $row['class_name'] . ' ' . $row['section_name'])] = (int) $row['id'];
        }

        $imported = 0;
        $skipped = 0;
        $problems = [];

        foreach ($rows as $number => $row) {
            $line = $number + 2;
            $name = trim((string) ($row['name'] ?? ''));
            $className = strtolower(trim((string) ($row['class'] ?? '')));
            $sectionName = strtolower(trim((string) ($row['section'] ?? '')));

            if ($name === '') {
                $skipped++;
                $problems[] = 'Row ' . $line . ': no name, skipped.';
                continue;
            }

            $class = $classIds[$className] ?? null;
            $classId = (int) ($class['id'] ?? 0);
            $sectionId = $sectionIds[$className . ' ' . $sectionName] ?? 0;

            if ($classId === 0 || $sectionId === 0) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': class "' . ($row['class'] ?? '') . '" or section "' . ($row['section'] ?? '') . '" is unknown.';
                continue;
            }

            $academicGroup = trim((string) ($row['academic_group'] ?? ''));
            if ((int) ($class['level'] ?? 0) === 9 && !in_array($academicGroup, $academicGroups, true)) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': Class 9 requires academic_group to be Science, Business Studies, Humanities, or S.S.C Vocational.';
                continue;
            }
            if ((int) ($class['level'] ?? 0) !== 9) $academicGroup = '';

            $roll = (int) ($row['roll_no'] ?? 0);
            if ($roll < 1) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': roll number is missing or invalid.';
                continue;
            }

            $studentId = trim((string) ($row['student_id'] ?? ''));
            if ($studentId === '') {
                $studentId = Student::nextStudentId((string) ($year['name'] ?? date('Y')));
            }

            $gender = trim((string) ($row['gender'] ?? ''));
            $status = trim((string) ($row['status'] ?? 'active'));
            if (!in_array($gender, ['', 'Male', 'Female', 'Other'], true)) {
                $gender = '';
            }
            if (!in_array($status, ['active', 'inactive', 'graduated'], true)) {
                $status = 'active';
            }

            try {
                $db->beginTransaction();
                $statement = $db->prepare(
                    'INSERT INTO students (student_id, name, class_id, academic_group, section_id, academic_year_id, roll_no, gender, status, branch_id)'
                    . ' VALUES (:student_id, :name, :class_id, :academic_group, :section_id, :academic_year_id, :roll_no, :gender, :status, :branch_id)'
                );
                $statement->execute([
                    'student_id' => $studentId,
                    'name' => $name,
                    'class_id' => $classId,
                    'academic_group' => $academicGroup !== '' ? $academicGroup : null,
                    'section_id' => $sectionId,
                    'academic_year_id' => $yearId,
                    'roll_no' => $roll,
                    'gender' => $gender !== '' ? $gender : null,
                    'status' => $status,
                    'branch_id' => BranchContext::id(),
                ]);
                $studentRecordId = (int) $db->lastInsertId();
                $guardianName = trim((string) ($row['guardian_name'] ?? ''));
                $guardianPhone = trim((string) ($row['guardian_phone'] ?? ''));
                preg_match_all('/./us', $guardianName, $guardianCharacters);
                $guardianLength = isset($guardianCharacters[0]) ? count($guardianCharacters[0]) : strlen($guardianName);
                if (($guardianName === '') !== ($guardianPhone === '') || strlen($guardianPhone) > 20 || $guardianLength > 150) {
                    throw new \RuntimeException('Guardian name and phone must both be present and within the allowed length.');
                }
                if ($guardianName !== '') {
                    $guardian = $db->prepare('INSERT INTO guardians (name,phone,relation,created_at,branch_id) VALUES (:name,:phone,\'Guardian\',NOW(),:branch)');
                    $guardian->execute(['name' => $guardianName, 'phone' => $guardianPhone, 'branch' => BranchContext::id()]);
                    $link = $db->prepare('INSERT INTO student_guardian (student_id,guardian_id,is_primary,created_at,branch_id) VALUES (:student,:guardian,1,NOW(),:branch)');
                    $link->execute(['student' => $studentRecordId, 'guardian' => (int) $db->lastInsertId(), 'branch' => BranchContext::id()]);
                }
                $db->commit();
                $imported++;
            } catch (\Throwable $error) {
                if ($db->inTransaction()) $db->rollBack();
                $skipped++;
                $problems[] = 'Row ' . $line . ': could not be saved. Check the student ID, roll number, and guardian details.';
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'problems' => array_slice($problems, 0, 50)];
    }
}
