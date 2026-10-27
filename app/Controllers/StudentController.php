<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\BranchContext;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;
use App\Core\Upload;
use App\Models\Student;
use PDOException;

final class StudentController
{
    public function index(Request $request): void
    {
        $this->requireSignIn();
        $search = trim((string) $request->input('q', ''));
        $classId = max(0, (int) $request->input('class_id', 0));
        $status = (string) $request->input('status', '');
        $perPageOption = (int) $request->input('per_page', 25);
        $perPage = in_array($perPageOption, [10, 25, 50, 100], true) ? $perPageOption : 25;
        $totalStudents = Student::countListing($search, $classId, $status);
        $totalPages = max(1, (int) ceil($totalStudents / $perPage));
        $page = min($totalPages, max(1, (int) $request->input('page', 1)));
        View::render('students/index', [
            'title' => 'Students',
            'user' => Auth::user(),
            'students' => Student::listing($search, $classId, $status, $perPage, ($page - 1) * $perPage),
            'classes' => Student::classes(),
            'search' => $search,
            'selectedClass' => $classId,
            'selectedStatus' => $status,
            'perPage' => $perPage,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalStudents' => $totalStudents,
        ]);
    }

    public function export(Request $request): void
    {
        $this->requireSignIn();
        $search = trim((string) $request->input('q', ''));
        $classId = max(0, (int) $request->input('class_id', 0));
        $status = (string) $request->input('status', '');
        if (!in_array($status, ['', 'active', 'inactive', 'graduated'], true)) $status = '';

        $sql = 'SELECT s.student_id, s.name, s.name_bn, c.name AS class_name, sec.name AS section_name, '
            . 's.roll_no, ay.name AS academic_year, s.gender, s.date_of_birth, s.birth_registration_no, '
            . 's.admission_date, s.previous_school, s.address, s.status '
            . 'FROM students s JOIN classes c ON c.id=s.class_id AND c.branch_id=s.branch_id JOIN sections sec ON sec.id=s.section_id AND sec.branch_id=s.branch_id '
            . 'JOIN academic_years ay ON ay.id=s.academic_year_id AND ay.branch_id=s.branch_id WHERE s.branch_id=:branch_id';
        $bindings = ['branch_id' => BranchContext::id()];
        if ($classId > 0) { $sql .= ' AND s.class_id=:class_id'; $bindings['class_id'] = $classId; }
        if ($status !== '') { $sql .= ' AND s.status=:status'; $bindings['status'] = $status; }
        if ($search !== '') {
            $sql .= ' AND (s.name LIKE :q1 OR s.name_bn LIKE :q2 OR s.student_id LIKE :q3 OR CAST(s.roll_no AS CHAR) LIKE :q4)';
            for ($i = 1; $i <= 4; $i++) $bindings['q' . $i] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY c.numeric_level, sec.name, s.roll_no, s.name';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($bindings);

        AuditLog::record('students.exported', 'students', null, ['count' => (int) Student::countListing($search, $classId, $status)]);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="students-' . date('Y-m-d') . '.csv"');
        header('X-Content-Type-Options: nosniff');
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Student ID', 'Name', 'Name (Bangla)', 'Class', 'Section', 'Roll', 'Academic Year', 'Gender', 'Date of Birth', 'Birth Registration No.', 'Admission Date', 'Previous School', 'Address', 'Status']);
        while ($row = $statement->fetch()) {
            fputcsv($output, array_map(static function (mixed $value): string {
                $value = (string) ($value ?? '');
                return preg_match('/^[=+@\-]/', $value) ? "'" . $value : $value;
            }, array_values($row)));
        }
        fclose($output);
        exit;
    }

    public function show(Request $request): void
    {
        $this->requireSignIn();
        $student = Student::details((int) $request->input('id', 0));
        if (!$student) {
            Toast::flash('Student record not found.', 'error');
            header('Location: ' . Url::to('/students')); exit;
        }
        $studentId = (int) $student['id'];
        View::render('students/show', [
            'title' => 'Student details',
            'user' => Auth::user(),
            'student' => $student,
            'attendanceSummary' => Student::attendanceSummary($studentId),
            'recentAttendance' => Student::recentAttendance($studentId),
            'feeHistory' => Student::feeHistory($studentId),
            'recentReports' => Student::recentReports($studentId),
            'enrollmentHistory' => Student::enrollmentHistory($studentId),
        ]);
    }

    public function create(Request $request): void
    {
        $this->requireSignIn();
        $this->renderForm(null);
    }

    public function store(Request $request): void
    {
        $this->requireSignIn();
        if (!$this->verifyCsrf($request)) { return; }
        $data = $this->validatedInput($request, true);
        if ($data === null) { header('Location: ' . Url::to('/students/new')); exit; }
        $guardian = $data['_registration_guardian'] ?? ['mode' => 'none'];
        unset($data['_registration_guardian']);
        try { $data['photo'] = Upload::store('photo', 'student-photos', ['jpg', 'jpeg', 'png', 'webp'], 5242880); }
        catch (\RuntimeException $error) { $this->rememberForm(); Toast::flash($error->getMessage(), 'error'); header('Location: ' . Url::to('/students/new')); exit; }
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $studentId = Student::create($data);
            if ($guardian['mode'] === 'existing') {
                Student::linkGuardian($studentId, (int) $guardian['guardian_id'], true);
            } elseif ($guardian['mode'] === 'new') {
                $guardianId = Student::createGuardian($guardian['data']);
                Student::linkGuardian($studentId, $guardianId, true);
            }
            $db->commit();
        } catch (PDOException $error) {
            if ($db->inTransaction()) $db->rollBack();
            Upload::delete($data['photo'] ?? null);
            $this->rememberForm();
            error_log((string) $error);
            Toast::flash('The student or guardian could not be saved. Check for a duplicate roll number or invalid details.', 'error');
            header('Location: ' . Url::to('/students/new')); exit;
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            Upload::delete($data['photo'] ?? null);
            $this->rememberForm();
            error_log((string) $error);
            Toast::flash('The student registration could not be completed. Please try again.', 'error');
            header('Location: ' . Url::to('/students/new')); exit;
        }
        AuditLog::record('student.created', 'students', $studentId);
        Toast::flash('Student admitted successfully.');
        header('Location: ' . Url::to('/students')); exit;
    }

