<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * The teacher register.
 */
final class Teacher
{
    public static function module(): array
    {
        return [
            'table' => 'teachers',
            'route' => '/teachers',
            'title' => 'Teachers',
            'singular' => 'teacher',
            'createLabel' => 'Add New Teacher',
            'formFullWidth' => true,
            'formColumns' => 3,
            'permission' => 'teachers.view',
            'managePermission' => 'teachers.create',
            'from' => 'teachers t LEFT JOIN designations d ON d.id = t.designation_id AND d.branch_id = t.branch_id'
                . ' LEFT JOIN departments dept ON dept.id = t.department_id AND dept.branch_id = t.branch_id',
            'select' => 't.*, d.name AS designation_name, dept.name AS department_name',
            'columns' => [
                ['name' => '__serial', 'label' => 'Sl.', 'format' => 'serial'],
                ['name' => 'employee_id', 'label' => 'Employee ID', 'format' => 'muted'],
                ['name' => 'name', 'label' => 'Name', 'nowrap' => true],
                ['name' => 'designation_name', 'label' => 'Designation', 'format' => 'badge'],
                ['name' => 'department_name', 'label' => 'Department', 'format' => 'muted'],
                ['name' => 'phone', 'label' => 'Phone', 'format' => 'muted'],
                ['name' => 'email', 'label' => 'Email', 'format' => 'muted'],
                ['name' => 'gender', 'label' => 'Gender'],
                ['name' => 'date_of_birth', 'label' => 'DOB'],
                ['name' => 'blood_group', 'label' => 'Blood group'],
                ['name' => 'qualification', 'label' => 'Qualification', 'format' => 'muted', 'truncate' => true],
                ['name' => 'joining_date', 'label' => 'Joined'],
                ['name' => 'photo', 'label' => 'Photo', 'format' => 'image', 'avatarFallback' => true],
            ],
            'fields' => [
                [
                    'name' => 'employee_id',
                    'label' => 'Employee ID',
                    'type' => 'text',
                    'required' => true,
                    'max' => 30,
                    'unique' => true,
                    'uniqueMessage' => 'That Employee ID is already assigned to another teacher.',
                    'autoGenerate' => static fn (): string => self::nextEmployeeId(),
                    'hint' => 'Generated automatically and kept unique across all campuses.',
                ],
                ['name' => 'name', 'label' => 'Full name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'designation_id', 'label' => 'Designation', 'type' => 'select', 'options' => static fn (): array => Catalogue::designations()],
                ['name' => 'department_id', 'label' => 'Department', 'type' => 'select', 'options' => static fn (): array => Catalogue::departments()],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'max' => 14],
                ['name' => 'email', 'label' => 'Email', 'type' => 'text', 'max' => 190],
                ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other']],
                ['name' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date'],
                ['name' => 'blood_group', 'label' => 'Blood group', 'type' => 'select', 'options' => [
                    'A+' => 'A+',
                    'A-' => 'A-',
                    'B+' => 'B+',
                    'B-' => 'B-',
                    'AB+' => 'AB+',
                    'AB-' => 'AB-',
                    'O+' => 'O+',
                    'O-' => 'O-',
                ]],
                ['name' => 'qualification', 'label' => 'Qualification', 'type' => 'textarea', 'rows' => 2, 'wide' => false],
                ['name' => 'experience_years', 'label' => 'Experience (years)', 'type' => 'number', 'default' => 0],
                ['name' => 'joining_date', 'label' => 'Joining date', 'type' => 'date'],
                ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'rows' => 2, 'wide' => false],
                ['name' => 'photo', 'label' => 'Photo', 'type' => 'file', 'image' => true, 'deletable' => true, 'deleteOldOnReplace' => true, 'directory' => 'teacher-photos', 'extensions' => ['jpg', 'jpeg', 'png', 'webp'], 'maxBytes' => 5242880, 'hint' => 'JPG, PNG, or WebP · maximum 5 MB. The original image size is kept.'],
            ],
            'search' => ['t.name', 't.employee_id', 'd.name', 'dept.name'],
            'order' => 't.id DESC',
        ];
    }

    private static function nextEmployeeId(): string
    {
        $statement = Database::read()->query(
            "SELECT employee_id FROM teachers WHERE employee_id REGEXP '^TCH-[0-9]+$'"
        );
        $highest = 0;
        foreach ($statement->fetchAll() as $row) {
            $number = (int) substr((string) $row['employee_id'], 4);
            $highest = max($highest, $number);
        }

        return 'TCH-' . str_pad((string) ($highest + 1), 3, '0', STR_PAD_LEFT);
    }
}
