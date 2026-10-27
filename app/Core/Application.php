<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\AcademicSetupController;
use App\Controllers\AuditLogController;
use App\Controllers\BranchContextController;
use App\Controllers\AccountController;
use App\Controllers\AllocationMatrixController;
use App\Controllers\AttendanceRegisterController;
use App\Controllers\AuthController;
use App\Controllers\BatchAttendanceController;
use App\Controllers\BulkMarkController;
use App\Controllers\ClassTeacherController;
use App\Controllers\DashboardController;
use App\Controllers\IdCardController;
use App\Controllers\OnlineExamController;
use App\Controllers\PermissionController;
use App\Controllers\PortalController;
use App\Controllers\PublicController;
use App\Controllers\ResultController;
use App\Controllers\SchoolSettingController;
use App\Controllers\SeatingChartController;
use App\Controllers\SetupController;
use App\Controllers\StudentController;
use App\Controllers\StudentImportController;
use App\Controllers\TimetableController;
use App\Controllers\UploadController;
use App\Controllers\FeeGenerationController;
use App\Controllers\UserController;
use App\Models\Attendance;
use App\Models\BankReconciliation;
use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Branch;
use App\Models\Certificate;
use App\Models\ClassRoutine;
use App\Models\Complaint;
use App\Models\Designation;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamAttendance;
use App\Models\ExamSchedule;
use App\Models\ExamSuggestion;
use App\Models\FeeCarryForward;
use App\Models\FeeCollection;
use App\Models\FeeDiscount;
use App\Models\FeeHead;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\Holiday;
use App\Models\HostelAllocation;
use App\Models\HostelRoom;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\LeaveRequest;
use App\Models\LessonPlan;
use App\Models\Notice;
use App\Models\OnlineExam;
use App\Models\PaymentGateway;
use App\Models\SalaryPayment;
use App\Models\SalaryStructure;
use App\Models\SmsSetting;
use App\Models\StaffAttendance;
use App\Models\StudentHouse;
use App\Models\StudentTransport;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeacherSubject;
use App\Models\Transaction;
use App\Models\TransportRoute;
use App\Models\TransportStop;

final class Application
{
    public function __construct(private array $config) {}

