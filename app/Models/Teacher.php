<?php
declare(strict_types=1);

namespace App\Models;

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
            'permission' => 'teachers.view',
            'managePermission' => 'teachers.create',
            'from' => 'teachers t LEFT JOIN designations d ON d.id = t.designation_id AND d.branch_id = t.branch_id',
            'select' => 't.*, d.name AS designation_name',
            'columns' => [
                ['name' => 'employee_id', 'label' => 'Employee ID', 'format' => 'muted'],
                ['name' => 'name', 'label' => 'Name'],
                ['name' => 'designation_name', 'label' => 'Designation', 'format' => 'badge'],
                ['name' => 'department', 'label' => 'Department', 'format' => 'muted'],
                ['name' => 'phone', 'label' => 'Phone', 'format' => 'muted'],
                ['name' => 'joining_date', 'label' => 'Joined'],
            ],
            'fields' => [
                ['name' => 'employee_id', 'label' => 'Employee ID', 'type' => 'text', 'required' => true, 'max' => 30],
                ['name' => 'name', 'label' => 'Full name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'name_bn', 'label' => 'Name in Bangla', 'type' => 'text', 'max' => 150],
                ['name' => 'designation_id', 'label' => 'Designation', 'type' => 'select', 'options' => static fn (): array => Catalogue::designations()],
                ['name' => 'department', 'label' => 'Department', 'type' => 'text', 'max' => 100, 'hint' => 'For example Bangla, English, Mathematics, ICT.'],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'max' => 20],
                ['name' => 'email', 'label' => 'Email', 'type' => 'text', 'max' => 190],
                ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other']],
                ['name' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date'],
                ['name' => 'blood_group', 'label' => 'Blood group', 'type' => 'text', 'max' => 5],
                ['name' => 'qualification', 'label' => 'Qualification', 'type' => 'textarea', 'rows' => 2],
                ['name' => 'experience_years', 'label' => 'Experience (years)', 'type' => 'number', 'default' => 0],
                ['name' => 'joining_date', 'label' => 'Joining date', 'type' => 'date'],
                ['name' => 'district', 'label' => 'District', 'type' => 'text', 'max' => 100],
                ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'rows' => 2],
            ],
            'search' => ['t.name', 't.name_bn', 't.employee_id', 'd.name', 't.department'],
            'order' => 't.name ASC',
        ];
    }
}
