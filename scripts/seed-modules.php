<?php
declare(strict_types=1);

/**
 * Seed the modules added on top of the original student register.
 *
 * Every insert is guarded by a lookup on a natural key, so running the script
 * again only fills in what is missing and never duplicates a record.
 *
 * Usage: php scripts/seed-modules.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$config = require BASE_PATH . '/config/database.php';
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['database'], $config['charset'] ?? 'utf8mb4');
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$inserted = 0;

/** Insert a row once, keyed by the given column. */
function put(string $table, string $keyColumn, string $keyValue, array $row): void
{
    putWhere($table, $keyColumn . ' = :lookup', ['lookup' => $keyValue], $row);
}

/** Insert a row once, matched by an arbitrary condition. */
function putWhere(string $table, string $condition, array $lookup, array $row): void
{
    global $pdo, $inserted;

    $find = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE ' . $condition . ' LIMIT 1');
    $find->execute($lookup);
    if ($find->fetchColumn() !== false) {
        return;
    }

    $columns = array_keys($row);
    $statement = $pdo->prepare(
        'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')'
    );
    $statement->execute($row);
    $inserted++;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

// ---------------------------------------------------------------------------
// Subjects, following the NCTB curriculum
// ---------------------------------------------------------------------------

$subjects = [
    ['name' => 'Bangla', 'name_bn' => 'বাংলা', 'code' => 'BAN'],
    ['name' => 'English', 'name_bn' => 'ইংরেজি', 'code' => 'ENG'],
    ['name' => 'Mathematics', 'name_bn' => 'গণিত', 'code' => 'MATH'],
    ['name' => 'BGS', 'name_bn' => 'বাংলাদেশ ও বিশ্বপরিচিতি', 'code' => 'BGS'],
    ['name' => 'Science', 'name_bn' => 'বিজ্ঞান', 'code' => 'SCI'],
    ['name' => 'Religion', 'name_bn' => 'ধর্ম', 'code' => 'REL'],
    ['name' => 'ICT', 'name_bn' => 'তথ্য ও যোগাযোগ প্রযুক্তি', 'code' => 'ICT'],
    ['name' => 'Arts & Crafts', 'name_bn' => 'চারুকলা', 'code' => 'ART'],
    ['name' => 'Physical Education', 'name_bn' => 'শারীরিক শিক্ষা', 'code' => 'PE'],
    ['name' => 'Agriculture', 'name_bn' => 'কৃষি শিক্ষা', 'code' => 'AGR'],
    ['name' => 'Life & Livelihood', 'name_bn' => 'জীবন ও জীবিকা', 'code' => 'LNL'],
    ['name' => 'Arabic', 'name_bn' => 'আরবি', 'code' => 'ARB'],
    ['name' => 'Social Science', 'name_bn' => 'সমাজ বিজ্ঞান', 'code' => 'SS'],
];

$subjectIds = [];
foreach ($subjects as $subject) {
    $find = $pdo->prepare('SELECT id FROM subjects WHERE name = :name LIMIT 1');
    $find->execute(['name' => $subject['name']]);
    $id = $find->fetchColumn();
    if ($id === false) {
        $statement = $pdo->prepare('INSERT INTO subjects (name, name_bn, code, type, created_at) VALUES (:name, :name_bn, :code, :type, :created_at)');
        $statement->execute($subject + ['type' => 'core', 'created_at' => now()]);
        $id = $pdo->lastInsertId();
        $inserted++;
    }
    $subjectIds[$subject['name']] = (int) $id;
}

// ---------------------------------------------------------------------------
// Class and subject mapping
// ---------------------------------------------------------------------------

$primary = ['Bangla', 'English', 'Mathematics', 'BGS', 'Science', 'Religion', 'Arts & Crafts', 'Physical Education'];
$junior = ['Bangla', 'English', 'Mathematics', 'BGS', 'Science', 'Religion', 'ICT', 'Arts & Crafts', 'Physical Education', 'Agriculture', 'Life & Livelihood'];
$secondary = ['Bangla', 'English', 'Mathematics', 'BGS', 'Science', 'Religion', 'ICT', 'Agriculture', 'Life & Livelihood', 'Arabic', 'Social Science'];

