<?php
declare(strict_types=1);

/**
 * Apply the database schema.
 *
 * Every statement in database/schema.sql is written as "CREATE TABLE IF NOT
 * EXISTS", so running this script is safe on an existing installation: it
 * creates any table that is missing and leaves the ones already there
 * untouched.
 *
 * Usage: php scripts/migrate.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));

$configPath = BASE_PATH . '/config/database.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "Database is not configured. Copy config/database.example.php to config/database.php.\n");
    exit(1);
}

$config = require $configPath;
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['database'], $config['charset'] ?? 'utf8mb4');

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $error) {
    fwrite(STDERR, 'Could not connect to the database: ' . $error->getMessage() . "\n");
    exit(1);
}

$schema = file_get_contents(BASE_PATH . '/database/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "Could not read database/schema.sql.\n");
    exit(1);
}

$applied = 0;
foreach (explode(';', $schema) as $statement) {
    $statement = trim($statement);
    if ($statement === '') {
        continue;
    }
    try {
        $pdo->exec($statement);
        $applied++;
    } catch (PDOException $error) {
        fwrite(STDERR, 'Failed: ' . $error->getMessage() . "\n  " . substr($statement, 0, 120) . "\n");
        exit(1);
    }
}

// Add a tenant key to school data while keeping control-plane tables global.
// Existing records are assigned to the main campus, preserving single-branch
// installations and making the migration safe to repeat.
$branchTables = [
    'users', 'audit_logs', 'academic_years', 'classes', 'sections', 'students', 'student_enrollment_history',
    'notices', 'holidays', 'events', 'complaints', 'school_settings', 'subjects', 'class_subject',
    'teachers', 'designations', 'teacher_subject', 'guardians', 'student_guardian', 'student_houses',
    'shifts', 'departments', 'fee_heads', 'fee_structures', 'fee_collections', 'fee_discounts', 'fee_carry_forwards',
    'transactions', 'bank_reconciliations', 'salary_structures', 'salary_payments', 'attendances',
    'teacher_attendances', 'staff_attendances', 'leave_requests', 'inventory_categories',
    'inventory_items', 'books', 'book_issues', 'transport_routes', 'transport_stops', 'student_transport',
    'hostel_rooms', 'hostel_allocations', 'class_routines', 'exams', 'exam_schedules', 'exam_attendances',
    'exam_suggestions', 'academic_reports', 'academic_report_marks', 'report_configs', 'online_exams',
    'exam_questions', 'exam_submissions', 'certificates', 'sms_settings', 'payment_gateways', 'exam_seats',
    'study_materials', 'lesson_plans',
];
$pdo->exec("INSERT INTO branches (name,is_main,status) SELECT 'Main Campus',1,'active' WHERE NOT EXISTS (SELECT 1 FROM branches)");
$mainBranchId = (int) $pdo->query('SELECT id FROM branches ORDER BY is_main DESC,id ASC LIMIT 1')->fetchColumn();
$pdo->exec('UPDATE branches SET is_main=0 WHERE id <> ' . $mainBranchId . ' AND is_main=1');
$pdo->exec('UPDATE branches SET is_main=1 WHERE id=' . $mainBranchId);
$branchColumnsAdded = 0;
foreach ($branchTables as $table) {
    $existing = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('branch_id', $existing, true)) {
        $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN branch_id BIGINT UNSIGNED NULL');
        $branchColumnsAdded++;
    }
    $pdo->exec('UPDATE `' . $table . '` SET branch_id=' . $mainBranchId . ' WHERE branch_id IS NULL');
    $pdo->exec('ALTER TABLE `' . $table . '` MODIFY COLUMN branch_id BIGINT UNSIGNED NOT NULL DEFAULT ' . $mainBranchId);
    $indexName = $table . '_branch_index';
    $index = $pdo->query("SHOW INDEX FROM `{$table}` WHERE Key_name = " . $pdo->quote($indexName))->fetchAll();
    if ($index === []) $pdo->exec('ALTER TABLE `' . $table . '` ADD INDEX `' . $indexName . '` (branch_id)');
    $constraintName = $table . '_branch_fk';
    $constraint = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=" . $pdo->quote($table) . ' AND CONSTRAINT_NAME=' . $pdo->quote($constraintName))->fetchColumn();
    if ($constraint === false) $pdo->exec('ALTER TABLE `' . $table . '` ADD CONSTRAINT `' . $constraintName . '` FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE RESTRICT');
}

// Scope historically global natural keys by campus so each branch can use
// its own class labels, subjects, and student identifiers.
$branchUniqueKeys = [
    'academic_years' => ['academic_years_name_unique' => 'name'],
    'classes' => ['classes_name_unique' => 'name', 'classes_numeric_level_unique' => 'numeric_level'],
    'students' => ['students_student_id_unique' => 'student_id'],
    'school_settings' => ['school_settings_key_unique' => 'key'],
    'subjects' => ['subjects_name_unique' => 'name', 'subjects_code_unique' => 'code'],
    'teachers' => ['teachers_employee_id_unique' => 'employee_id'],
    'designations' => ['designations_name_unique' => 'name'],
    'shifts' => ['shifts_name_unique' => 'name'],
    'departments' => ['departments_name_unique' => 'name'],
    'fee_heads' => ['fee_heads_name_unique' => 'name'],
    'student_houses' => ['student_houses_name_unique' => 'name'],
    'inventory_categories' => ['inventory_categories_name_unique' => 'name'],
    'transport_routes' => ['transport_routes_name_unique' => 'name'],
    'hostel_rooms' => ['hostel_rooms_room_unique' => 'room_no'],
    'certificates' => ['certificates_number_unique' => 'certificate_no'],
    'payment_gateways' => ['payment_gateways_provider_unique' => 'provider_key'],
    'branches' => [],
];
foreach ($branchUniqueKeys as $table => $keys) {
    foreach ($keys as $key => $column) {
        $present = $pdo->query("SHOW INDEX FROM `{$table}` WHERE Key_name=" . $pdo->quote($key))->fetchAll();
        if ($present === []) continue;
        $pdo->exec("ALTER TABLE `{$table}` DROP INDEX `{$key}`");
        $pdo->exec("ALTER TABLE `{$table}` ADD UNIQUE KEY `{$key}` (branch_id, `{$column}`)");
    }
}

// A prior migration could leave school_settings without its natural-key index.
// Collapse duplicate rows to the newest saved value before restoring it.
$schoolSettingsIndex = $pdo->query("SHOW INDEX FROM school_settings WHERE Key_name='school_settings_key_unique'")->fetchAll();
if ($schoolSettingsIndex === []) {
    $pdo->exec(
        'DELETE older FROM school_settings older '
        . 'INNER JOIN school_settings newer '
        . 'ON newer.branch_id = older.branch_id AND newer.`key` = older.`key` AND newer.id > older.id'
    );
    $pdo->exec('ALTER TABLE school_settings ADD UNIQUE KEY school_settings_key_unique (branch_id, `key`)');
}

// Seed the default shifts used by classes and students.
foreach (['Morning', 'Day'] as $shiftName) {
    $findShift = $pdo->prepare('SELECT id FROM shifts WHERE name = :name AND branch_id = :branch LIMIT 1');
    $findShift->execute(['name' => $shiftName, 'branch' => $mainBranchId]);
    if ($findShift->fetchColumn() === false) {
        $pdo->prepare('INSERT INTO shifts (name, branch_id, created_at) VALUES (:name, :branch, NOW())')->execute(['name' => $shiftName, 'branch' => $mainBranchId]);
    }
}

// Columns added after a table already exists in the field need an explicit
// alteration, because CREATE TABLE IF NOT EXISTS leaves the table untouched.
$columns = [
    'users' => [
        'link_type' => "ENUM('student','teacher','guardian') NULL AFTER role",
        'link_id' => 'BIGINT UNSIGNED NULL AFTER link_type',
    ],
    'students' => [
        'photo' => 'VARCHAR(255) NULL AFTER gender',
        'date_of_birth' => 'DATE NULL AFTER photo',
        'birth_registration_no' => 'VARCHAR(50) NULL AFTER date_of_birth',
        'admission_date' => 'DATE NULL AFTER birth_registration_no',
        'previous_school' => 'VARCHAR(190) NULL AFTER admission_date',
        'address' => 'TEXT NULL AFTER previous_school',
    ],
    'academic_report_marks' => [
        'obtained' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER ca_marks',
    ],
    'academic_reports' => [
        'exam_id' => 'BIGINT UNSIGNED NULL AFTER student_id',
    ],
];

$added = 0;
foreach ($columns as $table => $tableColumns) {
    $existing = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tableColumns as $column => $definition) {
        if (in_array($column, $existing, true)) {
            continue;
        }
        $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        $added++;
        echo 'Added column ', $table, '.', $column, PHP_EOL;
    }
}

// Link legacy report cards to their exam records when a matching exam exists.
$reportCols = $pdo->query('SHOW COLUMNS FROM academic_reports')->fetchAll(PDO::FETCH_COLUMN);
if (in_array('exam_name', $reportCols, true)) {
    $pdo->exec("UPDATE academic_reports ar SET ar.exam_id = (SELECT e.id FROM exams e LEFT JOIN academic_years ay ON ay.id = e.academic_year_id WHERE e.name = ar.exam_name AND (e.academic_year_id IS NULL OR ay.name = ar.academic_year) ORDER BY (e.academic_year_id IS NULL), e.id LIMIT 1) WHERE ar.exam_id IS NULL");
}
$indexes = $pdo->query("SHOW INDEX FROM academic_reports WHERE Key_name = 'academic_reports_exam_index'")->fetchAll();
if ($indexes === []) $pdo->exec('ALTER TABLE academic_reports ADD INDEX academic_reports_exam_index (exam_id)');
$constraints = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'academic_reports' AND CONSTRAINT_NAME = 'academic_reports_exam_fk'")->fetchAll();
if ($constraints === []) $pdo->exec('ALTER TABLE academic_reports ADD CONSTRAINT academic_reports_exam_fk FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE SET NULL');
$uniqueExam = $pdo->query("SHOW INDEX FROM academic_reports WHERE Key_name = 'academic_reports_student_exam_unique'")->fetchAll();
$duplicateExamLinks = (int) $pdo->query('SELECT COUNT(*) FROM (SELECT student_id, exam_id FROM academic_reports WHERE exam_id IS NOT NULL GROUP BY student_id, exam_id HAVING COUNT(*) > 1) duplicate_exam_reports')->fetchColumn();
if ($uniqueExam === [] && $duplicateExamLinks === 0) {
    $pdo->exec('ALTER TABLE academic_reports ADD UNIQUE KEY academic_reports_student_exam_unique (student_id, exam_id)');
    echo 'Added unique student/exam report-card key.', PHP_EOL;
} elseif ($duplicateExamLinks > 0) {
    echo 'Existing duplicate student/exam report cards were preserved; unique exam key was not added.', PHP_EOL;
}

// ---------------------------------------------------------------------------
// Normalization migrations: replace denormalized columns with relationships.
// ---------------------------------------------------------------------------

// teachers.designation -> designation_id
$teacherColumns = $pdo->query('SHOW COLUMNS FROM teachers')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('designation_id', $teacherColumns, true)) {
    $pdo->exec('ALTER TABLE teachers ADD COLUMN designation_id BIGINT UNSIGNED NULL AFTER department');
}
if (in_array('designation', $teacherColumns, true)) {
    $legacyTeachers = $pdo->query("SELECT id, designation, branch_id FROM teachers WHERE designation IS NOT NULL AND designation <> '' AND designation_id IS NULL")->fetchAll();
    $findDesignation = $pdo->prepare('SELECT id FROM designations WHERE name = :name AND branch_id = :branch LIMIT 1');
    $insertDesignation = $pdo->prepare('INSERT INTO designations (name, branch_id, is_active, created_at) VALUES (:name, :branch, 1, NOW())');
    $updateTeacher = $pdo->prepare('UPDATE teachers SET designation_id = :designation_id WHERE id = :id');
    foreach ($legacyTeachers as $teacher) {
        $findDesignation->execute(['name' => $teacher['designation'], 'branch' => $teacher['branch_id']]);
        $designationId = $findDesignation->fetchColumn();
        if ($designationId === false) {
            $insertDesignation->execute(['name' => $teacher['designation'], 'branch' => $teacher['branch_id']]);
            $designationId = $pdo->lastInsertId();
        }
        $updateTeacher->execute(['designation_id' => (int) $designationId, 'id' => (int) $teacher['id']]);
    }
    $pdo->exec('ALTER TABLE teachers DROP COLUMN designation');
}
if ($pdo->query("SHOW INDEX FROM teachers WHERE Key_name = 'teachers_designation_index'")->fetchAll() === []) {
    $pdo->exec('ALTER TABLE teachers ADD INDEX teachers_designation_index (designation_id)');
}
if ($pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teachers' AND CONSTRAINT_NAME = 'teachers_designation_fk'")->fetchColumn() === false) {
    $pdo->exec('ALTER TABLE teachers ADD CONSTRAINT teachers_designation_fk FOREIGN KEY (designation_id) REFERENCES designations (id) ON DELETE SET NULL');
}

// teachers.department -> department_id
$teacherColumns = $pdo->query('SHOW COLUMNS FROM teachers')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('department_id', $teacherColumns, true)) {
    $pdo->exec('ALTER TABLE teachers ADD COLUMN department_id BIGINT UNSIGNED NULL AFTER designation_id');
}
if (in_array('department', $teacherColumns, true)) {
    $legacyTeachers = $pdo->query("SELECT id, department, branch_id FROM teachers WHERE department IS NOT NULL AND department <> '' AND department_id IS NULL")->fetchAll();
    $findDepartment = $pdo->prepare('SELECT id FROM departments WHERE name = :name AND branch_id = :branch LIMIT 1');
    $insertDepartment = $pdo->prepare('INSERT INTO departments (name, branch_id, created_at) VALUES (:name, :branch, NOW())');
    $updateTeacher = $pdo->prepare('UPDATE teachers SET department_id = :department_id WHERE id = :id');
    foreach ($legacyTeachers as $teacher) {
        $findDepartment->execute(['name' => $teacher['department'], 'branch' => $teacher['branch_id']]);
        $departmentId = $findDepartment->fetchColumn();
        if ($departmentId === false) {
            $insertDepartment->execute(['name' => $teacher['department'], 'branch' => $teacher['branch_id']]);
            $departmentId = $pdo->lastInsertId();
        }
        $updateTeacher->execute(['department_id' => (int) $departmentId, 'id' => (int) $teacher['id']]);
    }
    $pdo->exec('ALTER TABLE teachers DROP COLUMN department');
}
if ($pdo->query("SHOW INDEX FROM teachers WHERE Key_name = 'teachers_department_index'")->fetchAll() === []) {
    $pdo->exec('ALTER TABLE teachers ADD INDEX teachers_department_index (department_id)');
}
if ($pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teachers' AND CONSTRAINT_NAME = 'teachers_department_fk'")->fetchColumn() === false) {
    $pdo->exec('ALTER TABLE teachers ADD CONSTRAINT teachers_department_fk FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL');
}

// academic_report_marks.subject_name -> subject_id
$markColumns = $pdo->query('SHOW COLUMNS FROM academic_report_marks')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('subject_id', $markColumns, true)) {
    $pdo->exec('ALTER TABLE academic_report_marks ADD COLUMN subject_id BIGINT UNSIGNED NULL AFTER academic_report_id');
}
if (in_array('subject_name', $markColumns, true)) {
    $legacyMarks = $pdo->query("SELECT id, subject_name, branch_id FROM academic_report_marks WHERE subject_name IS NOT NULL AND subject_name <> '' AND subject_id IS NULL")->fetchAll();
    $findSubject = $pdo->prepare('SELECT id FROM subjects WHERE name = :name AND branch_id = :branch LIMIT 1');
    $insertSubject = $pdo->prepare("INSERT INTO subjects (name, type, branch_id, created_at) VALUES (:name, 'core', :branch, NOW())");
    $updateMark = $pdo->prepare('UPDATE academic_report_marks SET subject_id = :subject_id WHERE id = :id');
    foreach ($legacyMarks as $mark) {
        $findSubject->execute(['name' => $mark['subject_name'], 'branch' => $mark['branch_id']]);
        $subjectId = $findSubject->fetchColumn();
        if ($subjectId === false) {
            $insertSubject->execute(['name' => $mark['subject_name'], 'branch' => $mark['branch_id']]);
            $subjectId = $pdo->lastInsertId();
        }
        $updateMark->execute(['subject_id' => (int) $subjectId, 'id' => (int) $mark['id']]);
    }
    $pdo->exec('ALTER TABLE academic_report_marks DROP COLUMN subject_name');
}
if ($pdo->query("SHOW INDEX FROM academic_report_marks WHERE Key_name = 'academic_report_marks_subject_index'")->fetchAll() === []) {
    $pdo->exec('ALTER TABLE academic_report_marks ADD INDEX academic_report_marks_subject_index (subject_id)');
}
if ($pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'academic_report_marks' AND CONSTRAINT_NAME = 'academic_report_marks_subject_fk'")->fetchColumn() === false) {
    $pdo->exec('ALTER TABLE academic_report_marks ADD CONSTRAINT academic_report_marks_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE SET NULL');
}

// academic_reports.exam_name/academic_year -> exam_id (linked above)
$reportColumns = $pdo->query('SHOW COLUMNS FROM academic_reports')->fetchAll(PDO::FETCH_COLUMN);
if (in_array('exam_name', $reportColumns, true)) {
    $orphans = $pdo->query('SELECT id, exam_name, academic_year, branch_id FROM academic_reports WHERE exam_id IS NULL')->fetchAll();
    foreach ($orphans as $report) {
        $name = trim((string) ($report['exam_name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $yearName = trim((string) ($report['academic_year'] ?? ''));
        $yearId = null;
        if ($yearName !== '') {
            $stmt = $pdo->prepare('SELECT id FROM academic_years WHERE name = :name AND branch_id = :branch LIMIT 1');
            $stmt->execute(['name' => $yearName, 'branch' => $report['branch_id']]);
            $yearId = $stmt->fetchColumn() ?: null;
        }
        $stmt = $pdo->prepare('SELECT id FROM exams WHERE name = :name AND branch_id = :branch AND ((academic_year_id IS NULL AND :year_id IS NULL) OR academic_year_id = :year_id2) ORDER BY id LIMIT 1');
        $stmt->execute(['name' => $name, 'branch' => $report['branch_id'], 'year_id' => $yearId, 'year_id2' => $yearId]);
        $examId = $stmt->fetchColumn();
        if ($examId === false) {
            $stmt = $pdo->prepare('INSERT INTO exams (name, type, academic_year_id, branch_id, created_at) VALUES (:name, "other", :year_id, :branch, NOW())');
            $stmt->execute(['name' => $name, 'year_id' => $yearId, 'branch' => $report['branch_id']]);
            $examId = $pdo->lastInsertId();
        }
        $pdo->prepare('UPDATE academic_reports SET exam_id = :exam_id WHERE id = :id')->execute(['exam_id' => (int) $examId, 'id' => (int) $report['id']]);
    }
    // Older installs used a composite unique key on the legacy columns; drop
    // it before removing the columns it references.
    $legacyUnique = $pdo->query("SHOW INDEX FROM academic_reports WHERE Key_name = 'academic_reports_unique'")->fetchAll();
    if ($legacyUnique !== []) {
        $pdo->exec('ALTER TABLE academic_reports DROP INDEX academic_reports_unique');
    }
    $pdo->exec('ALTER TABLE academic_reports DROP COLUMN exam_name');
    $pdo->exec('ALTER TABLE academic_reports DROP COLUMN academic_year');
}

// notices.target_roles -> notice_roles
$noticeColumns = $pdo->query('SHOW COLUMNS FROM notices')->fetchAll(PDO::FETCH_COLUMN);
if (in_array('target_roles', $noticeColumns, true)) {
    $legacyNotices = $pdo->query("SELECT id, target_roles FROM notices WHERE target_roles IS NOT NULL AND target_roles <> ''")->fetchAll();
    $insertRole = $pdo->prepare('INSERT IGNORE INTO notice_roles (notice_id, role) VALUES (:notice_id, :role)');
    foreach ($legacyNotices as $notice) {
        foreach (array_filter(array_map('trim', explode(',', (string) $notice['target_roles']))) as $role) {
            $insertRole->execute(['notice_id' => (int) $notice['id'], 'role' => (string) $role]);
        }
    }
    $pdo->exec('ALTER TABLE notices DROP COLUMN target_roles');
}

// exam_questions.options -> exam_question_options
$questionColumns = $pdo->query('SHOW COLUMNS FROM exam_questions')->fetchAll(PDO::FETCH_COLUMN);
if (in_array('options', $questionColumns, true)) {
    $legacyQuestions = $pdo->query("SELECT id, options, correct_answer FROM exam_questions WHERE options IS NOT NULL AND options <> ''")->fetchAll();
    $insertOption = $pdo->prepare('INSERT INTO exam_question_options (exam_question_id, option_text, is_correct, sort_order) VALUES (:question_id, :option_text, :is_correct, :sort_order)');
    foreach ($legacyQuestions as $question) {
        $decoded = json_decode((string) $question['options'], true);
        $options = is_array($decoded) ? array_values($decoded) : array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $question['options']))));
        $correct = trim((string) ($question['correct_answer'] ?? ''));
        foreach ($options as $index => $option) {
            $option = trim((string) $option);
            if ($option === '') {
                continue;
            }
            $insertOption->execute([
                'question_id' => (int) $question['id'],
                'option_text' => $option,
                'is_correct' => ($correct !== '' && strcasecmp($option, $correct) === 0) ? 1 : 0,
                'sort_order' => $index,
            ]);
        }
    }
    $pdo->exec('ALTER TABLE exam_questions DROP COLUMN options');
}

// ---------------------------------------------------------------------------
// High-traffic result management: materialized totals, GPA and position.
// ---------------------------------------------------------------------------

$columns = [
    'academic_reports' => [
        'total_marks' => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER class_position',
        'gpa' => 'DECIMAL(4,2) NOT NULL DEFAULT 0.00 AFTER total_marks',
        'overall_grade' => "VARCHAR(5) NOT NULL DEFAULT 'F' AFTER gpa",
        'has_failed' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER overall_grade',
    ],
];
foreach ($columns as $table => $tableColumns) {
    $existing = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tableColumns as $column => $definition) {
        if (in_array($column, $existing, true)) {
            continue;
        }
        $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        $added++;
    }
}
foreach ([
    'academic_reports_exam_position_index' => 'ALTER TABLE academic_reports ADD INDEX academic_reports_exam_position_index (exam_id, class_position)',
    'academic_reports_gpa_index' => 'ALTER TABLE academic_reports ADD INDEX academic_reports_gpa_index (exam_id, gpa)',
    'students_class_status_index' => 'ALTER TABLE students ADD INDEX students_class_status_index (class_id, status)',
    'students_class_exam_index' => 'ALTER TABLE students ADD INDEX students_class_exam_index (class_id, academic_year_id)',
] as $name => $sql) {
    $table = strpos($name, 'students_') === 0 ? 'students' : 'academic_reports';
    if ($pdo->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$name}'")->fetchAll() === []) {
        $pdo->exec($sql);
    }
}

// The (academic_report_id, subject_id) uniqueness enforces exactly one mark
// per subject on a student's report card.
if ($pdo->query("SHOW INDEX FROM academic_report_marks WHERE Key_name = 'academic_report_marks_report_subject_unique'")->fetchAll() === []) {
    $duplicates = (int) $pdo->query('SELECT COUNT(*) FROM (SELECT academic_report_id, subject_id FROM academic_report_marks WHERE subject_id IS NOT NULL GROUP BY academic_report_id, subject_id HAVING COUNT(*) > 1) duplicate_marks')->fetchColumn();
    if ($duplicates === 0) {
        $pdo->exec('ALTER TABLE academic_report_marks ADD UNIQUE KEY academic_report_marks_report_subject_unique (academic_report_id, subject_id)');
    } else {
        echo 'Duplicate academic_report_marks rows were preserved; the unique subject key was not added.', PHP_EOL;
    }
}

// Backfill total, GPA, grade and position for every existing report card.
function gradeForMarks(float $marks): array
{
    if ($marks >= 80) return ['A+', 5.0];
    if ($marks >= 70) return ['A', 4.0];
    if ($marks >= 60) return ['A-', 3.5];
    if ($marks >= 50) return ['B', 3.0];
    if ($marks >= 40) return ['C', 2.0];
    if ($marks >= 33) return ['D', 1.0];
    return ['F', 0.0];
}
function overallGradeFor(float $average, bool $hasFailed): string
{
    if ($hasFailed || $average < 1.0) return 'F';
    if ($average >= 5.0) return 'A+';
    if ($average >= 4.0) return 'A';
    if ($average >= 3.5) return 'A-';
    if ($average >= 3.0) return 'B';
    if ($average >= 2.0) return 'C';
    return 'D';
}
$reportRows = $pdo->query(
    'SELECT ar.id, ar.exam_id, ar.student_id, s.class_id, s.roll_no'
    . ' FROM academic_reports ar JOIN students s ON s.id = ar.student_id AND s.branch_id = ar.branch_id'
)->fetchAll();
$markRows = $pdo->query(
    'SELECT academic_report_id, written_marks, mcq_marks, practical_marks, ca_marks, obtained'
    . ' FROM academic_report_marks'
)->fetchAll();
$marksByReport = [];
foreach ($markRows as $mark) {
    $marksByReport[(int) $mark['academic_report_id']][] = (int) $mark['written_marks'] + (int) $mark['mcq_marks']
        + (int) $mark['practical_marks'] + (int) $mark['ca_marks'] + (int) $mark['obtained'];
}
$computed = [];
foreach ($reportRows as $report) {
    $total = 0.0;
    $gradePoints = 0.0;
    $hasFailed = false;
    $subjects = $marksByReport[(int) $report['id']] ?? [];
    foreach ($subjects as $subjectTotal) {
        $total += (float) $subjectTotal;
        [$letter, $point] = gradeForMarks((float) $subjectTotal);
        if ($letter === 'F') {
            $hasFailed = true;
        } else {
            $gradePoints += $point;
        }
    }
    $average = $subjects === [] ? 0.0 : round($gradePoints / count($subjects), 2);
    $computed[(int) $report['id']] = [
        'total' => (int) round($total),
        'gpa' => $hasFailed ? 0.0 : $average,
        'grade' => overallGradeFor($average, $hasFailed),
        'has_failed' => $hasFailed ? 1 : 0,
        'class_id' => (int) $report['class_id'],
        'exam_id' => (int) $report['exam_id'],
        'roll_no' => (int) $report['roll_no'],
    ];
}
$groups = [];
foreach ($computed as $reportId => $values) {
    $groups[$values['exam_id']][$values['class_id']][] = $reportId;
}
$updateReport = $pdo->prepare('UPDATE academic_reports SET total_marks = :total, gpa = :gpa, overall_grade = :grade, has_failed = :failed, class_position = :position WHERE id = :id');
foreach ($groups as $examGroup) {
  foreach ($examGroup as $group) {
    usort($group, static function (int $a, int $b) use ($computed): int {
        if ($computed[$a]['total'] !== $computed[$b]['total']) {
            return $computed[$b]['total'] <=> $computed[$a]['total'];
        }
        return $computed[$a]['roll_no'] <=> $computed[$b]['roll_no'];
    });
    $position = 0;
    $previousTotal = null;
    $previousPosition = 0;
    foreach ($group as $reportId) {
        $position++;
        $entryPosition = ($previousTotal !== null && $computed[$reportId]['total'] === $previousTotal) ? $previousPosition : $position;
        if ($entryPosition === $position) {
            $previousPosition = $position;
        }
        $previousTotal = $computed[$reportId]['total'];
        $updateReport->execute([
            'total' => $computed[$reportId]['total'],
            'gpa' => $computed[$reportId]['gpa'],
            'grade' => $computed[$reportId]['grade'],
            'failed' => $computed[$reportId]['has_failed'],
            'position' => $entryPosition,
            'id' => $reportId,
        ]);
    }
  }
}

// ---------------------------------------------------------------------------
// Expanded admission form: additional student attributes, guardian photo and
// admission documents.
// ---------------------------------------------------------------------------

$newColumns = [
    'students' => [
        'shift' => 'VARCHAR(10) NULL AFTER section_id',
        'religion' => 'VARCHAR(50) NULL AFTER address',
        'caste' => 'VARCHAR(100) NULL AFTER religion',
        'mobile_number' => 'VARCHAR(20) NULL AFTER caste',
        'email' => 'VARCHAR(190) NULL AFTER mobile_number',
        'blood_group' => 'VARCHAR(5) NULL AFTER email',
        'height' => 'DECIMAL(5,2) NULL AFTER blood_group',
        'weight' => 'DECIMAL(5,2) NULL AFTER height',
        'measurement_date' => 'DATE NULL AFTER weight',
        'medical_history' => 'TEXT NULL AFTER measurement_date',
        'current_address' => 'TEXT NULL AFTER medical_history',
        'permanent_address' => 'TEXT NULL AFTER current_address',
        'national_id' => 'VARCHAR(50) NULL AFTER permanent_address',
        'local_id' => 'VARCHAR(50) NULL AFTER national_id',
        'note' => 'TEXT NULL AFTER local_id',
        'house_id' => 'BIGINT UNSIGNED NULL AFTER note',
        'transport_route_id' => 'BIGINT UNSIGNED NULL AFTER house_id',
        'transport_stop_id' => 'BIGINT UNSIGNED NULL AFTER transport_route_id',
        'transport_fees_month' => 'VARCHAR(20) NULL AFTER transport_stop_id',
        'hostel_room_id' => 'BIGINT UNSIGNED NULL AFTER transport_fees_month',
        'father_name' => 'VARCHAR(150) NULL AFTER hostel_room_id',
        'father_phone' => 'VARCHAR(20) NULL AFTER father_name',
        'father_occupation' => 'VARCHAR(100) NULL AFTER father_phone',
        'father_photo' => 'VARCHAR(255) NULL AFTER father_occupation',
        'mother_name' => 'VARCHAR(150) NULL AFTER father_photo',
        'mother_phone' => 'VARCHAR(20) NULL AFTER mother_name',
        'mother_occupation' => 'VARCHAR(100) NULL AFTER mother_phone',
        'mother_photo' => 'VARCHAR(255) NULL AFTER mother_occupation',
    ],
    'guardians' => [
        'photo' => 'VARCHAR(255) NULL AFTER nid',
    ],
];
foreach ($newColumns as $table => $tableColumns) {
    $existing = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tableColumns as $column => $definition) {
        if (in_array($column, $existing, true)) {
            continue;
        }
        $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        $added++;
    }
}

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

echo 'Schema applied. ', $applied, ' statement(s) executed';
echo ', ', $branchColumnsAdded, ' branch column(s) added';
echo $added > 0 ? ', ' . $added . ' column(s) added.' : ', no columns added.';
echo PHP_EOL;
echo 'Tables in ', $config['database'], ': ', count($tables), "\n  ", implode(', ', $tables), "\n";
