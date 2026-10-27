<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Money collected from a student.
 */
final class FeeCollection
{
    public static function module(): array
    {
        return [
            'table' => 'fee_collections',
            'route' => '/fees',
            'title' => 'Fee Collections',
            'singular' => 'fee collection',
            'permission' => 'fees.manage',
            'managePermission' => 'fees.manage',
            'columns' => [
                ['name' => 'invoice_no', 'label' => 'Invoice', 'format' => 'muted'],
                ['name' => 'student_name', 'label' => 'Student'],
                ['name' => 'head_name', 'label' => 'Fee head'],
                ['name' => 'amount', 'label' => 'Amount'],
                ['name' => 'status', 'label' => 'Status', 'format' => 'badge'],
                ['name' => 'paid_on', 'label' => 'Paid on'],
            ],
            'fields' => [
                ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'students']],
                ['name' => 'fee_head_id', 'label' => 'Fee head', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'feeHeads']],
                ['name' => 'academic_year_id', 'label' => 'Academic year', 'type' => 'select', 'options' => [Catalogue::class, 'academicYears']],
                ['name' => 'invoice_no', 'label' => 'Invoice number', 'type' => 'text', 'required' => true, 'max' => 50],
                ['name' => 'month', 'label' => 'Month', 'type' => 'text', 'max' => 20, 'hint' => 'For example January or 2026-01.'],
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true, 'default' => 0, 'step' => '0.01'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'default' => 'due', 'options' => [
                    'paid' => 'Paid',
                    'due' => 'Due',
                    'partial' => 'Partial',
                ]],
                ['name' => 'due_date', 'label' => 'Due date', 'type' => 'date'],
                ['name' => 'paid_on', 'label' => 'Paid on', 'type' => 'date'],
                ['name' => 'payment_method', 'label' => 'Payment method', 'type' => 'text', 'max' => 50],
                ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['fc.invoice_no', 's.name', 's.student_id', 'h.name'],
            'order' => 'fc.id DESC',
            'select' => 'fc.*, s.name AS student_name, h.name AS head_name',
            'from' => 'fee_collections fc JOIN students s ON s.id = fc.student_id JOIN fee_heads h ON h.id = fc.fee_head_id',
            'headerAction' => ['url' => '/fees/generate', 'label' => 'Generate invoices'],
        ];
    }
}