    public function run(): void
    {
        $router = new Router();
        $auth = new AuthController();
        $dashboard = new DashboardController();
        $setup = new SetupController();
        $students = new StudentController();
        $academics = new AcademicSetupController();
        $schoolSettings = new SchoolSettingController();
        $permissions = new PermissionController();
        $public = new PublicController();
        $import = new StudentImportController();
        $batchAttendance = new BatchAttendanceController();
        $accounts = new AccountController();
        $results = new ResultController();
        $allocation = new AllocationMatrixController();
        $classTeachers = new ClassTeacherController();
        $idCards = new IdCardController();
        $onlineExams = new OnlineExamController();
        $portal = new PortalController();
        $register = new AttendanceRegisterController();
        $timetable = new TimetableController();
        $seating = new SeatingChartController();
        $bulkMarks = new BulkMarkController();
        $users = new UserController();
        $uploads = new UploadController();
        $feeGeneration = new FeeGenerationController();
        $auditLogs = new AuditLogController();
        $branchContext = new BranchContextController();

        // The public website, open to everyone.
        $router->get('/', [$public, 'home']);

        // The portals of a teacher, a student and a parent.
        $router->get('/portal', [$portal, 'index']);
        $router->get('/portal/student', [$portal, 'student']);
        $router->get('/portal/teacher', [$portal, 'teacher']);
        $router->get('/portal/parent', [$portal, 'parent']);
        $router->get('/portal/exams', [$portal, 'exams']);
        $router->get('/portal/exams/take', [$portal, 'takeExam']);
        $router->post('/portal/exams/submit', [$portal, 'submitExam']);
        $router->get('/portal/results', [$portal, 'results']);
        $router->get('/portal/attendance', [$portal, 'attendance']);
        $router->get('/portal/fees', [$portal, 'fees']);

        $router->get('/login', [$auth, 'loginForm']);
        $router->post('/login', [$auth, 'login']);
        $router->post('/logout', [$auth, 'logout']);
        $router->get('/dashboard', [$dashboard, 'index']);
        $router->get('/setup', [$setup, 'form']);
        $router->post('/setup', [$setup, 'createAdmin']);

        // Students and academics
        $router->get('/students', [$students, 'index']);
        $router->get('/students/export', [$students, 'export']);
        $router->get('/students/view', [$students, 'show']);
        $router->get('/students/new', [$students, 'create']);
        $router->post('/students', [$students, 'store']);
        $router->get('/students/edit', [$students, 'edit']);
        $router->post('/students/update', [$students, 'update']);
        $router->post('/students/archive', [$students, 'archive']);
        $router->get('/students/guardians', [$students, 'guardians']);
        $router->post('/students/guardians/link', [$students, 'linkGuardian']);
        $router->post('/students/guardians/unlink', [$students, 'unlinkGuardian']);
        $router->post('/students/guardians/primary', [$students, 'setPrimaryGuardian']);
        $router->get('/students/promote', [(new \App\Controllers\StudentPromotionController()), 'index']);
        $router->post('/students/promote', [(new \App\Controllers\StudentPromotionController()), 'promote']);
        $router->get('/students/guardians', [$students, 'guardians']);
        $router->post('/students/guardians/link', [$students, 'linkGuardian']);
        $router->post('/students/guardians/unlink', [$students, 'unlinkGuardian']);
        $router->post('/students/guardians/primary', [$students, 'setPrimaryGuardian']);
        $router->get('/uploads/file', [$uploads, 'show']);
        $router->get('/students/import', [$import, 'show']);
        $router->post('/students/import', [$import, 'store']);
        $router->get('/academics', [$academics, 'index']);
        $router->post('/academics/years', [$academics, 'storeYear']);
        $router->post('/academics/years/current', [$academics, 'setCurrentYear']);
        $router->post('/academics/classes', [$academics, 'storeClass']);
        $router->post('/academics/sections', [$academics, 'storeSection']);

        // Communication
        $this->crud($router, new Crud(Notice::module()), '/notices');
        $this->crud($router, new Crud(Holiday::module()), '/holidays');
        $this->crud($router, new Crud(Event::module()), '/events');
        $this->crud($router, new Crud(Complaint::module()), '/complaints');

        // People and academic
        $this->crud($router, new Crud(Teacher::module()), '/teachers');
        $this->crud($router, new Crud(Designation::module()), '/designations');
        $this->crud($router, new Crud(Guardian::module()), '/guardians');
        $this->crud($router, new Crud(StudentHouse::module()), '/student-houses');
        $this->crud($router, new Crud(Subject::module()), '/subjects');
        $this->crud($router, new Crud(StudyMaterial::module()), '/study-materials');
        $this->crud($router, new Crud(LessonPlan::module()), '/lesson-plans');
        $this->crud($router, new Crud(Branch::module()), '/branches');

        // Attendance
        $this->crud($router, new Crud(Attendance::module()), '/attendance');
        $router->get('/attendance-register', [$register, 'index']);
        $router->get('/batch-attendance', [$batchAttendance, 'create']);
        $router->post('/batch-attendance', [$batchAttendance, 'store']);
        $this->crud($router, new Crud(TeacherAttendance::module()), '/teacher-attendances');
        $this->crud($router, new Crud(StaffAttendance::module()), '/staff-attendances');
        $this->crud($router, new Crud(LeaveRequest::module()), '/leave-requests');

        // Finance
        $this->crud($router, new Crud(FeeHead::module()), '/fee-heads');
        $this->crud($router, new Crud(FeeStructure::module()), '/fee-structures');
        $this->crud($router, new Crud(FeeCollection::module()), '/fees');
        $router->get('/fees/generate', [$feeGeneration, 'form']);
        $router->post('/fees/generate', [$feeGeneration, 'generate']);

        // Accounts
        $router->get('/accounts', [$accounts, 'dashboard']);
        $this->crud($router, new Crud(Transaction::module()), '/accounts/transactions');
        $this->crud($router, new Crud(FeeDiscount::module()), '/accounts/discounts');
        $this->crud($router, new Crud(FeeCarryForward::module()), '/accounts/carry-forwards');
        $this->crud($router, new Crud(BankReconciliation::module()), '/accounts/reconciliations');

        // Payroll
        $this->crud($router, new Crud(SalaryStructure::module()), '/salary-structures');
        $this->crud($router, new Crud(SalaryPayment::module()), '/salary-payments');

        // Teacher assignments
        $router->get('/allocation-matrix', [$allocation, 'index']);
        $router->post('/allocation-matrix/update', [$allocation, 'update']);
        $router->get('/class-teachers', [$classTeachers, 'index']);
        $router->post('/class-teachers/update', [$classTeachers, 'update']);
        $this->crud($router, new Crud(TeacherSubject::module()), '/teacher-subjects');
        $this->crud($router, new Crud(ClassRoutine::module()), '/class-routines');
        $router->get('/timetable', [$timetable, 'index']);

        // Facilities
        $this->crud($router, new Crud(Book::module()), '/books');
        $this->crud($router, new Crud(BookIssue::module()), '/book-issues');
        $this->crud($router, new Crud(TransportRoute::module()), '/transport-routes');
        $this->crud($router, new Crud(TransportStop::module()), '/transport-stops');
        $this->crud($router, new Crud(StudentTransport::module()), '/student-transport');
        $this->crud($router, new Crud(HostelRoom::module()), '/hostel-rooms');
        $this->crud($router, new Crud(HostelAllocation::module()), '/hostel-allocations');
        $this->crud($router, new Crud(InventoryCategory::module()), '/inventory/categories');
        $this->crud($router, new Crud(InventoryItem::module()), '/inventory');

        // Exams and results
        $this->crud($router, new Crud(Exam::module()), '/exams');
        $this->crud($router, new Crud(ExamSchedule::module()), '/exam-schedules');
        $router->get('/reports', [$results, 'reports']);
        $router->get('/reports/form', [$results, 'reportForm']);
        $router->post('/reports/save', [$results, 'reportSave']);
        $router->get('/reports/config', [$results, 'configForm']);
        $router->post('/reports/config/save', [$results, 'configSave']);
        $router->post('/reports/config/delete', [$results, 'configDelete']);
        $router->get('/fail-lists', [$results, 'failLists']);
        $router->get('/section-results', [$results, 'sectionResults']);
        $router->get('/tabulation-sheets', [$results, 'tabulationSheets']);
        $router->get('/merit-lists', [$results, 'meritLists']);
        $router->get('/admit-cards', [$results, 'admitCards']);
        $router->get('/admit-cards/print', [$results, 'printAdmitCard']);
        $this->crud($router, new Crud(ExamSuggestion::module()), '/exam-suggestions');
        $this->crud($router, new Crud(ExamAttendance::module()), '/exam-attendances');
        $router->get('/id-cards', [$idCards, 'index']);
        $router->get('/marks', [$bulkMarks, 'index']);
        $router->post('/marks/save', [$bulkMarks, 'store']);
        $router->get('/marks/sheet', [$bulkMarks, 'sheet']);
        $router->get('/seating-chart', [$seating, 'index']);
        $router->post('/seating-chart/generate', [$seating, 'generate']);
        $router->post('/seating-chart/clear', [$seating, 'clear']);
        $this->crud($router, new Crud(Certificate::module()), '/certificates');
        $this->crud($router, new Crud(OnlineExam::module()), '/online-exams');
        $router->get('/online-exams/questions', [$onlineExams, 'questions']);
        $router->post('/online-exams/questions/store', [$onlineExams, 'storeQuestion']);
        $router->post('/online-exams/questions/delete', [$onlineExams, 'deleteQuestion']);
        $router->get('/online-exams/results', [$onlineExams, 'results']);

        // Settings
        $router->get('/settings/school', [$schoolSettings, 'index']);
        $router->get('/audit-logs', [$auditLogs, 'index']);
        $router->post('/branches/active', [$branchContext, 'switch']);
        $router->post('/settings/school', [$schoolSettings, 'update']);
        $router->get('/permissions', [$permissions, 'index']);
        $router->post('/permissions', [$permissions, 'update']);
        $this->crud($router, new Crud(SmsSetting::module()), '/sms-settings');
        $this->crud($router, new Crud(PaymentGateway::module()), '/payment-gateways');

        // User accounts, and the person record each one is linked to.
        $router->get('/users', [$users, 'index']);
        $router->get('/users/new', [$users, 'create']);
        $router->post('/users', [$users, 'store']);
        $router->get('/users/edit', [$users, 'edit']);
        $router->post('/users/update', [$users, 'update']);
        $router->post('/users/delete', [$users, 'destroy']);

        try {
            $router->dispatch(new Request());
        } catch (\Throwable $error) {
            http_response_code(500);
            error_log((string) $error);
            ErrorLog::write($error);
            View::render('errors/500', ['title' => 'Application error', 'message' => 'The request could not be completed. Check the database configuration and try again.']);
        }
    }

    /**
     * Register the six routes a standard list and form module needs.
     */
    private function crud(Router $router, Crud $controller, string $route): void
    {
        $router->get($route, [$controller, 'index']);
        $router->get($route . '/new', [$controller, 'create']);
        $router->post($route, [$controller, 'store']);
        $router->get($route . '/edit', [$controller, 'edit']);
        $router->post($route . '/update', [$controller, 'update']);
        $router->post($route . '/delete', [$controller, 'destroy']);
    }
}