    public function edit(Request $request): void
    {
        $this->requireSignIn();
        $student = Student::find((int) $request->input('id', 0));
        if (!$student) {
            Toast::flash('Student record not found.', 'error');
            header('Location: ' . Url::to('/students')); exit;
        }
        $this->renderForm($student);
    }

    public function update(Request $request): void
    {
        $this->requireSignIn();
        if (!$this->verifyCsrf($request)) { return; }
        $id = (int) $request->input('id', 0);
        if (!Student::find($id)) {
            Toast::flash('Student record not found.', 'error');
            header('Location: ' . Url::to('/students')); exit;
        }
        $data = $this->validatedInput($request, false);
        if ($data === null) { header('Location: ' . Url::to('/students/edit?id=' . $id)); exit; }
        try { $data['photo'] = Upload::store('photo', 'student-photos', ['jpg', 'jpeg', 'png', 'webp'], 5242880) ?? (Student::find($id)['photo'] ?? null); }
        catch (\RuntimeException $error) { Toast::flash($error->getMessage(), 'error'); header('Location: ' . Url::to('/students/edit?id=' . $id)); exit; }
        try {
            Student::update($id, $data);
        } catch (PDOException $error) {
            error_log((string) $error);
            Toast::flash('That roll number is already used in the selected class and year.', 'error');
            header('Location: ' . Url::to('/students/edit?id=' . $id)); exit;
        }
        AuditLog::record('student.updated', 'students', $id);
        Toast::flash('Student record updated.');
        header('Location: ' . Url::to('/students')); exit;
    }

    public function archive(Request $request): void
    {
        $this->requireSignIn();
        if (!$this->verifyCsrf($request)) { return; }
        Student::archive((int) $request->input('id', 0));
        AuditLog::record('student.archived', 'students', (int) $request->input('id', 0));
        Toast::flash('Student record marked inactive.');
        header('Location: ' . Url::to('/students')); exit;
    }

    private function renderForm(?array $student): void
    {
        $classes = Student::classes();
        $selectedClass = (int) ($student['class_id'] ?? ($_SESSION['_student_old']['class_id'] ?? ($classes[0]['id'] ?? 0)));
        $year = isset($student['academic_year_id']) ? Student::yearById((int) $student['academic_year_id']) : Student::currentYear();
        if ($year === null) {
            Toast::flash('There is no current academic year. Set one up before admitting students.', 'error');
        }
        View::render('students/form', [
            'title' => $student ? 'Edit student' : 'Admit student',
            'user' => Auth::user(),
            'student' => $student,
            'classes' => $classes,
            'sections' => Student::allSections(),
            'guardians' => Student::guardianOptions(),
            'year' => $year,
        ]);
        unset($_SESSION['_student_old']);
    }

