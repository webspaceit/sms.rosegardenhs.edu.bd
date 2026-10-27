<?php
declare(strict_types=1);

/**
 * Seed the finance, attendance, facilities, exam and result modules.
 *
 * Like the other seeders, every insert is guarded by a lookup so the script
 * can be run more than once without creating duplicates.
 *
 * Usage: php scripts/seed-records.php
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

/** Insert a row once, matched by an arbitrary condition. */
function putWhere(string $table, string $condition, array $lookup, array $row): int
{
    global $pdo, $inserted;

    $find = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE ' . $condition . ' LIMIT 1');
    $find->execute($lookup);
    $id = $find->fetchColumn();
    if ($id !== false) {
        return (int) $id;
    }

    $columns = array_keys($row);
    $statement = $pdo->prepare(
        'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')'
    );
    $statement->execute($row);
    $inserted++;

    return (int) $pdo->lastInsertId();
}

function put(string $table, string $keyColumn, string $keyValue, array $row): int
{
    return putWhere($table, $keyColumn . ' = :lookup', ['lookup' => $keyValue], $row);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function idOf(string $table, string $column, string $value): int
{
    global $pdo;
    $statement = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE ' . $column . ' = :value LIMIT 1');
    $statement->execute(['value' => $value]);
    $id = $statement->fetchColumn();
    return $id === false ? 0 : (int) $id;
}

$adminId = (int) ($pdo->query("SELECT id FROM users WHERE role IN ('superadmin','admin') ORDER BY id LIMIT 1")->fetchColumn() ?: 0);
$yearId = (int) ($pdo->query('SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1')->fetchColumn() ?: 0);
$yearName = (string) ($pdo->query('SELECT name FROM academic_years WHERE is_current = 1 LIMIT 1')->fetchColumn() ?: date('Y'));

// ---------------------------------------------------------------------------
// Fees
// ---------------------------------------------------------------------------

$feeHeads = [
    ['name' => 'Tuition Fee', 'name_bn' => 'বেতন', 'frequency' => 'monthly', 'sort_order' => 1],
    ['name' => 'Admission Fee', 'name_bn' => 'ভর্তি ফি', 'frequency' => 'one-time', 'sort_order' => 2],
    ['name' => 'Development Fee', 'name_bn' => 'উন্নয়ন ফি', 'frequency' => 'yearly', 'sort_order' => 3],
    ['name' => 'Examination Fee', 'name_bn' => 'পরীক্ষা ফি', 'frequency' => 'yearly', 'sort_order' => 4],
    ['name' => 'Laboratory Fee', 'name_bn' => 'ল্যাবরেটরি ফি', 'frequency' => 'monthly', 'sort_order' => 5],
    ['name' => 'Library Fee', 'name_bn' => 'গ্রন্থাগার ফি', 'frequency' => 'monthly', 'sort_order' => 6],
    ['name' => 'Sports Fee', 'name_bn' => 'ক্রীড়া ফি', 'frequency' => 'monthly', 'sort_order' => 7],
    ['name' => 'ICT Fee', 'name_bn' => 'আইসিটি ফি', 'frequency' => 'monthly', 'sort_order' => 8],
];

$feeHeadIds = [];
foreach ($feeHeads as $head) {
    $feeHeadIds[$head['name']] = put('fee_heads', 'name', $head['name'], $head + ['is_optional' => 0, 'created_at' => now()]);
}

// A fee structure for every class, based on the level.
$classIds = [];
foreach ($pdo->query('SELECT id, numeric_level FROM classes ORDER BY numeric_level') as $class) {
    $classIds[(int) $class['numeric_level']] = (int) $class['id'];
}

$baseAmounts = [
    'Tuition Fee' => 500,
    'Development Fee' => 1500,
    'Examination Fee' => 800,
    'Laboratory Fee' => 200,
    'Library Fee' => 100,
    'Sports Fee' => 150,
    'ICT Fee' => 150,
];

foreach ($classIds as $level => $classId) {
    foreach ($baseAmounts as $headName => $base) {
        $amount = $base + ($level * 50);
        putWhere('fee_structures', 'class_id = :class_id AND fee_head_id = :fee_head_id', [
            'class_id' => $classId,
            'fee_head_id' => $feeHeadIds[$headName],
        ], [
            'class_id' => $classId,
            'fee_head_id' => $feeHeadIds[$headName],
            'amount' => $amount,
            'created_at' => now(),
        ]);
    }
}

// Fee collections for the students, paid and due.
$students = $pdo->query('SELECT id, name FROM students ORDER BY id')->fetchAll();
$invoice = (int) $pdo->query('SELECT COUNT(*) FROM fee_collections')->fetchColumn() + 1;

foreach ($students as $index => $student) {
    $paid = $index % 4 !== 0;
    $month = '2026-01';
    $headId = $feeHeadIds['Tuition Fee'];

    // Keyed by the student, the fee head and the month so a second run does
    // not create a second invoice for the same fee.
    $existing = $pdo->prepare(
        'SELECT id, invoice_no FROM fee_collections WHERE student_id = :student_id AND fee_head_id = :fee_head_id AND month = :month LIMIT 1'
    );
    $existing->execute(['student_id' => (int) $student['id'], 'fee_head_id' => $headId, 'month' => $month]);
    $row = $existing->fetch();

    if ($row !== false) {
        continue;
    }

    put('fee_collections', 'invoice_no', sprintf('INV-%s-%04d', $yearName, $invoice), [
        'student_id' => (int) $student['id'],
        'fee_head_id' => $headId,
        'academic_year_id' => $yearId,
        'invoice_no' => sprintf('INV-%s-%04d', $yearName, $invoice),
        'month' => $month,
        'amount' => 500 + ((int) $index % 5) * 50,
        'status' => $paid ? 'paid' : 'due',
        'due_date' => '2026-01-15',
        'paid_on' => $paid ? '2026-01-10' : null,
        'payment_method' => $paid ? 'Cash' : null,
        'created_at' => now(),
    ]);
    $invoice++;
}

// A discount and a carry forward.
if ($students !== []) {
    putWhere('fee_discounts', 'student_id = :student_id', ['student_id' => (int) $students[0]['id']], [
        'student_id' => (int) $students[0]['id'],
        'fee_head_id' => $feeHeadIds['Tuition Fee'],
        'amount' => 200,
        'reason' => 'Sibling discount',
        'approved_by' => $adminId,
        'date' => '2026-01-05',
        'created_at' => now(),
    ]);

    putWhere('fee_carry_forwards', 'student_id = :student_id', ['student_id' => (int) $students[1]['id']], [
        'student_id' => (int) $students[1]['id'],
        'academic_year_id' => $yearId,
        'amount' => 350,
        'reason' => 'Unpaid from the previous year',
        'date' => '2026-01-05',
        'created_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Accounts
// ---------------------------------------------------------------------------

$transactions = [
    ['expense', 'Electricity bill', 8500, 'Monthly power bill', '2026-01-28', 'Bank'],
    ['expense', 'Stationery', 3200, 'Registers and chalk for the term', '2026-01-22', 'Cash'],
    ['income', 'Donation', 25000, 'Donation from the parents committee', '2026-01-18', 'Cash'],
    ['expense', 'Repairs', 6400, 'Classroom floor repairs', '2026-02-04', 'Cash'],
    ['income', 'Admission income', 45000, 'Admission charges for the new session', '2026-02-10', 'Bank'],
    ['expense', 'Sports equipment', 9800, 'Cricket and football gear', '2026-02-14', 'Cash'],
];

foreach ($transactions as $transaction) {
    putWhere('transactions', 'category = :category AND date = :date', [
        'category' => $transaction[1],
        'date' => $transaction[4],
    ], [
        'type' => $transaction[0],
        'category' => $transaction[1],
        'amount' => $transaction[2],
        'description' => $transaction[3],
        'date' => $transaction[4],
        'payment_method' => $transaction[5],
        'user_id' => $adminId,
        'created_at' => now(),
    ]);
}

put('bank_reconciliations', 'account_name', 'School operating account', [
    'account_name' => 'School operating account',
    'account_number' => '0123456789',
    'statement_date' => '2026-02-28',
    'ending_balance' => 185000,
    'cleared_balance' => 182400,
    'difference' => 2600,
    'status' => 'discrepancy',
    'notes' => 'Two cheques not yet presented',
    'created_at' => now(),
]);

// ---------------------------------------------------------------------------
// Payroll
// ---------------------------------------------------------------------------

$teacherIds = $pdo->query('SELECT id FROM teachers ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
$payroll = 0;
foreach ($teacherIds as $teacherId) {
    $payroll++;
    putWhere('salary_structures', 'teacher_id = :teacher_id AND effective_from = :effective_from', [
        'teacher_id' => (int) $teacherId,
        'effective_from' => '2026-01-01',
    ], [
        'teacher_id' => (int) $teacherId,
        'basic_salary' => 18000 + ($payroll * 500),
        'house_rent' => 5000,
        'medical' => 1500,
        'transport_allowance' => 1200,
        'other_allowances' => 800,
        'deductions' => 0,
        'notes' => 'Monthly structure',
        'effective_from' => '2026-01-01',
        'created_at' => now(),
    ]);

    $gross = 18000 + ($payroll * 500) + 5000 + 1500 + 1200 + 800;
    putWhere('salary_payments', 'teacher_id = :teacher_id AND month = :month AND year = :year', [
        'teacher_id' => (int) $teacherId,
        'month' => 'January',
        'year' => '2026',
    ], [
        'teacher_id' => (int) $teacherId,
        'month' => 'January',
        'year' => '2026',
        'gross_salary' => $gross,
        'deductions' => 0,
        'net_pay' => $gross,
        'paid_date' => '2026-01-28',
        'payment_method' => 'Bank',
        'reference_no' => 'SAL-' . $yearName . '-01-' . str_pad((string) $payroll, 3, '0', STR_PAD_LEFT),
        'notes' => null,
        'created_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Attendance
// ---------------------------------------------------------------------------

$dates = ['2026-02-02', '2026-02-03', '2026-02-04', '2026-02-05', '2026-02-08'];
$statuses = ['present', 'present', 'present', 'late', 'absent'];

foreach ($students as $index => $student) {
    foreach ($dates as $day => $date) {
        $status = $statuses[($index + $day) % count($statuses)];
        putWhere('attendances', 'student_id = :student_id AND date = :date', [
            'student_id' => (int) $student['id'],
            'date' => $date,
        ], [
            'student_id' => (int) $student['id'],
            'date' => $date,
            'status' => $status,
            'remarks' => $status === 'absent' ? 'Absent without leave' : null,
            'marked_by' => $adminId > 0 ? $adminId : null,
            'created_at' => now(),
        ]);
    }
}

foreach ($teacherIds as $index => $teacherId) {
    foreach ($dates as $day => $date) {
        $status = $statuses[($index + $day) % count($statuses)];
        putWhere('teacher_attendances', 'teacher_id = :teacher_id AND date = :date', [
            'teacher_id' => (int) $teacherId,
            'date' => $date,
        ], [
            'teacher_id' => (int) $teacherId,
            'date' => $date,
            'status' => $status,
            'remarks' => null,
            'created_at' => now(),
        ]);
    }
}

$staffIds = $pdo->query('SELECT id FROM users WHERE role NOT IN ("student","parent") AND id <> ' . $adminId)->fetchAll(PDO::FETCH_COLUMN);
foreach ($staffIds as $index => $userId) {
    foreach ($dates as $day => $date) {
        putWhere('staff_attendances', 'user_id = :user_id AND date = :date', [
            'user_id' => (int) $userId,
            'date' => $date,
        ], [
            'user_id' => (int) $userId,
            'date' => $date,
            'status' => 'present',
            'remarks' => null,
            'created_at' => now(),
        ]);
    }
}

if ($teacherIds !== []) {
    putWhere('leave_requests', 'teacher_id = :teacher_id AND start_date = :start_date', [
        'teacher_id' => (int) $teacherIds[0],
        'start_date' => '2026-02-10',
    ], [
        'teacher_id' => (int) $teacherIds[0],
        'type' => 'sick',
        'start_date' => '2026-02-10',
        'end_date' => '2026-02-12',
        'reason' => 'Fever and a doctor advised rest',
        'status' => 'pending',
        'admin_remarks' => null,
        'approved_by' => null,
        'created_at' => now(),
    ]);

    putWhere('leave_requests', 'teacher_id = :teacher_id AND start_date = :start_date', [
        'teacher_id' => (int) $teacherIds[1] ?? 0,
        'start_date' => '2026-02-16',
    ], [
        'teacher_id' => (int) $teacherIds[1],
        'type' => 'casual',
        'start_date' => '2026-02-16',
        'end_date' => '2026-02-16',
        'reason' => 'Family function',
        'status' => 'approved',
        'admin_remarks' => 'Approved',
        'approved_by' => $adminId > 0 ? $adminId : null,
        'created_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Inventory and library
// ---------------------------------------------------------------------------

$categories = [
    ['name' => 'Stationery', 'description' => 'Chalk, registers, pens and paper'],
    ['name' => 'Sports', 'description' => 'Balls, bats and nets'],
    ['name' => 'Laboratory', 'description' => 'Science laboratory supplies'],
    ['name' => 'Furniture', 'description' => 'Desks, chairs and cupboards'],
];

$categoryIds = [];
foreach ($categories as $category) {
    $categoryIds[$category['name']] = put('inventory_categories', 'name', $category['name'], $category + ['created_at' => now()]);
}

$items = [
    ['Whiteboard marker', 'Stationery', 40, 10, 120, 'Box of twelve'],
    ['Copy paper A4', 'Stationery', 25, 10, 480, 'Five reams per pack'],
    ['Cricket ball', 'Sports', 8, 4, 750, 'Match quality'],
    ['Football', 'Sports', 12, 5, 900, 'Size four'],
    ['Beaker glass 100ml', 'Laboratory', 18, 6, 240, 'Borosilicate'],
    ['Test tube rack', 'Laboratory', 10, 4, 350, 'Holds twenty tubes'],
    ['Student desk', 'Furniture', 30, 10, 2200, 'Single seater'],
    ['Steel chair', 'Furniture', 45, 15, 950, 'Stackable'],
];

foreach ($items as $item) {
    putWhere('inventory_items', 'name = :name AND category_id = :category_id', [
        'name' => $item[0],
        'category_id' => $categoryIds[$item[1]],
    ], [
        'name' => $item[0],
        'category_id' => $categoryIds[$item[1]],
        'quantity' => $item[2],
        'minimum_quantity' => $item[3],
        'unit_price' => $item[4],
        'description' => $item[5],
        'created_at' => now(),
    ]);
}

$books = [
    ['Bangla Sahitya Bishwakosh', 'Dr. Shamsuzzaman Khan', 'Bangla Academy', '978-984-01', '3rd'],
    ['English Grammar in Use', 'Raymond Murphy', 'Cambridge University Press', '978-0521', '5th'],
    ['Mathematics for Class 5', 'Board of Education', 'Bangladesh Education Board', '978-111', '2nd'],
    ['General Science', 'A. K. Mukherjee', 'Deyal', '978-222', '1st'],
    ['Bangladesh and the World', 'M. A. Islam', 'Bangla Academy', '978-333', '2nd'],
    ['Computer Basics', 'N. N. Alam', 'Banglapedia', '978-444', '1st'],
    ['Islamic Studies', 'Maulana Fazlul Karim', 'Islamic Foundation', '978-555', '4th'],
    ['Social Science Primer', 'National Curriculum Centre', 'NCB', '978-666', '1st'],
];

$bookIds = [];
foreach ($books as $book) {
    $bookIds[] = put('books', 'title', $book[0], [
        'title' => $book[0],
        'author' => $book[1],
        'publisher' => $book[2],
        'isbn' => $book[3],
        'edition' => $book[4],
        'quantity' => 12,
        'available' => 12,
        'shelf_location' => 'A' . (count($bookIds) + 1),
        'created_at' => now(),
    ]);
}

if ($bookIds !== [] && $students !== []) {
    putWhere('book_issues', 'book_id = :book_id AND student_id = :student_id', [
        'book_id' => $bookIds[0],
        'student_id' => (int) $students[0]['id'],
    ], [
        'book_id' => $bookIds[0],
        'student_id' => (int) $students[0]['id'],
        'teacher_id' => null,
        'issue_date' => '2026-02-02',
        'due_date' => '2026-02-16',
        'return_date' => null,
        'fine' => 0,
        'status' => 'issued',
        'created_at' => now(),
    ]);

    putWhere('book_issues', 'book_id = :book_id AND student_id = :student_id', [
        'book_id' => $bookIds[1],
        'student_id' => (int) $students[1]['id'],
    ], [
        'book_id' => $bookIds[1],
        'student_id' => (int) $students[1]['id'],
        'teacher_id' => null,
        'issue_date' => '2026-01-10',
        'due_date' => '2026-01-24',
        'return_date' => '2026-01-26',
        'fine' => 20,
        'status' => 'returned',
        'created_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Transport and hostel
// ---------------------------------------------------------------------------

$routes = [
    ['Gulshan Para route', 'Md. Jasim', '01711000001', 'Dhaka 11-2233', 500],
    ['Station route', 'Md. Rafiq', '01711000002', 'Dhaka 11-4455', 400],
    ['Gulshan line', 'Md. Sadek', '01711000003', 'Dhaka 11-6677', 450],
    ['Market route', 'Md. Hasan', '01711000004', 'Dhata 11-8899', 350],
    ['School campus route', 'Md. Bablu', '01711000005', 'Dhata 11-1212', 300],
];

$routeIds = [];
foreach ($routes as $route) {
    $routeIds[$route[0]] = put('transport_routes', 'name', $route[0], [
        'name' => $route[0],
        'driver_name' => $route[1],
        'driver_phone' => $route[2],
        'vehicle_no' => $route[3],
        'fare' => $route[4],
        'description' => null,
        'created_at' => now(),
    ]);
}

$stops = [
    ['Gulshan Para route', 'Gulshan Para', '07:15', '14:10'],
    ['Gulshan Para route', 'Chuadanga Bazar', '07:30', '14:00'],
    ['Station route', 'Railway Station', '07:00', '14:30'],
    ['Station route', 'New Market', '07:20', '14:15'],
    ['Gulshan line', 'Gulshan Para', '07:10', '14:20'],
];

$stopIds = [];
foreach ($stops as $stop) {
    $key = $stop[0] . '|' . $stop[1];
    $stopIds[$key] = putWhere('transport_stops', 'transport_route_id = :route_id AND name = :name', [
        'route_id' => $routeIds[$stop[0]],
        'name' => $stop[1],
    ], [
        'transport_route_id' => $routeIds[$stop[0]],
        'name' => $stop[1],
        'pickup_time' => $stop[2],
        'drop_time' => $stop[3],
        'sort_order' => count($stopIds) + 1,
        'created_at' => now(),
    ]);
}

if ($students !== []) {
    for ($index = 0; $index < min(5, count($students)); $index++) {
        $routeName = array_keys($routeIds)[$index % count($routeIds)];
        $stopName = $stops[$index % count($stops)][1];
        putWhere('student_transport', 'student_id = :student_id AND transport_route_id = :route_id', [
            'student_id' => (int) $students[$index]['id'],
            'route_id' => $routeIds[$routeName],
        ], [
            'student_id' => (int) $students[$index]['id'],
            'transport_route_id' => $routeIds[$routeName],
            'transport_stop_id' => $stopIds[$routeName . '|' . $stopName] ?? null,
            'start_date' => '2026-01-05',
            'end_date' => null,
            'is_active' => 1,
            'created_at' => now(),
        ]);
    }
}

$hostelRooms = [
    ['A-101', 'single', 1, 2500, 'Attach bathroom'],
    ['A-102', 'double', 2, 1800, 'Attach bathroom, Balcony'],
    ['A-103', 'double', 2, 1800, 'Attach bathroom'],
    ['B-201', 'triple', 3, 1400, 'Shared bathroom'],
    ['B-202', 'triple', 3, 1400, 'Shared bathroom, Balcony'],
    ['C-301', 'dormitory', 6, 900, 'Shared bathroom, Mess'],
    ['C-302', 'dormitory', 6, 900, 'Shared bathroom'],
    ['C-303', 'dormitory', 8, 850, 'Shared bathroom, Mess'],
    ['D-401', 'single', 1, 2500, 'Attach bathroom, Balcony'],
    ['D-402', 'double', 2, 1800, 'Attach bathroom'],
];

$roomIds = [];
foreach ($hostelRooms as $room) {
    $roomIds[] = put('hostel_rooms', 'room_no', $room[0], [
        'room_no' => $room[0],
        'type' => $room[1],
        'capacity' => $room[2],
        'rent_per_bed' => $room[3],
        'facilities' => $room[4],
        'created_at' => now(),
    ]);
}

if ($students !== [] && $roomIds !== []) {
    for ($index = 0; $index < min(4, count($students)); $index++) {
        putWhere('hostel_allocations', 'student_id = :student_id AND hostel_room_id = :room_id', [
            'student_id' => (int) $students[$index]['id'],
            'room_id' => $roomIds[$index],
        ], [
            'student_id' => (int) $students[$index]['id'],
            'hostel_room_id' => $roomIds[$index],
            'start_date' => '2026-01-10',
            'end_date' => null,
            'is_active' => 1,
            'created_at' => now(),
        ]);
    }
}

// ---------------------------------------------------------------------------
// Class routines
// ---------------------------------------------------------------------------

$subjects = $pdo->query('SELECT id, name FROM subjects')->fetchAll();
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
$times = [['08:00', '08:45'], ['08:45', '09:30'], ['09:30', '10:15'], ['10:30', '11:15'], ['11:15', '12:00']];

$subjectNames = array_column($subjects, 'name');
$teacherIds = array_map('intval', $teacherIds);

foreach ($classIds as $level => $classId) {
    foreach ($days as $dayIndex => $day) {
        foreach ($times as $timeIndex => $time) {
            $subjectId = (int) $subjects[($level + $dayIndex + $timeIndex) % count($subjects)]['id'];
            putWhere('class_routines', 'class_id = :class_id AND day_of_week = :day AND start_time = :start_time', [
                'class_id' => $classId,
                'day' => $day,
                'start_time' => $time[0],
            ], [
                'class_id' => $classId,
                'section_id' => null,
                'day_of_week' => $day,
                'subject_id' => $subjectId,
                'teacher_id' => $teacherIds !== [] ? $teacherIds[($level + $dayIndex) % count($teacherIds)] : null,
                'start_time' => $time[0],
                'end_time' => $time[1],
                'room' => 'Room ' . (10 + $level),
                'created_at' => now(),
            ]);
        }
    }
}

// ---------------------------------------------------------------------------
// Exams and results
// ---------------------------------------------------------------------------

$examId = put('exams', 'name', 'Half Yearly Examination 2026', [
    'name' => 'Half Yearly Examination 2026',
    'type' => 'half-yearly',
    'academic_year_id' => $yearId,
    'start_date' => '2026-06-15',
    'end_date' => '2026-06-25',
    'description' => 'Half yearly examinations for classes one to five',
    'created_at' => now(),
]);

$examSubjects = $pdo->query('SELECT id, name FROM subjects ORDER BY id LIMIT 6')->fetchAll();
foreach ($examSubjects as $index => $subject) {
    putWhere('exam_schedules', 'exam_id = :exam_id AND subject_id = :subject_id', [
        'exam_id' => $examId,
        'subject_id' => (int) $subject['id'],
    ], [
        'exam_id' => $examId,
        'subject_id' => (int) $subject['id'],
        'class_id' => null,
        'date' => '2026-06-' . (15 + $index),
        'start_time' => '09:00',
        'end_time' => '12:00',
        'hall' => 'Hall ' . ($index + 1),
        'created_at' => now(),
    ]);
}

// Marks for every student, so the result screens have something to show.
$examName = 'Half Yearly Examination 2026';
$studentRows = $pdo->query('SELECT id, class_id, section_id, roll_no FROM students ORDER BY roll_no')->fetchAll();

foreach ($studentRows as $index => $student) {
    $classSubjects = $pdo->query(
        'SELECT subject_id FROM class_subject WHERE class_id = ' . (int) $student['class_id'] . ' ORDER BY sort_order'
    )->fetchAll(PDO::FETCH_COLUMN);

    $reportId = putWhere('academic_reports', 'student_id = :student_id AND exam_id = :exam_id', [
        'student_id' => (int) $student['id'],
        'exam_id' => $examId,
    ], [
        'student_id' => (int) $student['id'],
        'exam_id' => $examId,
        'class_position' => null,
        'working_days' => 120,
        'present_days' => 110 + ($index % 8),
        'absent_days' => 10 - ($index % 8),
        'moral_evaluation' => ['Excellent', 'Very good', 'Good', 'Satisfactory'][$index % 4],
        'co_curricular' => 'Scout, Debate club',
        'comments' => $index % 5 === 0 ? 'Needs to work on mathematics' : null,
        'created_at' => now(),
    ]);

    foreach ($classSubjects as $subjectIndex => $subjectId) {
        // A spread of results, with a few students failing one subject.
        $base = 45 + (($index * 7 + $subjectIndex * 11) % 50);
        if ($index % 9 === 0 && $subjectIndex === 1) {
            $base = 20 + ($index % 10);
        }
        $written = min(60, (int) round($base * 0.6));
        $mcq = min(30, (int) round($base * 0.3));
        $ca = (int) min(10, $base - $written - $mcq);

        putWhere('academic_report_marks', 'academic_report_id = :report_id AND subject_id = :subject_id', [
            'report_id' => $reportId,
            'subject_id' => (int) $subjectId,
        ], [
            'academic_report_id' => $reportId,
            'subject_id' => (int) $subjectId,
            'full_marks' => 100,
            'highest_marks' => 95,
            'written_marks' => $written,
            'mcq_marks' => $mcq,
            'practical_marks' => 0,
            'ca_marks' => max(0, $ca),
            'created_at' => now(),
        ]);
    }
}

// One configuration per result screen, so each screen opens with something.
$screenTypes = [
    'report' => 'Report card',
    'fail-list' => 'Fail list',
    'section-result' => 'Section result',
    'tabulation' => 'Tabulation sheet',
    'merit-list' => 'Merit list',
    'admit-card' => 'Admit card',
];

$firstClass = $pdo->query('SELECT id, name FROM classes ORDER BY numeric_level LIMIT 1')->fetch();
$firstSection = $pdo->query('SELECT name FROM sections WHERE class_id = ' . (int) $firstClass['id'] . ' ORDER BY name LIMIT 1')->fetchColumn();

foreach ($screenTypes as $type => $label) {
    putWhere('report_configs', 'type = :type AND class_name = :class_name AND exam_name = :exam_name', [
        'type' => $type,
        'class_name' => (string) $firstClass['name'],
        'exam_name' => $examName,
    ], [
        'type' => $type,
        'class_name' => (string) $firstClass['name'],
        'section' => (string) ($firstSection ?? ''),
        'exam_name' => $examName,
        'academic_year' => $yearName,
        'config' => null,
        'created_at' => now(),
    ]);
}

// Exam attendance, suggestions, certificates and online exams.
foreach ($students as $index => $student) {
    putWhere('exam_attendances', 'exam_id = :exam_id AND student_id = :student_id AND subject_id = :subject_id', [
        'exam_id' => $examId,
        'student_id' => (int) $student['id'],
        'subject_id' => (int) $examSubjects[$index % count($examSubjects)]['id'],
    ], [
        'exam_id' => $examId,
        'student_id' => (int) $student['id'],
        'subject_id' => (int) $examSubjects[$index % count($examSubjects)]['id'],
        'present' => $index % 10 === 0 ? 0 : 1,
        'remarks' => $index % 10 === 0 ? 'Medical leave' : null,
        'created_at' => now(),
    ]);
}

$examSubjectId = (int) $examSubjects[0]['id'];
put('exam_suggestions', 'title', 'Bangla paper suggestions', [
    'exam_id' => $examId,
    'subject_id' => $examSubjectId,
    'title' => 'Bangla paper suggestions',
    'suggestion' => 'Practise the poetry and prose sections, and revise the grammar chapter on sentence transformation.',
    'file_path' => null,
    'teacher_id' => $teacherIds[0] ?? null,
    'created_at' => now(),
]);

put('exam_suggestions', 'title', 'Mathematics paper suggestions', [
    'exam_id' => $examId,
    'subject_id' => (int) $examSubjects[2]['id'],
    'title' => 'Mathematics paper suggestions',
    'suggestion' => 'Focus on fractions and mensuration. Work through the past three papers.',
    'file_path' => null,
    'teacher_id' => $teacherIds[2] ?? null,
    'created_at' => now(),
]);

foreach (array_slice($students, 0, 3) as $index => $student) {
    put('certificates', 'certificate_no', 'CERT-' . $yearName . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT), [
        'student_id' => (int) $student['id'],
        'type' => ['Merit', 'Academic', 'Sports'][$index % 3],
        'certificate_no' => 'CERT-' . $yearName . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
        'issue_date' => '2026-01-20',
        'content' => 'Awarded for outstanding performance in the first term.',
        'remarks' => null,
        'created_at' => now(),
    ]);
}

$onlineExamId = put('online_exams', 'title', 'Mathematics practice quiz', [
    'title' => 'Mathematics practice quiz',
    'class_id' => $firstClass['id'],
    'subject_id' => (int) $examSubjects[2]['id'],
    'duration_minutes' => 20,
    'total_marks' => 10,
    'start_time' => '2026-02-01 09:00:00',
    'end_time' => '2026-02-28 23:59:00',
    'status' => 'active',
    'instructions' => 'Answer every question. Each question carries one mark.',
    'created_at' => now(),
]);

$questions = [
    ['What is 12 multiplied by 8?', "96\n104\n88\n112", '96'],
    ['What is a quarter of a hundred?', '20', '25'],
    ['Which shape has no corners?', 'Circle', 'Circle'],
    ['What is the next number: 2, 4, 8, 16?', '32', '32'],
    ['How many sides does a hexagon have?', '6', '6'],
];

foreach ($questions as $index => $question) {
    $questionId = putWhere('exam_questions', 'online_exam_id = :exam_id AND question = :question', [
        'exam_id' => $onlineExamId,
        'question' => $question[0],
    ], [
        'online_exam_id' => $onlineExamId,
        'question' => $question[0],
        'type' => 'mcq',
        'correct_answer' => null,
        'marks' => 2,
        'created_at' => now(),
    ]);

    $options = array_values(array_filter(array_map('trim', explode("\n", $question[1]))));
    $insertOption = $pdo->prepare('INSERT INTO exam_question_options (exam_question_id, option_text, is_correct, sort_order) VALUES (:question_id, :option_text, :is_correct, :sort_order)');
    foreach ($options as $optionIndex => $option) {
        $insertOption->execute([
            'question_id' => $questionId,
            'option_text' => $option,
            'is_correct' => strcasecmp($option, (string) $question[2]) === 0 ? 1 : 0,
            'sort_order' => $optionIndex,
        ]);
    }
}

foreach (array_slice($students, 0, 5) as $index => $student) {
    putWhere('exam_submissions', 'online_exam_id = :exam_id AND student_id = :student_id', [
        'exam_id' => $onlineExamId,
        'student_id' => (int) $student['id'],
    ], [
        'online_exam_id' => $onlineExamId,
        'student_id' => (int) $student['id'],
        'answers' => json_encode(['1' => '96', '2' => '25']),
        'score' => 10 - ($index * 2),
        'started_at' => '2026-02-10 09:0' . $index . ':00',
        'submitted_at' => '2026-02-10 09:2' . $index . ':00',
        'created_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Integrations
// ---------------------------------------------------------------------------

put('sms_settings', 'provider', 'rocept', [
    'provider' => 'rocept',
    'api_key' => 'demo-api-key',
    'api_secret' => 'demo-api-secret',
    'sender_id' => 'WSITSCH',
    'api_base_url' => 'https://api.rocept.com/sendsms',
    'is_active' => 1,
    'created_at' => now(),
]);

foreach ([
    ['bKash', 'bkash'],
    ['Nagad', 'nagad'],
    ['Rocket', 'rocket'],
] as $gateway) {
    put('payment_gateways', 'provider_key', $gateway[1], [
        'name' => $gateway[0],
        'provider_key' => $gateway[1],
        'api_key' => null,
        'api_secret' => null,
        'merchant_id' => null,
        'is_active' => 0,
        'created_at' => now(),
    ]);
}

echo 'Records seeded: ', $inserted, " new row(s).\n";

echo "\n";
$report = [
    'fee_heads' => 'fee heads', 'fee_structures' => 'fee structures', 'fee_collections' => 'fee collections',
    'fee_discounts' => 'fee discounts', 'fee_carry_forwards' => 'carry forwards', 'transactions' => 'transactions',
    'bank_reconciliations' => 'reconciliations', 'salary_structures' => 'salary structures',
    'salary_payments' => 'salary payments', 'attendances' => 'student attendance',
    'teacher_attendances' => 'teacher attendance', 'staff_attendances' => 'staff attendance',
    'leave_requests' => 'leave requests', 'inventory_items' => 'inventory items', 'books' => 'books',
    'book_issues' => 'book issues', 'transport_routes' => 'transport routes', 'transport_stops' => 'transport stops',
    'student_transport' => 'student transport', 'hostel_rooms' => 'hostel rooms',
    'hostel_allocations' => 'hostel allocations', 'class_routines' => 'class routines', 'exams' => 'exams',
    'exam_schedules' => 'exam schedules', 'exam_attendances' => 'exam attendance',
    'exam_suggestions' => 'exam suggestions', 'academic_reports' => 'report cards',
    'academic_report_marks' => 'report marks', 'report_configs' => 'result setups',
    'certificates' => 'certificates', 'online_exams' => 'online exams', 'exam_questions' => 'online questions',
    'exam_submissions' => 'online submissions', 'sms_settings' => 'sms providers',
    'payment_gateways' => 'payment gateways',
];

foreach ($report as $table => $label) {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
    printf("  %-22s %4d\n", $label, $count);
}
