<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A reduction granted to a student.
 */
final class FeeDiscount
{
    public static function module(): array
    {
        return [
            'table' => 'fee_discounts',
            'route' => '/accounts/discounts',
            'title' => 'Fee Discounts',
            'singular' => 'fee discount',
            'permission' => 'accounts.manage',
            'managePermission' => 'accounts.manage',
            'columns' => [
                ['name' => 'student_name', 'label' => 'Student'],
                ['name' => 'head_name', 'label' => 'Fee head', 'format' => 'muted'],
                ['name' => 'amount', 'label' => 'Amount'],
                ['name' => 'reason', 'label' => 'Reason', 'format' => 'muted'],
                ['name' => 'date', 'label' => 'Date'],
            ],
            'fields' => [
                ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'students']],
                ['name' => 'fee_head_id', 'label' => 'Fee head', 'type' => 'select', 'options' => [Catalogue::class, 'feeHeads']],
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true, 'default' => 0, 'step' => '0.01'],
                ['name' => 'reason', 'label' => 'Reason', 'type' => 'text', 'max' => 255],
                ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
            ],
            'search' => ['s.name', 'd.reason'],
            'order' => 'd.date DESC, d.id DESC',
            'select' => 'd.*, s.name AS student_name, h.name AS head_name',
            'from' => 'fee_discounts d JOIN students s ON s.id = d.student_id LEFT JOIN fee_heads h ON h.id = d.fee_head_id',
        ];
    }
}