    private function validatedInput(Request $request, bool $new): ?array
    {
        $name = trim((string) $request->input('name', ''));
        $nameBn = trim((string) $request->input('name_bn', ''));
        $classId = (int) $request->input('class_id', 0);
        $sectionId = (int) $request->input('section_id', 0);
        $yearId = (int) $request->input('academic_year_id', 0);
        $roll = (int) $request->input('roll_no', 0);
        $gender = (string) $request->input('gender', '');
        $dateOfBirth = trim((string) $request->input('date_of_birth', ''));
        $birthRegistrationNo = trim((string) $request->input('birth_registration_no', ''));
        $admissionDate = trim((string) $request->input('admission_date', ''));
        $previousSchool = trim((string) $request->input('previous_school', ''));
        $address = trim((string) $request->input('address', ''));
        $status = (string) $request->input('status', 'active');
        $year = Student::yearById($yearId);
        $sections = Student::sections($classId);
        $sectionIsValid = in_array($sectionId, array_map(static fn (array $item): int => (int) $item['id'], $sections), true);
        preg_match_all('/./us', $name, $nameCharacters);
        $nameLength = isset($nameCharacters[0]) ? count($nameCharacters[0]) : strlen($name);
        if ($name === '' || $nameLength > 150 || $roll < 1 || $roll > 65535 || !$year || !$sectionIsValid
            || !in_array($gender, ['', 'Male', 'Female', 'Other'], true)
            || !in_array($status, ['active', 'inactive', 'graduated'], true)
            || !$this->validDate($dateOfBirth, true) || !$this->validDate($admissionDate, false)
            || $this->textLength($nameBn) > 150 || strlen($birthRegistrationNo) > 50 || $this->textLength($previousSchool) > 190 || $this->textLength($address) > 10000) {
            $this->rememberForm();
            Toast::flash('Check the student name, class, section, year, roll number, dates, and field lengths.', 'error');
            return null;
        }
        $data = [
            'name' => $name,
            'name_bn' => $nameBn !== '' ? $nameBn : null,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'year_id' => $yearId,
            'roll_no' => $roll,
            'gender' => $gender !== '' ? $gender : null,
            'status' => $status,
            'date_of_birth' => $dateOfBirth !== '' ? $dateOfBirth : null,
            'birth_registration_no' => $birthRegistrationNo !== '' ? $birthRegistrationNo : null,
            'admission_date' => $admissionDate !== '' ? $admissionDate : null,
            'previous_school' => $previousSchool !== '' ? $previousSchool : null,
            'address' => $address !== '' ? $address : null,
        ];
        if ($new) {
            $guardianMode = (string) $request->input('guardian_mode', 'none');
            if (!in_array($guardianMode, ['none', 'existing', 'new'], true)) {
                Toast::flash('Choose a valid guardian option.', 'error'); return null;
            }
            if ($guardianMode === 'existing') {
                $guardianId = (int) $request->input('guardian_id', 0);
                if (!Student::guardianExists($guardianId)) {
                    $this->rememberForm(); Toast::flash('Choose an existing guardian record.', 'error'); return null;
                }
                $data['_registration_guardian'] = ['mode' => 'existing', 'guardian_id' => $guardianId];
            } elseif ($guardianMode === 'new') {
                $guardianName = trim((string) $request->input('guardian_name', ''));
                $guardianNameBn = trim((string) $request->input('guardian_name_bn', ''));
                $guardianRelation = (string) $request->input('guardian_relation', '');
                $guardianPhone = trim((string) $request->input('guardian_phone', ''));
                $guardianEmail = trim((string) $request->input('guardian_email', ''));
                $relations = ['Father', 'Mother', 'Guardian', 'Grandparent', 'Sibling', 'Other'];
                if ($guardianName === '' || $this->textLength($guardianName) > 150 || $this->textLength($guardianNameBn) > 150
                    || !in_array($guardianRelation, $relations, true) || $guardianPhone === '' || strlen($guardianPhone) > 20
                    || ($guardianEmail !== '' && (!filter_var($guardianEmail, FILTER_VALIDATE_EMAIL) || strlen($guardianEmail) > 190))) {
                    $this->rememberForm(); Toast::flash('Enter a guardian name, relation and valid phone; check the optional email.', 'error'); return null;
                }
                $data['_registration_guardian'] = ['mode' => 'new', 'data' => [
                    'name' => $guardianName, 'name_bn' => $guardianNameBn !== '' ? $guardianNameBn : null,
                    'relation' => $guardianRelation, 'phone' => $guardianPhone,
                    'email' => $guardianEmail !== '' ? $guardianEmail : null,
                ]];
            }
        }
        if ($new) {
            $data['student_id'] = Student::nextStudentId($year['name']);
        }
        return $data;
    }

