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
    'fee_heads', 'fee_structures', 'fee_collections', 'fee_discounts', 'fee_carry_forwards',
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
$pdo->exec("UPDATE academic_reports ar SET ar.exam_id = (SELECT e.id FROM exams e LEFT JOIN academic_years ay ON ay.id = e.academic_year_id WHERE e.name = ar.exam_name AND (e.academic_year_id IS NULL OR ay.name = ar.academic_year) ORDER BY (e.academic_year_id IS NULL), e.id LIMIT 1) WHERE ar.exam_id IS NULL");
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

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

echo 'Schema applied. ', $applied, ' statement(s) executed';
echo ', ', $branchColumnsAdded, ' branch column(s) added';
echo $added > 0 ? ', ' . $added . ' column(s) added.' : ', no columns added.';
echo PHP_EOL;
echo 'Tables in ', $config['database'], ': ', count($tables), "\n  ", implode(', ', $tables), "\n";
