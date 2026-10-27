<?php
declare(strict_types=1);

namespace App\Models;

/**
 * What each class pays for each fee head.
 */
final class FeeStructure
{
    public static function module(): array
    {
        return [
            'table' => 'fee_structures',
            'route' => '/fee-structures',
            'title' => 'Fee Structures',
            'singular' => 'fee structure',
            'permission' => 'fees.manage',
            'managePermission' => 'fees.manage',
            'columns' => [
                ['name' => 'class_name', 'label' => 'Class'],
                ['name' => 'head_name', 'label' => 'Fee head'],
                ['name' => 'frequency', 'label' => 'Frequency', 'format' => 'badge'],
                ['name' => 'amount', 'label' => 'Amount'],
            ],
            'fields' => [
                ['name' => 'class_id', 'label' => 'Class', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'classes']],
                ['name' => 'fee_head_id', 'label' => 'Fee head', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'feeHeads']],
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true, 'default' => 0, 'step' => '0.01'],
            ],
            'search' => ['c.name', 'h.name'],
            'order' => 'c.numeric_level ASC, h.sort_order ASC, h.name ASC',
            'select' => 'fs.*, c.name AS class_name, h.name AS head_name, h.frequency AS frequency',
            'from' => 'fee_structures fs JOIN classes c ON c.id = fs.class_id JOIN fee_heads h ON h.id = fs.fee_head_id',
        ];
    }
}