    private function validDate(string $value, bool $notFuture): bool
    {
        if ($value === '') return true;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value && (!$notFuture || $date <= new \DateTimeImmutable('today'));
    }

    private function textLength(string $value): int
    {
        preg_match_all('/./us', $value, $characters);
        return isset($characters[0]) ? count($characters[0]) : strlen($value);
    }

    private function rememberForm(): void
    {
        $allowed = ['name', 'name_bn', 'class_id', 'section_id', 'roll_no', 'gender', 'date_of_birth', 'birth_registration_no', 'admission_date', 'previous_school', 'address', 'guardian_mode', 'guardian_id', 'guardian_name', 'guardian_name_bn', 'guardian_relation', 'guardian_phone', 'guardian_email'];
        $old = [];
        foreach ($allowed as $key) {
            if (isset($_POST[$key]) && is_scalar($_POST[$key])) $old[$key] = (string) $_POST[$key];
        }
        $_SESSION['_student_old'] = $old;
    }

    public function guardians(Request $request): void
    {
        $this->requireSignIn();
        $student = Student::details((int) $request->input('id', 0));
        if (!$student) {
            Toast::flash('Student record not found.', 'error');
            header('Location: ' . Url::to('/students')); exit;
        }
        View::render('students/guardians', [
            'title' => 'Guardian links',
            'user' => Auth::user(),
            'student' => $student,
            'linked' => Student::guardians((int) $student['id']),
            'available' => Student::unlinkedGuardians((int) $student['id']),
        ]);
    }

    public function linkGuardian(Request $request): void
    {
        $this->requireSignIn();
        if (!$this->verifyCsrf($request)) { return; }
        $studentId = (int) $request->input('student_id', 0);
        $guardianId = (int) $request->input('guardian_id', 0);
        $primary = (bool) $request->input('is_primary', false);
        if ($studentId <= 0 || $guardianId <= 0) {
            Toast::flash('Select a guardian to link.', 'error');
            header('Location: ' . Url::to('/students/guardians?id=' . $studentId)); exit;
        }
        try {
            Student::linkGuardian($studentId, $guardianId, $primary);
            Toast::flash('Guardian linked to student.');
        } catch (\Throwable $error) {
            error_log((string) $error);
            Toast::flash('Could not link guardian.', 'error');
        }
        header('Location: ' . Url::to('/students/guardians?id=' . $studentId)); exit;
    }

    public function unlinkGuardian(Request $request): void
    {
        $this->requireSignIn();
        if (!$this->verifyCsrf($request)) { return; }
        $studentId = (int) $request->input('student_id', 0);
        $guardianId = (int) $request->input('guardian_id', 0);
        Student::unlinkGuardian($studentId, $guardianId);
        Toast::flash('Guardian unlinked.');
        header('Location: ' . Url::to('/students/guardians?id=' . $studentId)); exit;
    }

    public function setPrimaryGuardian(Request $request): void
    {
        $this->requireSignIn();
        if (!$this->verifyCsrf($request)) { return; }
        $studentId = (int) $request->input('student_id', 0);
        $guardianId = (int) $request->input('guardian_id', 0);
        Student::setPrimaryGuardian($studentId, $guardianId);
        Toast::flash('Primary guardian updated.');
        header('Location: ' . Url::to('/students/guardians?id=' . $studentId)); exit;
    }

    private function verifyCsrf(Request $request): bool
    {
        if (Csrf::verify($request)) { return true; }
        http_response_code(419);
        Toast::flash('Your session expired. Please try again.', 'error');
        header('Location: ' . Url::to('/students'), true, 419); exit;
    }

    private function requireSignIn(): void
    {
        if (Auth::check() && in_array(Auth::user()['role'] ?? '', ['admin', 'superadmin'], true)) { return; }
        if (Auth::check()) {
            Toast::flash('You do not have permission to manage student records.', 'error');
            header('Location: ' . Url::to('/dashboard')); exit;
        }
        Toast::flash('Please sign in to continue.', 'error');
        header('Location: ' . Url::to('/login')); exit;
    }
}
