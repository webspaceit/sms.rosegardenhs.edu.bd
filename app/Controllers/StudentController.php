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
use App\Core\Upload;
use App\Models\Catalogue;
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
            'canDeleteStudents' => Guard::can('students.edit'),
        ]);
    }

    public function export(Request $request): void
    {
        $this->requireSignIn();
        $search = trim((string) $request->input('q', ''));
        $classId = max(0, (int) $request->input('class_id', 0));
        $status = (string) $request->input('status', '');
        if (!in_array($status, ['', 'active', 'inactive', 'graduated'], true)) $status = '';

        $sql = 'SELECT s.student_id, s.name, c.name AS class_name, sec.name AS section_name, '
            . 's.roll_no, ay.name AS academic_year, s.gender, s.date_of_birth, s.birth_registration_no, '
            . 's.admission_date, s.address '
            . 'FROM students s JOIN classes c ON c.id=s.class_id AND c.branch_id=s.branch_id JOIN sections sec ON sec.id=s.section_id AND sec.branch_id=s.branch_id '
            . 'JOIN academic_years ay ON ay.id=s.academic_year_id AND ay.branch_id=s.branch_id WHERE s.branch_id=:branch_id';
        $bindings = ['branch_id' => BranchContext::id()];
        if ($classId > 0) { $sql .= ' AND s.class_id=:class_id'; $bindings['class_id'] = $classId; }
        if ($status !== '') { $sql .= ' AND s.status=:status'; $bindings['status'] = $status; }
        if ($search !== '') {
            $sql .= ' AND (s.name LIKE :q1 OR s.student_id LIKE :q2 OR CAST(s.roll_no AS CHAR) LIKE :q3)';
            for ($i = 1; $i <= 3; $i++) $bindings['q' . $i] = '%' . $search . '%';
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
        fputcsv($output, ['Student ID', 'Name', 'Class', 'Section', 'Roll', 'Academic Year', 'Gender', 'Date of Birth', 'Birth Registration No.', 'Admission Date', 'Address']);
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
            'title' => 'Student details - ' . (string) $student['name'],
            'user' => Auth::user(),
            'student' => $student,
            'attendanceSummary' => Student::attendanceSummary($studentId),
            'recentAttendance' => Student::recentAttendance($studentId),
            'feeHistory' => Student::feeHistory($studentId),
            'documents' => Student::documents($studentId),
            'recentReports' => Student::recentReports($studentId),
            'enrollmentHistory' => Student::enrollmentHistory($studentId),
        ]);
    }

    public function create(Request $request): void
    {
        $this->requireSignIn();
        $this->renderForm(null);
    }

    public function quickCreate(Request $request): void
    {
        $this->requireSignIn();
        $classes = Student::classes();
        $year = Student::currentYear();
        if ($year === null) {
            Toast::flash('Set a current academic year before admitting students.', 'error');
            header('Location: ' . Url::to('/academics'));
            exit;
        }

        $sections = Student::allSections();
        foreach ($sections as &$section) {
            $section['next_roll'] = Student::nextRollNumber((int) $section['class_id'], (int) $section['id'], (int) $year['id']);
        }
        unset($section);

        View::render('students/quick-form', [
            'title' => 'Quick student admission',
            'user' => Auth::user(),
            'classes' => $classes,
            'sections' => $sections,
            'year' => $year,
            'nextStudentId' => Student::nextStudentId($year['name']),
        ]);
        unset($_SESSION['_student_old']);
    }

    public function store(Request $request): void
    {
        $this->requireSignIn();
        if (!$this->verifyCsrf($request)) { return; }
        $quickAdmission = $request->input('_quick_admission') === '1';
        $formUrl = $quickAdmission ? '/students/quick-admit' : '/students/new';
        $data = $this->validatedInput($request, true);
        if ($data === null) { header('Location: ' . Url::to($formUrl)); exit; }
        $guardian = $data['_registration_guardian'] ?? ['mode' => 'none'];
        unset($data['_registration_guardian']);

        $photoExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $documentExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp', 'mp4'];
        $uploaded = [];
        try {
            $data['photo'] = Upload::store('photo', 'student-photos', $photoExtensions, 5242880);
            $data['father_photo'] = Upload::store('father_photo', 'student-photos', $photoExtensions, 5242880);
            $data['mother_photo'] = Upload::store('mother_photo', 'student-photos', $photoExtensions, 5242880);
            if ($guardian['mode'] === 'new') {
                $guardian['data']['photo'] = Upload::store('guardian_photo', 'student-photos', $photoExtensions, 5242880);
            }
            foreach ([$data['photo'] ?? null, $data['father_photo'] ?? null, $data['mother_photo'] ?? null, $guardian['data']['photo'] ?? null] as $path) {
                if ($path !== null) $uploaded[] = $path;
            }
        } catch (\RuntimeException $error) {
            foreach ($uploaded as $path) Upload::delete($path);
            $this->rememberForm();
            Toast::flash($error->getMessage(), 'error');
            header('Location: ' . Url::to($formUrl)); exit;
        }

        $documentPaths = [];
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
            $documentCount = max(1, min(20, (int) $request->input('document_count', 1)));
            for ($i = 0; $i < $documentCount; $i++) {
                $title = trim((string) $request->input('document_title_' . $i, ''));
                try {
                    $path = Upload::store('document_file_' . $i, 'student-documents', $documentExtensions, 10485760);
                } catch (\RuntimeException $error) {
                    throw new \RuntimeException('Document ' . ($i + 1) . ': ' . $error->getMessage());
                }
                if ($path !== null) {
                    if ($title !== '') {
                        Student::addDocument($studentId, $title, $path);
                        $documentPaths[] = $path;
                    } else {
                        Upload::delete($path);
                    }
                }
            }
            $db->commit();
        } catch (PDOException $error) {
            if ($db->inTransaction()) $db->rollBack();
            foreach (array_merge($uploaded, $documentPaths) as $path) Upload::delete($path);
            $this->rememberForm();
            error_log((string) $error);
            Toast::flash('The student or guardian could not be saved. Check for a duplicate roll number or invalid details.', 'error');
            header('Location: ' . Url::to($formUrl)); exit;
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            foreach (array_merge($uploaded, $documentPaths) as $path) Upload::delete($path);
            $this->rememberForm();
            error_log((string) $error);
            Toast::flash($error instanceof \RuntimeException ? $error->getMessage() : 'The student registration could not be completed. Please try again.', 'error');
            header('Location: ' . Url::to($formUrl)); exit;
        }
        AuditLog::record('student.created', 'students', $studentId);
        Toast::flash($quickAdmission ? 'Quick admission complete. You can add the remaining details from the student profile.' : 'Student admitted successfully.');
        header('Location: ' . Url::to($quickAdmission ? '/students/view?id=' . $studentId : '/students')); exit;
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
        $existing = Student::find($id);
        $oldPaths = [];
        $newPaths = [];
        $db = Database::connection();
        try {
            foreach (['photo' => 'student-photos', 'father_photo' => 'student-photos', 'mother_photo' => 'student-photos'] as $field => $directory) {
                $oldPath = $existing[$field] ?? null;
                $newPath = Upload::store($field, $directory, ['jpg', 'jpeg', 'png', 'webp'], 5242880);
                if ($newPath !== null) {
                    $data[$field] = $newPath;
                    $newPaths[] = $newPath;
                    if ($oldPath) $oldPaths[] = $oldPath;
                } else {
                    $data[$field] = $oldPath;
                }
            }

            $guardianId = (int) $request->input('guardian_id', 0);
            $guardianMode = (string) $request->input('guardian_mode', $guardianId > 0 ? 'existing' : 'none');
            $guardianAction = (string) $request->input('guardian_action', 'none');
            $createGuardian = $request->input('create_guardian') === '1';
            $guardianToReplaceId = 0;
            $guardian = null;
            if ($guardianId > 0) {
                if (!Student::guardianLinkedToStudent($id, $guardianId)) {
                    throw new \RuntimeException('The selected guardian is not linked to this student.');
                }
                $guardian = Student::guardianById($guardianId);
                if ($guardian === null) throw new \RuntimeException('The selected guardian could not be loaded.');
            }
            if ($createGuardian && ($guardianAction === 'update' || $guardianAction === 'unlink')) {
                throw new \RuntimeException('Choose one guardian action at a time.');
            }
            $guardianFields = null;
            $guardianPhoto = null;
            if ($createGuardian) {
                $guardianPhoto = Upload::store('guardian_photo', 'student-photos', ['jpg', 'jpeg', 'png', 'webp'], 5242880);
                if ($guardianPhoto !== null) $newPaths[] = $guardianPhoto;
                $guardianFields = $this->validatedGuardian($request, null);
                $guardianFields['photo'] = $guardianPhoto;
                $guardianToReplaceId = $guardianId;
            } elseif ($guardianAction === 'unlink') {
                if ($guardian === null) throw new \RuntimeException('The linked guardian could not be found.');
                $guardianToReplaceId = $guardianId;
            } elseif ($guardianAction === 'update') {
                if ($guardian === null) throw new \RuntimeException('The linked guardian could not be found.');
                $guardianPhoto = Upload::store('guardian_photo', 'student-photos', ['jpg', 'jpeg', 'png', 'webp'], 5242880);
                if ($guardianPhoto !== null) $newPaths[] = $guardianPhoto;
                $candidate = $this->validatedGuardian($request, $guardian);
                $candidate['photo'] = $guardianPhoto ?? ($guardian['photo'] ?? null);
                if ($this->guardianDataChanged($guardian, $candidate) || $guardianPhoto !== null) {
                    if ((int) ($guardian['linked_students'] ?? 1) > 1) {
                        $guardianFields = $candidate;
                        $guardianFields['photo'] = $guardianPhoto;
                        $guardianToReplaceId = $guardianId;
                    } else {
                        $guardianFields = $candidate;
                        if ($guardianPhoto !== null && !empty($guardian['photo'])) $oldPaths[] = (string) $guardian['photo'];
                    }
                }
            } elseif ($guardianAction !== 'none') {
                throw new \RuntimeException('Choose a valid guardian action.');
            }

            $documentOperations = [];
            $removedDocumentIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('document_remove_ids', [])))));
            foreach ($removedDocumentIds as $documentId) {
                $current = Student::document($id, $documentId);
                if ($current === null) throw new \RuntimeException('A document selected for removal no longer belongs to this student. Reload and try again.');
                $documentOperations[] = ['kind' => 'delete', 'id' => $documentId, 'old_path' => $current['file_path']];
            }
            $documentCount = max(0, min(20, (int) $request->input('document_count', 0)));
            for ($i = 0; $i < $documentCount; $i++) {
                $documentId = (int) $request->input('document_id_' . $i, 0);
                $title = trim((string) $request->input('document_title_' . $i, ''));
                $file = Upload::store('document_file_' . $i, 'student-documents', ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp', 'mp4'], 10485760);
                if ($file !== null) $newPaths[] = $file;
                if ($documentId > 0) {
                    $current = Student::document($id, $documentId);
                    if ($current === null) throw new \RuntimeException('A document on this form no longer belongs to this student. Reload and try again.');
                    if ($file !== null || ($title !== '' && $title !== (string) $current['title'])) {
                        $documentOperations[] = ['kind' => 'update', 'id' => $documentId, 'title' => $title !== '' ? $title : (string) $current['title'], 'path' => $file, 'old_path' => $current['file_path']];
                    }
                } elseif ($file !== null && $title !== '') {
                    $documentOperations[] = ['kind' => 'add', 'title' => $title, 'path' => $file];
                } elseif ($file !== null) {
                    throw new \RuntimeException('Enter a title for every uploaded document.');
                }
            }

            $db->beginTransaction();
            Student::update($id, $data);
            if ($guardianFields !== null) {
                if ($guardianAction === 'update' && $guardianId > 0) {
                    Student::updateGuardian($guardianId, $guardianFields);
                } else {
                    $newGuardianId = Student::createGuardian($guardianFields);
                    if ($guardianToReplaceId > 0) Student::deleteGuardianLink($id, $guardianToReplaceId);
                    Student::linkGuardian($id, $newGuardianId, true);
                }
            } elseif ($guardianToReplaceId > 0) {
                Student::deleteGuardianLink($id, $guardianToReplaceId);
            }
            foreach ($documentOperations as $operation) {
                if ($operation['kind'] === 'delete') {
                    Student::deleteDocument($id, (int) $operation['id']);
                    $oldPaths[] = (string) $operation['old_path'];
                } elseif ($operation['kind'] === 'update') {
                    Student::updateDocument($id, (int) $operation['id'], (string) $operation['title'], $operation['path']);
                    if ($operation['path'] !== null) $oldPaths[] = (string) $operation['old_path'];
                } else {
                    Student::addDocument($id, (string) $operation['title'], (string) $operation['path']);
                }
            }
            $db->commit();
            foreach (array_unique($oldPaths) as $path) Upload::delete($path);
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            foreach (array_unique($newPaths) as $path) Upload::delete($path);
            if ($error instanceof PDOException) {
                error_log((string) $error);
                $isDuplicateKey = (int) ($error->errorInfo[1] ?? 0) === 1062;
                Toast::flash($isDuplicateKey ? 'That roll number is already used in the selected class and year.' : 'The student record could not be updated. Please try again or contact the administrator.', 'error');
            } else {
                Toast::flash($error->getMessage(), 'error');
            }
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

    public function deleteSelected(Request $request): void
    {
        $this->requireSignIn();
        if (!$this->verifyCsrf($request)) { return; }
        Guard::requirePermission('students.edit');

        $rawIds = $request->input('student_ids', []);
        if (!is_array($rawIds)) $rawIds = [];
        $ids = [];
        foreach ($rawIds as $rawId) {
            if (is_scalar($rawId) && filter_var((string) $rawId, FILTER_VALIDATE_INT) !== false) {
                $id = (int) $rawId;
                if ($id > 0) $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids === [] || count($ids) > 100) {
            Toast::flash($ids === [] ? 'Select at least one student to delete.' : 'Select no more than 100 students at a time.', 'error');
            header('Location: ' . Url::to('/students')); exit;
        }

        try {
            [$deletedCount, $files] = Student::deleteMany($ids);
            foreach ($files as $file) Upload::delete($file);
            if ($deletedCount > 0) {
                AuditLog::record('students.deleted', 'students', null, ['count' => $deletedCount]);
                Toast::flash($deletedCount . ' student ' . ($deletedCount === 1 ? 'record was' : 'records were') . ' permanently deleted with linked records.');
            } else {
                Toast::flash('No selected student records were found in this campus.', 'error');
            }
        } catch (\Throwable $error) {
            error_log((string) $error);
            Toast::flash('The selected student records could not be deleted. No changes were saved.', 'error');
        }

        $search = trim((string) $request->input('q', ''));
        $classId = max(0, (int) $request->input('class_id', 0));
        $status = (string) $request->input('status', '');
        if (!in_array($status, ['', 'active', 'inactive', 'graduated'], true)) $status = '';
        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) $perPage = 25;
        $page = max(1, (int) $request->input('page', 1));
        $query = http_build_query(['q' => $search, 'class_id' => $classId, 'status' => $status, 'per_page' => $perPage, 'page' => $page]);
        header('Location: ' . Url::to('/students?' . $query)); exit;
    }

    private function renderForm(?array $student): void
    {
        $classes = Student::classes();
        $studentId = (int) ($student['id'] ?? 0);
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
            'linkedGuardians' => $studentId > 0 ? Student::guardians($studentId) : [],
            'primaryGuardian' => $studentId > 0 ? Student::primaryGuardian($studentId) : null,
            'documents' => $studentId > 0 ? Student::documents($studentId) : [],
            'year' => $year,
            'houses' => Catalogue::houses(),
            'transportRoutes' => Catalogue::transportRoutes(),
            'transportStops' => Catalogue::transportStops(),
            'hostelRooms' => Catalogue::hostelRooms(),
            'nextStudentId' => $student ? ($student['student_id'] ?? '') : Student::nextStudentId($year['name'] ?? date('Y')),
            'enableTransport' => SchoolSettingController::flag('enable_transport_details'),
            'enableHostel' => SchoolSettingController::flag('enable_hostel_details'),
        ]);
        unset($_SESSION['_student_old']);
    }

    private function validatedInput(Request $request, bool $new): ?array
    {
        $existing = $new ? null : Student::find((int) $request->input('id', 0));
        if (!$new && $existing === null) {
            Toast::flash('Student record not found.', 'error');
            return null;
        }
        $value = static fn (string $key, mixed $default = ''): mixed => $request->input($key, $existing[$key] ?? $default);
        $provided = static fn (string $key): bool => array_key_exists($key, $_POST) || array_key_exists($key, $_GET);
        $name = trim((string) $value('name'));
        $classId = (int) $value('class_id', 0);
        $sectionId = (int) $value('section_id', 0);
        $shift = (string) $value('shift');
        $yearId = (int) $value('academic_year_id', 0);
        $roll = (int) $value('roll_no', 0);
        $gender = (string) $value('gender');
        $dateOfBirth = trim((string) $value('date_of_birth'));
        $birthRegistrationNo = trim((string) $value('birth_registration_no'));
        $admissionDate = trim((string) $value('admission_date'));
        $previousSchool = trim((string) $value('previous_school'));
        $address = trim((string) $value('address'));
        $religion = trim((string) $value('religion'));
        $caste = trim((string) $value('caste'));
        $mobile = trim((string) $value('mobile_number'));
        $email = trim((string) $value('email'));
        $bloodGroup = trim((string) $value('blood_group'));
        $height = trim((string) $value('height'));
        $weight = trim((string) $value('weight'));
        $measurementDate = trim((string) $value('measurement_date'));
        $medicalHistory = trim((string) $value('medical_history'));
        $studentBehaviour = trim((string) $value('student_behaviour'));
        $currentAddress = trim((string) $value('current_address'));
        $permanentAddress = trim((string) $value('permanent_address'));
        $nationalId = trim((string) $value('national_id'));
        $localId = trim((string) $value('local_id'));
        $note = trim((string) $value('note'));
        $houseId = (int) $value('house_id', 0);
        $transportRouteId = (int) $value('transport_route_id', 0);
        $transportStopId = (int) $value('transport_stop_id', 0);
        $transportFeesMonth = trim((string) $value('transport_fees_month'));
        $hostelRoomId = (int) $value('hostel_room_id', 0);
        $fatherName = trim((string) $value('father_name', $existing['father_name'] ?? ''));
        $fatherPhone = trim((string) $value('father_phone', $existing['father_phone'] ?? ''));
        $fatherOccupation = trim((string) $value('father_occupation', $existing['father_occupation'] ?? ''));
        $motherName = trim((string) $value('mother_name', $existing['mother_name'] ?? ''));
        $motherPhone = trim((string) $value('mother_phone', $existing['mother_phone'] ?? ''));
        $motherOccupation = trim((string) $value('mother_occupation', $existing['mother_occupation'] ?? ''));
        $status = (string) $value('status', $existing['status'] ?? 'active');
        $enableTransport = SchoolSettingController::flag('enable_transport_details');
        $enableHostel = SchoolSettingController::flag('enable_hostel_details');
        $year = Student::yearById($yearId);
        $sections = Student::sections($classId);
        $sectionIsValid = in_array($sectionId, array_map(static fn (array $item): int => (int) $item['id'], $sections), true);
        preg_match_all('/./us', $name, $nameCharacters);
        $nameLength = isset($nameCharacters[0]) ? count($nameCharacters[0]) : strlen($name);
        $validationIssues = [];
        if ($name === '' || $nameLength > 150) $validationIssues[] = 'student name (required, up to 150 characters)';
        if (!$year || !$sectionIsValid) $validationIssues[] = 'class, section, and academic year';
        if ($roll < 1 || $roll > 65535) $validationIssues[] = 'roll number (1–65535)';
        if (!in_array($gender, ['', 'Male', 'Female', 'Other'], true)) $validationIssues[] = 'gender';
        if (!in_array($shift, ['', 'Morning', 'Day'], true)) $validationIssues[] = 'shift';
        if (!in_array($status, ['active', 'inactive', 'graduated'], true)) $validationIssues[] = 'student status';
        if (!$this->validDate($dateOfBirth, true)) $validationIssues[] = 'date of birth (must be valid and not in the future)';
        if (!$this->validDate($admissionDate, false)) $validationIssues[] = 'admission date';
        if (!$this->validDate($measurementDate, false)) $validationIssues[] = 'measurement date';
        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) $validationIssues[] = 'email address';
        if ($height !== '' && !is_numeric($height)) $validationIssues[] = 'height (must be numeric)';
        if ($weight !== '' && !is_numeric($weight)) $validationIssues[] = 'weight (must be numeric)';
        if (strlen($birthRegistrationNo) > 50) $validationIssues[] = 'birth registration number (up to 50 characters)';
        if ($this->textLength($previousSchool) > 190) $validationIssues[] = 'previous school (up to 190 characters)';
        if ($this->textLength($address) > 10000) $validationIssues[] = 'address (up to 10,000 characters)';
        if ($this->textLength($studentBehaviour) > 5000) $validationIssues[] = 'student behaviour (up to 5,000 characters)';
        if ($validationIssues !== []) {
            $this->rememberForm();
            Toast::flash('Please check: ' . implode('; ', $validationIssues) . '.', 'error');
            return null;
        }
        $data = [
            'name' => $name,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'shift' => $shift !== '' ? $shift : null,
            'year_id' => $yearId,
            'roll_no' => $roll,
            'gender' => $gender !== '' ? $gender : null,
            'status' => $status,
            'date_of_birth' => $dateOfBirth !== '' ? $dateOfBirth : null,
            'birth_registration_no' => $birthRegistrationNo !== '' ? $birthRegistrationNo : null,
            'admission_date' => $admissionDate !== '' ? $admissionDate : null,
            'previous_school' => $previousSchool !== '' ? $previousSchool : null,
            'address' => $address !== '' ? $address : null,
            'religion' => $religion !== '' ? $religion : null,
            'caste' => $caste !== '' ? $caste : null,
            'mobile_number' => $mobile !== '' ? $mobile : null,
            'email' => $email !== '' ? $email : null,
            'blood_group' => $bloodGroup !== '' ? $bloodGroup : null,
            'height' => $height !== '' ? $height : null,
            'weight' => $weight !== '' ? $weight : null,
            'measurement_date' => $measurementDate !== '' ? $measurementDate : null,
            'medical_history' => $medicalHistory !== '' ? $medicalHistory : null,
            'student_behaviour' => $studentBehaviour !== '' ? $studentBehaviour : null,
            'current_address' => $currentAddress !== '' ? $currentAddress : null,
            'permanent_address' => $permanentAddress !== '' ? $permanentAddress : null,
            'national_id' => $nationalId !== '' ? $nationalId : null,
            'local_id' => $localId !== '' ? $localId : null,
            'note' => $note !== '' ? $note : null,
            'house_id' => $houseId > 0 ? $houseId : null,
            'transport_route_id' => $transportRouteId > 0 ? $transportRouteId : null,
            'transport_stop_id' => $transportStopId > 0 ? $transportStopId : null,
            'transport_fees_month' => $transportFeesMonth !== '' ? $transportFeesMonth : null,
            'hostel_room_id' => $hostelRoomId > 0 ? $hostelRoomId : null,
            'father_name' => $fatherName !== '' ? $fatherName : null,
            'father_phone' => $fatherPhone !== '' ? $fatherPhone : null,
            'father_occupation' => $fatherOccupation !== '' ? $fatherOccupation : null,
            'mother_name' => $motherName !== '' ? $motherName : null,
            'mother_phone' => $motherPhone !== '' ? $motherPhone : null,
            'mother_occupation' => $motherOccupation !== '' ? $motherOccupation : null,
        ];
        if (!$new) {
            foreach (['birth_registration_no', 'address', 'current_address', 'permanent_address', 'national_id', 'local_id', 'note'] as $key) {
                if (!$provided($key)) unset($data[$key]);
            }
            if (!$enableTransport) unset($data['transport_route_id'], $data['transport_stop_id'], $data['transport_fees_month']);
            if (!$enableHostel) unset($data['hostel_room_id']);
        }
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
                $guardianRelation = (string) $request->input('guardian_relation', '');
                $guardianPhone = trim((string) $request->input('guardian_phone', ''));
                $guardianEmail = trim((string) $request->input('guardian_email', ''));
                $guardianOccupation = trim((string) $request->input('guardian_occupation', ''));
                $guardianAddress = trim((string) $request->input('guardian_address', ''));
                $relations = ['Father', 'Mother', 'Guardian', 'Grandparent', 'Sibling', 'Other'];
                if ($guardianName === '' || $this->textLength($guardianName) > 150
                    || !in_array($guardianRelation, $relations, true) || $guardianPhone === '' || strlen($guardianPhone) > 20
                    || $this->textLength($guardianOccupation) > 100 || $this->textLength($guardianAddress) > 10000
                    || ($guardianEmail !== '' && (!filter_var($guardianEmail, FILTER_VALIDATE_EMAIL) || strlen($guardianEmail) > 190))) {
                    $this->rememberForm(); Toast::flash('Enter a guardian name, relation and valid phone; check the optional email.', 'error'); return null;
                }
                $data['_registration_guardian'] = ['mode' => 'new', 'data' => [
                    'name' => $guardianName,
                    'relation' => $guardianRelation, 'phone' => $guardianPhone,
                    'email' => $guardianEmail !== '' ? $guardianEmail : null,
                    'occupation' => $guardianOccupation !== '' ? $guardianOccupation : null,
                    'address' => $guardianAddress !== '' ? $guardianAddress : null,
                ]];
            }
        }
        if ($new) {
            $data['student_id'] = Student::nextStudentId($year['name']);
        }
        return $data;
    }

    /** @return array<string, mixed> */
    private function validatedGuardian(Request $request, ?array $existing): array
    {
        $value = static fn (string $key, string $fallback = ''): string => trim((string) $request->input($key, $existing[$key] ?? $fallback));
        $name = $value('guardian_name');
        $relation = $value('guardian_relation', 'Father');
        $phone = $value('guardian_phone');
        $email = $value('guardian_email');
        $occupation = $value('guardian_occupation');
        $address = $value('guardian_address');
        if ($name === '' || $this->textLength($name) > 150
            || !in_array($relation, ['Father', 'Mother', 'Guardian', 'Grandparent', 'Sibling', 'Other'], true)
            || $phone === '' || strlen($phone) > 20 || $this->textLength($occupation) > 100 || $this->textLength($address) > 10000
            || ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190))) {
            throw new \RuntimeException('Check guardian name, relation, phone, email, and field lengths.');
        }
        return [
            'name' => $name,
            'relation' => $relation,
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
            'occupation' => $occupation !== '' ? $occupation : null,
            'address' => $address !== '' ? $address : null,
            'photo' => $existing['photo'] ?? null,
        ];
    }

    /** @param array<string, mixed> $existing @param array<string, mixed> $candidate */
    private function guardianDataChanged(array $existing, array $candidate): bool
    {
        foreach (['name', 'relation', 'phone', 'email', 'occupation', 'address'] as $field) {
            if ((string) ($existing[$field] ?? '') !== (string) ($candidate[$field] ?? '')) return true;
        }
        return false;
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
        $allowed = ['name', 'class_id', 'section_id', 'shift', 'roll_no', 'gender', 'status', 'date_of_birth', 'birth_registration_no', 'admission_date', 'previous_school', 'address', 'religion', 'caste', 'mobile_number', 'email', 'blood_group', 'height', 'weight', 'measurement_date', 'medical_history', 'student_behaviour', 'current_address', 'permanent_address', 'national_id', 'local_id', 'note', 'house_id', 'transport_route_id', 'transport_stop_id', 'transport_fees_month', 'hostel_room_id', 'father_name', 'father_phone', 'father_occupation', 'mother_name', 'mother_phone', 'mother_occupation', 'guardian_mode', 'guardian_action', 'guardian_id', 'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_email', 'guardian_occupation', 'guardian_address', 'document_count'];
        $old = [];
        foreach ($allowed as $key) {
            if (isset($_POST[$key]) && is_scalar($_POST[$key])) $old[$key] = (string) $_POST[$key];
        }
        $documentCount = max(1, min(20, (int) ($_POST['document_count'] ?? 1)));
        $old['document_remove_ids'] = array_values(array_filter(array_map('intval', (array) ($_POST['document_remove_ids'] ?? []))));
        for ($i = 0; $i < $documentCount; $i++) {
            foreach (['document_title_', 'document_id_'] as $prefix) {
                $key = $prefix . $i;
                if (isset($_POST[$key]) && is_scalar($_POST[$key])) $old[$key] = (string) $_POST[$key];
            }
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
