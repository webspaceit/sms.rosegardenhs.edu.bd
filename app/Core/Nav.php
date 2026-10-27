<?php
declare(strict_types=1);

namespace App\Core;

/**
 * The application navigation tree.
 *
 * This mirrors the grouped navigation of the full Laravel build so both
 * applications expose the same information architecture: eleven top level
 * sections, each holding the modules that belong to it.
 *
 * Items carry one of two states:
 *   - `href` is a string  -> the module is implemented and the row is a link.
 *   - `href` is null      -> the module is planned; the row renders as a
 *                            disabled "Soon" placeholder instead of a link.
 *
 * Keeping the whole tree in one place means a new module is added by editing
 * this file only, and the sidebar can never drift from the roadmap.
 */
final class Nav
{
    /**
     * The full navigation tree.
     *
     * @return array<int, array{label: string, icon: string, collapsible: bool, items: array<int, array<string, mixed>>}>
     */
    public static function groups(): array
    {
        static $groups = null;

        if ($groups !== null) {
            return $groups;
        }

        $groups = [
            [
                'label' => 'Overview',
                'icon' => 'dashboard',
                'collapsible' => false,
                'items' => [
                    self::item('Dashboard', '/dashboard', 'dashboard'),
                    self::item('Public Website', '/', 'globe'),
                ],
            ],
            [
                'label' => 'Student Information',
                'icon' => 'cap',
                'collapsible' => true,
                'items' => [
                    self::item('Student Details', '/students', 'users'),
                    self::item('Student Admission', '/students/new', 'upload', ['/students/new', '/students/edit']),
                    self::item('Import Students', '/students/import', 'upload'),
                    self::item('Promote Students', '/students/promote', 'upload'),
                    self::item('Student Houses', '/student-houses', 'home'),
                    self::item('Guardians', '/guardians', 'user'),
                ],
            ],
            [
                'label' => 'Academic',
                'icon' => 'book',
                'collapsible' => true,
                'items' => [
                    self::item('Academic Setup', '/academics', 'building'),
                    self::item('Teachers', '/teachers', 'users', ['/teachers', '/designations'], [
                        self::item('All Teachers', '/teachers', 'users', ['/teachers']),
                        self::item('Designations', '/designations', 'award'),
                    ]),
                    self::item('Subjects', '/subjects', 'book'),
                    self::item('Study Materials', '/study-materials', 'file'),
                    self::item('Lesson Plans', '/lesson-plans', 'scroll'),
                    self::item('Weekly Timetable', '/timetable', 'calendar'),
                ],
            ],
            [
                'label' => 'Exams & Results',
                'icon' => 'table',
                'collapsible' => true,
                'items' => [
                    self::item('Exams', '/exams', 'clipboard'),
                    self::item('Exam Schedules', '/exam-schedules', 'clock'),
                    self::item('Reports', '/reports', 'table'),
                    self::item('Fail Lists', '/fail-lists', 'alert'),
                    self::item('Section Results', '/section-results', 'list'),
                    self::item('Tabulation Sheets', '/tabulation-sheets', 'table'),
                    self::item('Merit Lists', '/merit-lists', 'trophy'),
                    self::item('Admit Cards', '/admit-cards', 'card'),
                    self::item('Exam Suggestions', '/exam-suggestions', 'file'),
                    self::item('Exam Attendance', '/exam-attendances', 'clipboard'),
                    self::item('ID Cards', '/id-cards', 'card'),
                    self::item('Mark Entry', '/marks', 'table'),
                    self::item('Seating Chart', '/seating-chart', 'layout'),
                    self::item('Certificates', '/certificates', 'award'),
                    self::item('Online Examinations', '/online-exams', 'globe'),
                ],
            ],
            [
                'label' => 'Attendance',
                'icon' => 'calendarCheck',
                'collapsible' => true,
                'items' => [
                    self::item('Student Attendance', '/attendance', 'calendarCheck'),
                    self::item('Attendance Register', '/attendance-register', 'clipboard'),
                    self::item('Batch Attendance', '/batch-attendance', 'timer'),
                    self::item('Teacher Attendance', '/teacher-attendances', 'shield'),
                    self::item('Leave Requests', '/leave-requests', 'scroll'),
                    self::item('Staff Attendance', '/staff-attendances', 'clipboard'),
                ],
            ],
            [
                'label' => 'Finance',
                'icon' => 'banknote',
                'collapsible' => true,
                'items' => [
                    self::item('Fee Collections', '/fees', 'banknote'),
                    self::item('Fee Heads', '/fee-heads', 'receipt'),
                    self::item('Fee Structures', '/fee-structures', 'receipt'),
                    self::item('Salary Structures', '/salary-structures', 'home'),
                    self::item('Salary Payments', '/salary-payments', 'banknote'),
                ],
            ],
            [
                'label' => 'Accounts',
                'icon' => 'dollar',
                'collapsible' => true,
                'items' => [
                    self::item('Account Dashboard', '/accounts', 'dollar'),
                    self::item('Transactions', '/accounts/transactions', 'receipt'),
                    self::item('Fee Discounts', '/accounts/discounts', 'percent'),
                    self::item('Fee Carry Forwards', '/accounts/carry-forwards', 'banknote'),
                    self::item('Bank Reconciliations', '/accounts/reconciliations', 'banknote'),
                ],
            ],
            [
                'label' => 'Teacher Assignments',
                'icon' => 'userCheck',
                'collapsible' => true,
                'items' => [
                    self::item('Allocation Matrix', '/allocation-matrix', 'layout'),
                    self::item('Class Teachers', '/class-teachers', 'userCheck'),
                    self::item('Teacher Subjects', '/teacher-subjects', 'book'),
                    self::item('Class Routines', '/class-routines', 'clock'),
                ],
            ],
            [
                'label' => 'Facilities',
                'icon' => 'library',
                'collapsible' => true,
                'items' => [
                    self::item('Library Books', '/books', 'library'),
                    self::item('Book Issues', '/book-issues', 'bookMarked'),
                    self::item('Transport Routes', '/transport-routes', 'bus'),
                    self::item('Transport Stops', '/transport-stops', 'pin'),
                    self::item('Student Transport', '/student-transport', 'truck'),
                    self::item('Hostel Rooms', '/hostel-rooms', 'bed'),
                    self::item('Hostel Allocations', '/hostel-allocations', 'home'),
                    self::item('Inventory', '/inventory', 'box'),
                ],
            ],
            [
                'label' => 'Communication',
                'icon' => 'bell',
                'collapsible' => true,
                'items' => [
                    self::item('Notice Board', '/notices', 'bell'),
                    self::item('Holidays', '/holidays', 'calendar'),
                    self::item('Calendar', '/events', 'clock'),
                    self::item('Complaints', '/complaints', 'alert'),
                ],
            ],
            [
                'label' => 'Settings',
                'icon' => 'gear',
                'collapsible' => true,
                'items' => [
                    self::item('School Settings', '/settings/school', 'gear'),
                    self::item('Permissions & Roles', '/permissions', 'shield'),
                    self::item('SMS Integration', '/sms-settings', 'message'),
                    self::item('Payment Gateways', '/payment-gateways', 'card'),
                    self::item('User Accounts', '/users', 'userCheck'),
                    self::item('Audit Log', '/audit-logs', 'scroll'),
                    self::item('Branches', '/branches', 'building'),
                ],
            ],
        ];

        return $groups;
    }

