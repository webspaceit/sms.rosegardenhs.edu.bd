<?php
declare(strict_types=1);

namespace App\Models;

/**
 * What a teacher earns, broken into allowances.
 */
final class SalaryStructure
{
    public static function module(): array
    {
        return [
            'table' => 'salary_structures',
            'route' => '/salary-structures',
            'title' => 'Salary Structures',
            'singular' => 'salary structure',
            'permission' => 'payroll.manage',
            'managePermission' => 'payroll.manage',
            'columns' => [
                ['name' => 'teacher_name', 'label' => 'Teacher'],
                ['name' => 'basic_salary', 'label' => 'Basic'],
                ['name' => 'house_rent', 'label' => 'House rent'],
                ['name' => 'medical', 'label' => 'Medical'],
                ['name' => 'transport_allowance', 'label' => 'Transport'],
                ['name' => 'other_allowances', 'label' => 'Other'],
                ['name' => 'deductions', 'label' => 'Deductions'],
                ['name' => 'effective_from', 'label' => 'Effective from'],
            ],
            'fields' => [
                ['name' => 'teacher_id', 'label' => 'Teacher', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'teachers']],
                ['name' => 'basic_salary', 'label' => 'Basic salary', 'type' => 'number', 'required' => true, 'default' => 0, 'step' => '0.01'],
                ['name' => 'house_rent', 'label' => 'House rent', 'type' => 'number', 'default' => 0, 'step' => '0.01'],
                ['name' => 'medical', 'label' => 'Medical allowance', 'type' => 'number', 'default' => 0, 'step' => '0.01'],
                ['name' => 'transport_allowance', 'label' => 'Transport allowance', 'type' => 'number', 'default' => 0, 'step' => '0.01'],
                ['name' => 'other_allowances', 'label' => 'Other allowances', 'type' => 'number', 'default' => 0, 'step' => '0.01'],
                ['name' => 'deductions', 'label' => 'Deductions', 'type' => 'number', 'default' => 0, 'step' => '0.01'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'text', 'max' => 255],
                ['name' => 'effective_from', 'label' => 'Effective from', 'type' => 'date', 'required' => true],
            ],
            'search' => ['t.name', 's.notes'],
            'order' => 's.effective_from DESC, t.name ASC',
            'select' => 's.*, t.name AS teacher_name',
            'from' => 'salary_structures s JOIN teachers t ON t.id = s.teacher_id',
        ];
    }
}