$mapping = [
    1 => $primary, 2 => $primary, 3 => $primary, 4 => $primary, 5 => $primary,
    6 => $junior, 7 => $junior, 8 => $junior,
    9 => $secondary, 10 => $secondary,
];

$classIds = [];
foreach ($pdo->query('SELECT id, numeric_level FROM classes') as $class) {
    $classIds[(int) $class['numeric_level']] = (int) $class['id'];
}

foreach ($mapping as $level => $names) {
    if (!isset($classIds[$level])) {
        continue;
    }
    $sortOrder = 0;
    foreach ($names as $name) {
        putWhere('class_subject', 'class_id = :class_id AND subject_id = :subject_id', [
            'class_id' => $classIds[$level],
            'subject_id' => $subjectIds[$name],
        ], [
            'class_id' => $classIds[$level],
            'subject_id' => $subjectIds[$name],
            'full_marks' => 100,
            'written_marks' => 60,
            'mcq_marks' => 30,
            'practical_marks' => in_array($name, ['Science', 'ICT'], true) ? 20 : null,
            'ca_marks' => 10,
            'pass_marks' => 33,
            'sort_order' => $sortOrder,
            'created_at' => now(),
        ]);
        $sortOrder++;
    }
}

// ---------------------------------------------------------------------------
// Designations and teachers
// ---------------------------------------------------------------------------

$designations = [
    ['name' => 'Head Teacher', 'description' => 'Leads the academic and administrative team'],
    ['name' => 'Senior Teacher', 'description' => 'Senior member of the teaching staff'],
    ['name' => 'Assistant Teacher', 'description' => 'Supports the teaching and supervision duties'],
    ['name' => 'Lecturer', 'description' => 'Teaches the secondary and higher classes'],
    ['name' => 'Office Assistant', 'description' => 'Handles front office and records'],
];

foreach ($designations as $designation) {
    put('designations', 'name', $designation['name'], $designation + ['is_active' => 1, 'created_at' => now()]);
}

$teachers = [
    ['name' => 'Prof. Md. Azizul Haque', 'department' => 'Bangla', 'designation' => 'Senior Teacher'],
    ['name' => 'Mrs. Shamima Akhter', 'department' => 'English', 'designation' => 'Assistant Teacher'],
    ['name' => 'Md. Abdur Rouf', 'department' => 'Mathematics', 'designation' => 'Senior Teacher'],
    ['name' => 'Mrs. Nasima Khatun', 'department' => 'Science', 'designation' => 'Lecturer'],
    ['name' => 'Md. Shahidul Islam', 'department' => 'BGS', 'designation' => 'Assistant Teacher'],
    ['name' => 'Md. Ruhul Amin', 'department' => 'Religion', 'designation' => 'Senior Teacher'],
    ['name' => 'Mrs. Jahanara Begum', 'department' => 'ICT', 'designation' => 'Lecturer'],
    ['name' => 'Md. Delwar Hossain', 'department' => 'Physical Education', 'designation' => 'Assistant Teacher'],
];

$teacherIds = [];
$nextEmployee = 100;
foreach ($pdo->query('SELECT employee_id FROM teachers') as $row) {
    $number = (int) str_replace('TCH-', '', (string) $row['employee_id']);
    if ($number > $nextEmployee) {
        $nextEmployee = $number;
    }
}

foreach ($teachers as $teacher) {
    $find = $pdo->prepare('SELECT id FROM teachers WHERE name = :name LIMIT 1');
    $find->execute(['name' => $teacher['name']]);
    $id = $find->fetchColumn();
    if ($id === false) {
        $nextEmployee++;
        $statement = $pdo->prepare(
            'INSERT INTO teachers (employee_id, name, department, designation, phone, email, gender, joining_date, qualification, experience_years, district, created_at)
             VALUES (:employee_id, :name, :department, :designation, :phone, :email, :gender, :joining_date, :qualification, :experience_years, :district, :created_at)'
        );
        $statement->execute([
            'employee_id' => 'TCH-' . str_pad((string) $nextEmployee, 3, '0', STR_PAD_LEFT),
            'name' => $teacher['name'],
            'department' => $teacher['department'],
            'designation' => $teacher['designation'],
            'phone' => '017' . str_pad((string) (20000000 + $nextEmployee), 8, '0', STR_PAD_LEFT),
            'email' => null,
            'gender' => str_starts_with($teacher['name'], 'Mrs.') || str_starts_with($teacher['name'], 'Prof.') ? 'Female' : 'Male',
            'joining_date' => '2019-01-05',
            'qualification' => 'B.Sc / M.Sc in the subject',
            'experience_years' => 5 + ($nextEmployee % 8),
            'district' => 'Chuadanga',
            'created_at' => now(),
        ]);
        $id = $pdo->lastInsertId();
        $inserted++;
    }
    $teacherIds[$teacher['department']] = (int) $id;
}