    /** A module that is implemented and reachable. */
    private static function item(string $title, string $href, string $icon, array $match = [], array $children = []): array
    {
        return [
            'title' => $title,
            'href' => $href,
            'icon' => $icon,
            'match' => $match !== [] ? $match : [$href],
            'children' => $children,
        ];
    }

    /** A module that is planned but not built yet. */
    private static function soon(string $title, string $icon, array $children = []): array
    {
        return [
            'title' => $title,
            'href' => null,
            'icon' => $icon,
            'match' => [],
            'children' => $children,
        ];
    }

    /**
     * The permission a module needs before it appears in the navigation.
     *
     * The key is the longest route prefix that applies to a module. The
     * navigation is filtered by these so a librarian never sees the fee
     * screens in the menu, even though the server would refuse them anyway.
     *
     * @var array<string, string>
     */
    private const PERMISSIONS = [
        '/students' => 'students.view',
        '/audit-logs' => 'settings.manage',
        '/academics' => 'classes.manage',
        '/teachers' => 'teachers.view',
        '/designations' => 'designations.manage',
        '/subjects' => 'subjects.manage',
        '/study-materials' => 'study-materials.manage',
        '/lesson-plans' => 'lesson-plans.manage',
        '/notices' => 'notices.view',
        '/holidays' => 'holidays.manage',
        '/events' => 'events.manage',
        '/complaints' => 'complaints.manage',
        '/exams' => 'exams.manage',
        '/exam-schedules' => 'exams.manage',
        '/reports' => 'reports.view',
        '/fail-lists' => 'reports.view',
        '/section-results' => 'reports.view',
        '/tabulation-sheets' => 'reports.view',
        '/merit-lists' => 'reports.view',
        '/admit-cards' => 'reports.view',
        '/exam-suggestions' => 'exam-suggestions.manage',
        '/exam-attendances' => 'exam-attendance.manage',
        '/id-cards' => 'id-cards.view',
        '/marks' => 'reports.view',
        '/seating-chart' => 'exams.manage',
        '/certificates' => 'certificates.manage',
        '/online-exams' => 'exams.manage',
        '/attendance' => 'attendance.manage',
        '/attendance-register' => 'attendance.manage',
        '/batch-attendance' => 'attendance.manage',
        '/teacher-attendances' => 'attendance.manage',
        '/leave-requests' => 'attendance.manage',
        '/staff-attendances' => 'staff-attendance.manage',
        '/fee-heads' => 'fees.manage',
        '/fee-structures' => 'fees.manage',
        '/fees' => 'fees.manage',
        '/salary-structures' => 'payroll.manage',
        '/salary-payments' => 'payroll.manage',
        '/accounts' => 'accounts.manage',
        '/allocation-matrix' => 'classes.manage',
        '/class-teachers' => 'classes.manage',
        '/teacher-subjects' => 'classes.manage',
        '/class-routines' => 'classes.manage',
        '/timetable' => 'classes.manage',
        '/books' => 'library.manage',
        '/book-issues' => 'library.manage',
        '/transport-routes' => 'transport.manage',
        '/transport-stops' => 'transport.manage',
        '/student-transport' => 'transport.manage',
        '/hostel-rooms' => 'hostel.manage',
        '/hostel-allocations' => 'hostel.manage',
        '/inventory' => 'inventory.manage',
        '/settings/school' => 'settings.manage',
        '/permissions' => 'permissions.manage',
        '/sms-settings' => 'settings.manage',
        '/payment-gateways' => 'settings.manage',
        '/branches' => 'branches.manage',
        '/users' => 'settings.manage',
    ];

