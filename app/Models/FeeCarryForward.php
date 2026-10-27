<?php
declare(strict_types=1);

namespace App\Models;

/**
 * An unpaid fee carried into the next academic year.
 */
final class FeeCarryForward
{
    public static function module(): array
    {
        return [
            'table' => 'fee_carry_forwards',
            'route' => '/accounts/carry-forwards',
            'title' => 'Fee Carry Forwards',
            'singular' => 'carry forward',
            'permission' => 'accounts.manage',
            'managePermission' => 'accounts.manage',
            'columns' => [
                ['name' => 'student_name', 'label' => 'Student'],
                ['name' => 'year_name', 'label' => 'Academic year', 'format' => 'muted'],
                ['name' => 'amount', 'label' => 'Amount'],
                ['name' => 'reason', 'label' => 'Reason', 'format' => 'muted'],
                ['name' => 'date', 'label' => 'Date'],
            ],
            'fields' => [
                ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'students']],
                ['name' => 'academic_year_id', 'label' => 'Academic year', 'type' => 'select', 'options' => [Catalogue::class, 'academicYears']],
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true, 'default' => 0, 'step' => '0.01'],
                ['name' => 'reason', 'label' => 'Reason', 'type' => 'text', 'max' => 255],
                ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
            ],
            'search' => ['s.name', 'c.reason'],
            'order' => 'c.date DESC, c.id DESC',
            'select' => 'c.*, s.name AS student_name, y.name AS year_name',
            'from' => 'fee_carry_forwards c JOIN students s ON s.id = c.student_id LEFT JOIN academic_years y ON y.id = c.academic_year_id',
        ];
    }
}