// ---------------------------------------------------------------------------
// Guardians
// ---------------------------------------------------------------------------

$guardians = [
    ['name' => 'Md. Abdur Rahman', 'name_bn' => 'মোঃ আব্দুর রহমান', 'occupation' => 'Govt. Officer', 'relation' => 'Father', 'district' => 'Dhaka'],
    ['name' => 'Mrs. Salma Begum', 'name_bn' => 'মিসেস সালমা বেগম', 'occupation' => 'Teacher', 'relation' => 'Mother', 'district' => 'Dhaka'],
    ['name' => 'Md. Kamal Hossain', 'name_bn' => 'মোঃ কামাল হোসেন', 'occupation' => 'Businessman', 'relation' => 'Father', 'district' => 'Chattogram'],
    ['name' => 'Md. Nurul Islam', 'name_bn' => 'মোঃ নুরুল ইসলাম', 'occupation' => 'Doctor', 'relation' => 'Father', 'district' => 'Rajshahi'],
    ['name' => 'Mrs. Farida Yasmin', 'name_bn' => 'মিসেস ফরিদা ইয়াসমিন', 'occupation' => 'Housewife', 'relation' => 'Mother', 'district' => 'Khulna'],
    ['name' => 'Md. Abdul Malek', 'name_bn' => 'মোঃ আব্দুল মালেক', 'occupation' => 'Engineer', 'relation' => 'Father', 'district' => 'Sylhet'],
    ['name' => 'Md. Jahangir Alam', 'name_bn' => 'মোঃ জাহাঙ্গীর আলম', 'occupation' => 'Banker', 'relation' => 'Father', 'district' => 'Barishal'],
    ['name' => 'Mrs. Nasrin Akhter', 'name_bn' => 'মিসেস নাসরিন আক্তার', 'occupation' => 'Nurse', 'relation' => 'Mother', 'district' => 'Rangpur'],
    ['name' => 'Md. Shafiqul Islam', 'name_bn' => 'মোঃ শাফিকুল ইসলাম', 'occupation' => 'Lawyer', 'relation' => 'Father', 'district' => 'Mymensingh'],
    ['name' => 'Md. Abu Bakkar Siddique', 'name_bn' => 'মোঃ আবু বক্কর সিদ্দিক', 'occupation' => 'Businessman', 'relation' => 'Father', 'district' => 'Dhaka'],
];

