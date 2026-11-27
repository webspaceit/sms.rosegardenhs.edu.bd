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
use App\Core\PhoneNumber;
use App\Models\Student;
use App\Models\AcademicGroup;

/**
 * Bulk student import from a CSV file.
 *
 * The file needs a header row. The recognised columns cover the student,
 * placement, and guardian details used by the CSV template. Anything that cannot be read is reported back to the
 * administrator rather than silently dropped.
 */
final class StudentImportController
{
    public function example(Request $request): void
    {
        Guard::requirePermission('students.create');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="new-student-import-template.csv"');
        header('X-Content-Type-Options: nosniff');
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            http_response_code(500);
            return;
        }
        fputcsv($output, ['Student name', 'Class', 'Academic group', 'Section', 'Shift', 'Roll number', 'Gender', 'Date of birth', 'Birth registration number', 'Admission date', 'Current Address', 'Guardian name', 'Relation', 'Guardian phone']);
        fclose($output);
        exit;
    }

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
            Toast::flash('The CSV has no student rows yet. Add at least one student row to the template, then upload it again.', 'error');
            header('Location: ' . Url::to('/students/import'));
            exit;
        }

        $report = $this->import($rows);
        $_SESSION['_import_report'] = $report;
        AuditLog::record('students.imported', 'students', null, ['count' => (int) $report['imported']]);

        $notice = $report['imported'] . ' student(s) imported, ' . $report['skipped'] . ' skipped.';
        if ($report['roll_adjusted'] > 0) $notice .= ' ' . $report['roll_adjusted'] . ' duplicate roll number(s) reassigned to unique rolls.';
        Toast::flash($notice, ($report['skipped'] > 0 || $report['roll_adjusted'] > 0) ? 'warning' : 'success');
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
        $headerPositions = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === false) {
                continue;
            }
            if ($header === null) {
                $normalizedHeaders = array_map(
                    static fn (mixed $column): string => preg_replace('/[\s-]+/', '_', strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $column)))) ?? '',
                    $line
                );
                $aliases = [
                    'student_name' => 'name',
                    'admission_no' => 'student_id',
                    'admission_number' => 'student_id',
                    'roll_number' => 'roll_no',
                    'birth_registration_number' => 'birth_registration_no',
                    'birth_certificate_local_id' => 'birth_registration_no',
                    'relation' => 'guardian_relation',
                    'guardian_relation' => 'guardian_relation',
                    'dob' => 'date_of_birth',
                ];
                foreach ($normalizedHeaders as $index => $column) {
                    if ($column === '') continue; // Excel often leaves a trailing empty column.
                    $headerPositions[$index] = $aliases[$column] ?? $column;
                }
                $header = array_values($headerPositions);
                $allowed = ['name', 'student_id', 'class', 'academic_group', 'section', 'shift', 'roll_no', 'gender', 'date_of_birth', 'birth_registration_no', 'admission_date', 'current_address', 'guardian_name', 'guardian_relation', 'guardian_phone', 'status'];
                if (!in_array('name', $header, true) || !in_array('class', $header, true)
                    || !in_array('section', $header, true)
                    || array_diff($header, $allowed) !== [] || count($header) !== count(array_unique($header))) {
                    fclose($handle);
                    throw new \RuntimeException('CSV must include Student name, Class, and Section columns. Check that the downloaded template headers have not been renamed.');
                }
                continue;
            }
            if (count($rows) >= 5000) {
                fclose($handle);
                throw new \RuntimeException('A single import can contain up to 5,000 student rows.');
            }
            $row = [];
            foreach ($headerPositions as $index => $column) {
                $row[$column] = trim((string) ($line[$index] ?? ''));
            }
            if (count(array_filter($row, static fn (string $value): bool => $value !== '')) === 0) {
                continue;
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
     * @return array{imported: int, skipped: int, roll_adjusted: int, warnings: array<int, string>, problems: array<int, string>}
     */
    private function import(array $rows): array
    {
        $db = Database::connection();
        $year = Student::currentYear();
        $yearId = (int) ($year['id'] ?? 0);
        $academicGroups = AcademicGroup::names();

        $classIds = [];
        $classIdsByLevel = [];
        foreach ($db->query('SELECT id, name, numeric_level, shift FROM classes WHERE branch_id=' . BranchContext::id()) as $row) {
            $class = [
                'id' => (int) $row['id'],
                'level' => (int) $row['numeric_level'],
                'shift' => (string) ($row['shift'] ?? ''),
            ];
            $classIds[$this->normalizeClassName((string) $row['name'])] = $class;
            $level = $class['level'];
            if ($level === 0) $level = $this->namedClassLevel((string) $row['name']);
            if ($level > 0) $classIdsByLevel[$level] = $class;
        }
        $sectionIds = [];
        foreach ($db->query('SELECT s.id, s.class_id, s.name AS section_name FROM sections s WHERE s.branch_id=' . BranchContext::id()) as $row) {
            $sectionIds[(int) $row['class_id'] . ':' . strtolower(trim((string) $row['section_name']))] = (int) $row['id'];
        }

        $imported = 0;
        $skipped = 0;
        $rollAdjusted = 0;
        $warnings = [];
        $problems = [];

        foreach ($rows as $number => $row) {
            $line = $number + 2;
            $name = trim((string) ($row['name'] ?? ''));
            $className = $this->normalizeClassName((string) ($row['class'] ?? ''));
            $sectionName = strtolower(trim((string) ($row['section'] ?? '')));

            if ($name === '') {
                $skipped++;
                $problems[] = 'Row ' . $line . ': no name, skipped.';
                continue;
            }

            $class = $classIds[$className] ?? ($classIdsByLevel[$this->namedClassLevel((string) ($row['class'] ?? ''))] ?? null);
            $classId = (int) ($class['id'] ?? 0);
            $sectionId = $sectionIds[$classId . ':' . $sectionName] ?? 0;

            if ($classId === 0 || $sectionId === 0) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': class "' . ($row['class'] ?? '') . '" or section "' . ($row['section'] ?? '') . '" is unknown.';
                continue;
            }

            $academicGroup = trim((string) ($row['academic_group'] ?? ''));
            $classHasAcademicGroup = $this->classHasAcademicGroup($class);
            if ($classHasAcademicGroup && !in_array($academicGroup, $academicGroups, true)) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': Class 9 or 10 requires academic_group to be Science, Business Studies, Humanities, or S.S.C Vocational.';
                continue;
            }
            if (!$classHasAcademicGroup) $academicGroup = '';

            $shift = trim((string) ($row['shift'] ?? ''));
            if ($shift === '') $shift = in_array($class['shift'] ?? '', ['Morning', 'Day'], true) ? (string) $class['shift'] : 'Day';
            if (!in_array($shift, ['Morning', 'Day'], true)) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': shift must be Morning or Day.';
                continue;
            }
            $rollValue = trim((string) ($row['roll_no'] ?? ''));
            if ($rollValue !== '' && (!ctype_digit($rollValue) || (int) $rollValue < 1 || (int) $rollValue > 65535)) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': roll number must be a whole number from 1 to 65535, or left blank for automatic assignment.';
                continue;
            }
            $roll = $rollValue === ''
                ? Student::nextRollNumber($classId, $sectionId, $yearId, $shift)
                : (int) $rollValue;
            $dateOfBirth = $this->csvDate((string) ($row['date_of_birth'] ?? ''));
            if ($dateOfBirth === false || ($dateOfBirth !== null && $dateOfBirth > date('Y-m-d'))) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': date of birth must use DD-MM-YYYY and cannot be in the future.';
                continue;
            }
            $admissionDate = $this->csvDate((string) ($row['admission_date'] ?? ''));
            if ($admissionDate === false) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': admission date must use DD-MM-YYYY.';
                continue;
            }
            $birthRegistrationNo = trim((string) ($row['birth_registration_no'] ?? ''));
            if (strlen($birthRegistrationNo) > 50) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': birth registration number must be 50 characters or fewer.';
                continue;
            }
            $currentAddress = trim((string) ($row['current_address'] ?? ''));

            $studentId = Student::nextStudentId((string) ($year['name'] ?? date('Y')));
            if (strlen($studentId) > 40 || strlen($name) > 150) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': student name must be 150 characters or fewer and Student ID must be 40 characters or fewer.';
                continue;
            }

            $gender = trim((string) ($row['gender'] ?? ''));
            $status = 'active';
            if (!in_array($gender, ['', 'Male', 'Female', 'Other'], true)) {
                $gender = '';
            }
            if (!in_array($status, ['active', 'inactive', 'graduated'], true)) {
                $status = 'active';
            }

            $guardianName = trim((string) ($row['guardian_name'] ?? ''));
            $guardianPhone = trim((string) ($row['guardian_phone'] ?? ''));
            $guardianRelation = trim((string) ($row['guardian_relation'] ?? 'Guardian'));
            preg_match_all('/./us', $guardianName, $guardianCharacters);
            $guardianLength = isset($guardianCharacters[0]) ? count($guardianCharacters[0]) : strlen($guardianName);
            if ($guardianName === '' || $guardianPhone === '') {
                $skipped++;
                $problems[] = 'Row ' . $line . ': Quick admit requires both Guardian name and Guardian phone.';
                continue;
            }
            if (!PhoneNumber::isBangladeshiMobile($guardianPhone) || $guardianLength > 150) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': enter a valid Bangladesh guardian mobile number and a guardian name of 150 characters or fewer.';
                continue;
            }
            if (!in_array($guardianRelation, ['Father', 'Mother', 'Guardian', 'Grandparent', 'Sibling', 'Other'], true)) {
                $skipped++;
                $problems[] = 'Row ' . $line . ': Relation must be Father, Mother, Guardian, Grandparent, Sibling, or Other.';
                continue;
            }

            $duplicateRoll = $db->prepare('SELECT 1 FROM students WHERE class_id=:class_id AND section_id=:section_id AND academic_year_id=:year_id AND shift <=> :shift AND roll_no=:roll_no AND branch_id=:branch_id LIMIT 1');
            $duplicateRoll->execute(['class_id' => $classId, 'section_id' => $sectionId, 'year_id' => $yearId, 'shift' => $shift, 'roll_no' => $roll, 'branch_id' => BranchContext::id()]);
            if ($duplicateRoll->fetchColumn()) {
                $previousRoll = $roll;
                $roll = Student::nextRollNumber($classId, $sectionId, $yearId, $shift);
                $rollAdjusted++;
                $warnings[] = 'Row ' . $line . ': duplicate roll number ' . $previousRoll . ' was reassigned to ' . $roll . '.';
            }

            try {
                $db->beginTransaction();
                $statement = $db->prepare(
                    'INSERT INTO students (student_id, name, class_id, academic_group, section_id, shift, academic_year_id, roll_no, gender, date_of_birth, birth_registration_no, admission_date, current_address, status, branch_id)'
                    . ' VALUES (:student_id, :name, :class_id, :academic_group, :section_id, :shift, :academic_year_id, :roll_no, :gender, :date_of_birth, :birth_registration_no, :admission_date, :current_address, :status, :branch_id)'
                );
                $statement->execute([
                    'student_id' => $studentId,
                    'name' => $name,
                    'class_id' => $classId,
                    'academic_group' => $academicGroup !== '' ? $academicGroup : null,
                    'section_id' => $sectionId,
                    'shift' => $shift !== '' ? $shift : null,
                    'academic_year_id' => $yearId,
                    'roll_no' => $roll,
                    'gender' => $gender !== '' ? $gender : null,
                    'date_of_birth' => $dateOfBirth !== '' ? $dateOfBirth : null,
                    'birth_registration_no' => $birthRegistrationNo !== '' ? $birthRegistrationNo : null,
                    'admission_date' => $admissionDate ?? date('Y-m-d'),
                    'current_address' => $currentAddress !== '' ? $currentAddress : null,
                    'status' => $status,
                    'branch_id' => BranchContext::id(),
                ]);
                $studentRecordId = (int) $db->lastInsertId();
                $guardian = $db->prepare('INSERT INTO guardians (name,phone,relation,created_at,branch_id) VALUES (:name,:phone,:relation,NOW(),:branch)');
                $guardian->execute(['name' => $guardianName, 'phone' => $guardianPhone, 'relation' => $guardianRelation, 'branch' => BranchContext::id()]);
                $link = $db->prepare('INSERT INTO student_guardian (student_id,guardian_id,is_primary,created_at,branch_id) VALUES (:student,:guardian,1,NOW(),:branch)');
                $link->execute(['student' => $studentRecordId, 'guardian' => (int) $db->lastInsertId(), 'branch' => BranchContext::id()]);
                $db->commit();
                $imported++;
            } catch (\Throwable $error) {
                if ($db->inTransaction()) $db->rollBack();
                $skipped++;
                $message = strtolower($error->getMessage());
                if (str_contains($message, 'students_roll_unique')) {
                    $reason = 'roll number is already used in this class, section, shift, and academic year.';
                } else {
                    $reason = 'could not be saved. Check that the class, section, academic group, and student details match the system.';
                }
                $problems[] = 'Row ' . $line . ': ' . $reason;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'roll_adjusted' => $rollAdjusted, 'warnings' => array_slice($warnings, 0, 50), 'problems' => array_slice($problems, 0, 50)];
    }

    /** Parse the DD-MM-YYYY date format used by the Quick admit form. */
    private function csvDate(string $value): string|false|null
    {
        $value = trim($value);
        if ($value === '') return null;
        $date = \DateTimeImmutable::createFromFormat('!d-m-Y', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('d-m-Y') !== $value) {
            return false;
        }
        return $date->format('Y-m-d');
    }

    private function classHasAcademicGroup(array $class): bool
    {
        $name = strtoupper(preg_replace('/[^A-Z0-9]+/', '', (string) ($class['name'] ?? '')) ?? '');
        $name = preg_replace('/^CLASS/', '', $name) ?? $name;
        $namedLevels = [
            '1' => 1, 'ONE' => 1, '2' => 2, 'TWO' => 2, '3' => 3, 'THREE' => 3,
            '4' => 4, 'FOUR' => 4, '5' => 5, 'FIVE' => 5, '6' => 6, 'SIX' => 6,
            '7' => 7, 'SEVEN' => 7, '8' => 8, 'EIGHT' => 8, '9' => 9, 'NINE' => 9,
            '10' => 10, 'TEN' => 10,
        ];
        if (isset($namedLevels[$name])) return in_array($namedLevels[$name], [9, 10], true);
        return in_array((int) ($class['level'] ?? 0), [9, 10], true);
    }

    private function normalizeClassName(string $name): string
    {
        $name = strtolower(preg_replace('/[^a-z0-9]+/', '', trim($name)) ?? '');
        return preg_replace('/^class/', '', $name) ?? $name;
    }

    private function namedClassLevel(string $name): int
    {
        $key = $this->normalizeClassName($name);
        $levels = ['1' => 1, 'one' => 1, '2' => 2, 'two' => 2, '3' => 3, 'three' => 3, '4' => 4, 'four' => 4, '5' => 5, 'five' => 5, '6' => 6, 'six' => 6, '7' => 7, 'seven' => 7, '8' => 8, 'eight' => 8, '9' => 9, 'nine' => 9, '10' => 10, 'ten' => 10];
        return $levels[$key] ?? 0;
    }
}