    /**
     * The navigation the signed in user is allowed to see.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function visibleGroups(): array
    {
        $user = Auth::user();
        if ($user === null) {
            return [];
        }

        $role = (string) ($user['role'] ?? '');
        if ($role === 'superadmin' || $role === 'admin') {
            return self::groups();
        }

        $visible = [];
        foreach (self::groups() as $group) {
            $items = [];
            foreach ($group['items'] as $item) {
                if (self::allowed($item['href'] ?? null)) {
                    $items[] = $item;
                }
            }
            if ($items !== []) {
                $group['items'] = $items;
                $visible[] = $group;
            }
        }

        return $visible;
    }

    /** Whether the signed in role may see the module behind a route. */
    private static function allowed(?string $href): bool
    {
        if ($href === null) {
            return false;
        }

        $slug = null;
        $longest = -1;
        foreach (self::PERMISSIONS as $prefix => $permission) {
            if (($href === $prefix || str_starts_with($href, $prefix . '/')) && strlen($prefix) > $longest) {
                $longest = strlen($prefix);
                $slug = $permission;
            }
        }

        if ($slug === null) {
            // A route with no permission of its own, such as the dashboard.
            return true;
        }

        return Guard::can($slug);
    }

    /** The request path the navigation should highlight, base path removed. */
    public static function currentPath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = Url::basePath();

        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        return '/' . trim($path, '/');
    }

    /** Whether the given item (or any of its children) matches the current path. */
    public static function isActive(array $item, string $path): bool
    {
        if (in_array($path, $item['match'] ?? [], true)) {
            return true;
        }

        foreach ($item['children'] ?? [] as $child) {
            if (self::isActive($child, $path)) {
                return true;
            }
        }

        return false;
    }

    /** Whether any item inside the group matches the current path. */
    public static function groupIsActive(array $group, string $path): bool
    {
        foreach ($group['items'] as $item) {
            if (self::isActive($item, $path)) {
                return true;
            }
        }

        return false;
    }

    /** How many modules in the group are still awaiting implementation. */
    public static function pendingCount(array $group): int
    {
        $count = 0;

        foreach ($group['items'] as $item) {
            if ($item['href'] === null) {
                $count++;
            }
            $count += self::pendingCount(['items' => $item['children'] ?? []]);
        }

        return $count;
    }
}