foreach ($guardians as $index => $guardian) {
    $phone = '01711' . str_pad((string) (111111 + $index), 6, '0', STR_PAD_LEFT);
    put('guardians', 'phone', $phone, [
        'name' => $guardian['name'],
        'name_bn' => $guardian['name_bn'],
        'phone' => $phone,
        'occupation' => $guardian['occupation'],
        'relation' => $guardian['relation'],
        'address' => 'Chuadanga, Bangladesh',
        'district' => $guardian['district'],
        'created_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Student houses
// ---------------------------------------------------------------------------

$houses = [
    ['name' => 'Robin', 'name_bn' => 'রবিন', 'color' => '#ef4444', 'description' => 'Red house'],
    ['name' => 'Falcon', 'name_bn' => 'ফালকন', 'color' => '#3b82f6', 'description' => 'Blue house'],
    ['name' => 'Tiger', 'name_bn' => 'টাইগার', 'color' => '#f59e0b', 'description' => 'Orange house'],
    ['name' => 'Bear', 'name_bn' => 'বিয়ার', 'color' => '#10b981', 'description' => 'Green house'],
];

foreach ($houses as $house) {
    put('student_houses', 'name', $house['name'], $house + ['created_at' => now()]);
}

// ---------------------------------------------------------------------------
// Teacher assignments
// ---------------------------------------------------------------------------

$assignments = [
    ['Bangla', 5, true],
    ['English', 4, true],
    ['Mathematics', 3, true],
    ['Science', 2, true],
    ['BGS', 1, true],
    ['Religion', 1, false],
    ['ICT', 2, false],
    ['Physical Education', 3, false],
];

foreach ($assignments as [$department, $level, $isClassTeacher]) {
    if (!isset($teacherIds[$department], $classIds[$level], $subjectIds[$department])) {
        continue;
    }
    putWhere('teacher_subject', 'teacher_id = :teacher_id AND class_id = :class_id AND subject_id = :subject_id', [
        'teacher_id' => $teacherIds[$department],
        'class_id' => $classIds[$level],
        'subject_id' => $subjectIds[$department],
    ], [
        'teacher_id' => $teacherIds[$department],
        'class_id' => $classIds[$level],
        'subject_id' => $subjectIds[$department],
        'is_class_teacher' => $isClassTeacher ? 1 : 0,
        'created_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Branches
// ---------------------------------------------------------------------------

put('branches', 'name', 'Main Campus', [
    'name' => 'Main Campus',
    'address' => 'Holding No. 00-0865, Gulshan Para, Chuadanga',
    'phone' => '01700000000',
    'email' => 'webspaceit@gmail.com',
    'is_main' => 1,
    'status' => 'active',
    'created_at' => now(),
]);

// ---------------------------------------------------------------------------
// Communication
// ---------------------------------------------------------------------------

$adminId = (int) ($pdo->query("SELECT id FROM users WHERE role IN ('superadmin','admin') ORDER BY id LIMIT 1")->fetchColumn() ?: 0);

$notices = [
    ['title' => 'Annual sports day', 'content' => 'The annual sports day will be held on the school field. All students must report by 8:00 AM in their house uniform.', 'type' => 'event', 'published_date' => '2026-11-02'],
    ['title' => 'Half yearly examination schedule', 'content' => 'The half yearly examination routine has been published. Students should collect their admit cards from the office.', 'type' => 'exam', 'published_date' => '2026-11-10'],
    ['title' => 'Parent teacher meeting', 'content' => 'Parents are invited to meet the class teachers this Saturday between 9:00 AM and 1:00 PM.', 'type' => 'general', 'published_date' => '2026-10-26'],
    ['title' => 'Library week', 'content' => 'A book exhibition and reading competition will be organised during the school week.', 'type' => 'general', 'published_date' => '2026-11-16'],
];

foreach ($notices as $notice) {
    put('notices', 'title', $notice['title'], [
        'title' => $notice['title'],
        'content' => $notice['content'],
        'type' => $notice['type'],
        'published_date' => $notice['published_date'],
        'is_published' => 1,
        'created_by' => $adminId,
        'created_at' => now(),
    ]);
}

$holidays = [
    ['Eid holiday', '2026-12-02', 'School closed for Eid-ul-Fitr.'],
    ['National Mourning Day', '2026-08-15', 'School closed to mark the death anniversary of the father of the nation.'],
    ['Annual sports day', '2026-11-02', 'School closed for the annual sports day.'],
    ['Mid term break', '2026-10-24', 'One week mid term holiday.'],
];

foreach ($holidays as $holiday) {
    put('holidays', 'title', $holiday[0], [
        'title' => $holiday[0],
        'date' => $holiday[1],
        'description' => $holiday[2],
        'created_at' => now(),
    ]);
}

$events = [
    ['Science fair', 'Exhibition of student projects across every class.', '2026-11-08', '2026-11-09', '#06b6d4'],
    ['Debate competition', 'Inter class debate on the national day theme.', '2026-12-14', '2026-12-14', '#f59e0b'],
    ['Annual sports day', 'Track and field events for every house.', '2026-11-02', '2026-11-02', '#ef4444'],
    ['Science fair', 'Exhibition of student projects.', '2027-01-12', '2027-01-13', '#10b981'],
];

foreach ($events as $event) {
    put('events', 'title', $event[0], [
        'title' => $event[0],
        'description' => $event[1],
        'start_date' => $event[2],
        'end_date' => $event[3],
        'color' => $event[4],
        'created_at' => now(),
    ]);
}

$complaints = [
    ['Rahim Uddin', '01712121212', 'The drinking water supply in the second floor is not working properly.', 'parent', 'pending'],
    ['Salma Akter', '01713131313', 'The library shelves are out of reach for the senior classes.', 'student', 'investigating'],
    ['Md. Jasim', '01714141414', 'The school bus on the eastern route arrives late on weekdays.', 'other', 'resolved'],
];

foreach ($complaints as $complaint) {
    put('complaints', 'complainant_phone', $complaint[1], [
        'complainant_name' => $complaint[0],
        'complainant_phone' => $complaint[1],
        'description' => $complaint[2],
        'type' => $complaint[3],
        'status' => $complaint[4],
        'created_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Study materials and lesson plans
// ---------------------------------------------------------------------------

$materials = [
    ['Bangla grammar notes', 'Complete grammar reference for the secondary classes.', 'BAN', 6],
    ['English writing guide', 'Paragraph, essay and letter writing guide with examples.', 'ENG', 7],
    ['Mathematics formula sheet', 'Formulas and worked examples for chapters one to eight.', 'MATH', 8],
    ['Science practical handbook', 'Laboratory safety and the list of experiments.', 'SCI', 9],
];

foreach ($materials as $material) {
    put('study_materials', 'title', $material[0], [
        'title' => $material[0],
        'description' => $material[1],
        'file_type' => 'PDF',
        'class_id' => $classIds[$material[3]] ?? null,
        'subject_id' => $subjectIds[$material[2]] ?? null,
        'created_at' => now(),
    ]);
}

$lessons = [
    ['Introduction to the Bengali sentence', 'A sentence is a complete thought that follows the subject and predicate pattern.', 'Bangla', 6],
    ['Writing a formal letter', 'The parts of a formal letter and the layout expected in examinations.', 'English', 7],
    ['Fractions and their operations', 'Adding, subtracting and comparing fractions with unlike denominators.', 'Mathematics', 8],
    ['The water cycle', 'Evaporation, condensation, precipitation and collection.', 'Science', 9],
];

foreach ($lessons as $lesson) {
    put('lesson_plans', 'topic', $lesson[0], [
        'topic' => $lesson[0],
        'topic_overview' => $lesson[1],
        'lesson_breakdown' => "1. Introduction\n2. Guided practice\n3. Independent work\n4. Review and homework",
        'class_id' => $classIds[$lesson[3]] ?? null,
        'subject_id' => $subjectIds[$lesson[2]] ?? null,
        'lesson_date' => '2026-10-20',
        'created_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// School settings
// ---------------------------------------------------------------------------

$settings = [
    'school_name' => 'WSIT School Management System',
    'school_name_bn' => 'WSIT স্কুল ম্যানেজমেন্ট সিস্টেম',
    'school_address' => 'Holding No. 00-0865, Gulshan Para, Chuadanga',
    'school_address_bn' => 'হোল্ডিং নং ০০-০৮৬৫, গুলশান পাড়া, চুয়াডাঙ্গা',
    'school_phone' => '01700000000',
    'school_email' => 'webspaceit@gmail.com',
    'school_established' => '2000',
    'school_eiin' => '123456',
    'school_footer' => 'Powered by: Web Space IT',
    'school_footer_url' => 'https://webspaceit.com',
    'currency' => 'BDT',
    'admission_prefix' => 'STU',
];

$settingStatement = $pdo->prepare('INSERT INTO school_settings (`key`, value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE value = VALUES(value)');
foreach ($settings as $key => $value) {
    $settingStatement->execute(['key' => $key, 'value' => $value]);
}

echo 'Module data seeded: ', $inserted, " new record(s).\n";

$report = [
    'subjects' => 'subjects', 'class_subject' => 'class subject mapping', 'teachers' => 'teachers',
    'designations' => 'designations', 'guardians' => 'guardians', 'student_houses' => 'student houses',
    'teacher_subject' => 'teacher assignments', 'branches' => 'branches', 'notices' => 'notices',
    'holidays' => 'holidays', 'events' => 'events', 'complaints' => 'complaints',
    'study_materials' => 'study materials', 'lesson_plans' => 'lesson plans',
];

echo "\n";
foreach ($report as $table => $label) {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
    printf("  %-22s %4d\n", $label, $count);
}
