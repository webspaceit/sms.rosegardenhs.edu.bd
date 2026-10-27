<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Role based permissions.
 *
 * A permission is identified by a slug such as "students.view" and is
 * granted to one or more roles. The matrix that links them is managed from
 * the Permissions screen.
 */
final class Permission
{
    /** Every role a user account may hold. */
    public const ROLES = [
        'superadmin' => 'Super Admin',
        'admin' => 'Admin',
        'teacher' => 'Teacher',
        'accountant' => 'Accountant',
        'receptionist' => 'Receptionist',
        'librarian' => 'Librarian',
        'student' => 'Student',
        'parent' => 'Parent',
    ];

    /**
     * The permission catalogue, grouped by module.
     *
     * @return array<string, array<int, array{name: string, slug: string}>>
     */
    public static function catalogue(): array
    {
        $rows = Database::connection()->query('SELECT id, name, slug, module FROM permissions ORDER BY module, name')->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(string) $row['module']][] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
            ];
        }

        return $grouped;
    }

    /**
     * The permission ids granted to each role.
     *
     * @return array<string, array<int, int>>
     */
    public static function roleMap(): array
    {
        $rows = Database::connection()->query('SELECT role, permission_id FROM role_permissions')->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['role']][] = (int) $row['permission_id'];
        }

        return $map;
    }

    /**
     * The permission slugs granted to a role.
     *
     * @return array<int, string>
     */
    public static function slugsForRole(string $role): array
    {
        $stmt = Database::connection()
            ->prepare('SELECT p.slug FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role = :role');
        $stmt->execute(['role' => $role]);

        $slugs = [];
        foreach ($stmt->fetchAll() as $row) {
            $slugs[] = (string) $row['slug'];
        }

        return $slugs;
    }

    /**
     * Replace the permissions granted to a role.
     *
     * @param array<int, int> $permissionIds
     */
    public static function grantToRole(string $role, array $permissionIds): void
    {
        $db = Database::connection();

        $delete = $db->prepare('DELETE FROM role_permissions WHERE role = :role');
        $delete->execute(['role' => $role]);

        $insert = $db->prepare('INSERT INTO role_permissions (role, permission_id) VALUES (:role, :permission_id)');

        foreach (array_unique($permissionIds) as $permissionId) {
            $insert->execute(['role' => $role, 'permission_id' => (int) $permissionId]);
        }
    }

    /**
     * Create the permission catalogue and the default grants.
     *
     * Existing rows are left alone, so this is safe to run more than once.
     */
    public static function seed(): int
    {
        $permissions = self::defaultCatalogue();

        $db = Database::connection();
        $find = $db->prepare('SELECT id FROM permissions WHERE slug = :slug LIMIT 1');
        $insert = $db->prepare('INSERT INTO permissions (name, slug, module) VALUES (:name, :slug, :module)');

        $ids = [];
        foreach ($permissions as $permission) {
            $find->execute(['slug' => $permission['slug']]);
            $id = $find->fetchColumn();
            if ($id === false) {
                $insert->execute($permission);
                $id = (int) $db->lastInsertId();
            }
            $ids[$permission['slug']] = (int) $id;
        }

        $grants = [
            'superadmin' => array_keys($ids),
            'admin' => array_keys($ids),
            'teacher' => [
                'students.view', 'teachers.view', 'classes.manage', 'subjects.manage',
                'exams.manage', 'reports.view', 'attendance.manage', 'notices.view',
                'notices.create', 'id-cards.view', 'study-materials.manage', 'lesson-plans.manage',
                'exam-suggestions.manage', 'exam-attendance.manage', 'certificates.manage',
                'events.manage', 'holidays.manage', 'complaints.manage',
            ],
            'accountant' => [
                'fees.manage', 'accounts.manage', 'reports.view', 'students.view',
                'teachers.view', 'payroll.manage',
            ],
            'receptionist' => [
                'students.view', 'students.create', 'students.edit',
                'attendance.manage', 'notices.view', 'fees.manage',
            ],
            'librarian' => [
                'library.manage', 'students.view', 'teachers.view',
            ],
            'student' => ['notices.view'],
            'parent' => ['notices.view'],
        ];

        foreach ($grants as $role => $slugs) {
            $permissionIds = [];
            foreach ($slugs as $slug) {
                if (isset($ids[$slug])) {
                    $permissionIds[] = $ids[$slug];
                }
            }
            self::grantToRole($role, $permissionIds);
        }

        return count($permissions);
    }

    /**
     * The permission catalogue and the grants each role starts with.
     *
     * @return array<int, array{name: string, slug: string, module: string}>
     */
    public static function defaultCatalogue(): array
    {
        $definitions = [
            ['View Students', 'students.view', 'Students'],
            ['Create Student', 'students.create', 'Students'],
            ['Edit Student', 'students.edit', 'Students'],
            ['Delete Student', 'students.delete', 'Students'],
            ['View Teachers', 'teachers.view', 'Teachers'],
            ['Create Teacher', 'teachers.create', 'Teachers'],
            ['Edit Teacher', 'teachers.edit', 'Teachers'],
            ['Delete Teacher', 'teachers.delete', 'Teachers'],
            ['Manage Classes', 'classes.manage', 'Academic'],
            ['Manage Subjects', 'subjects.manage', 'Academic'],
            ['Manage Exams', 'exams.manage', 'Exams'],
            ['View Reports', 'reports.view', 'Exams'],
            ['Manage Fees', 'fees.manage', 'Finance'],
            ['Manage Attendance', 'attendance.manage', 'Attendance'],
            ['View Notice Board', 'notices.view', 'Communication'],
            ['Create Notice', 'notices.create', 'Communication'],
            ['Manage Library', 'library.manage', 'Facilities'],
            ['Manage Transport', 'transport.manage', 'Facilities'],
            ['Manage Hostel', 'hostel.manage', 'Facilities'],
            ['Manage Inventory', 'inventory.manage', 'Facilities'],
            ['Manage Accounts', 'accounts.manage', 'Finance'],
            ['Manage Settings', 'settings.manage', 'Settings'],
            ['Manage Permissions', 'permissions.manage', 'Settings'],
            ['View ID Cards', 'id-cards.view', 'Exams'],
            ['Manage Certificates', 'certificates.manage', 'Exams'],
            ['Manage Study Materials', 'study-materials.manage', 'Academic'],
            ['Manage Lesson Plans', 'lesson-plans.manage', 'Academic'],
            ['Manage Holidays', 'holidays.manage', 'Communication'],
            ['Manage Events', 'events.manage', 'Communication'],
            ['Manage Complaints', 'complaints.manage', 'Communication'],
            ['Manage Exam Attendance', 'exam-attendance.manage', 'Exams'],
            ['Manage Staff Attendance', 'staff-attendance.manage', 'Attendance'],
            ['Manage Payroll', 'payroll.manage', 'Finance'],
            ['Manage Exam Suggestions', 'exam-suggestions.manage', 'Exams'],
            ['Manage Guardians', 'guardians.manage', 'Students'],
            ['Manage Student Houses', 'student-houses.manage', 'Students'],
            ['Manage Designations', 'designations.manage', 'Teachers'],
            ['Manage Branches', 'branches.manage', 'Settings'],
        ];

        $permissions = [];
        foreach ($definitions as [$name, $slug, $module]) {
            $permissions[] = ['name' => $name, 'slug' => $slug, 'module' => $module];
        }

        return $permissions;
    }
}
