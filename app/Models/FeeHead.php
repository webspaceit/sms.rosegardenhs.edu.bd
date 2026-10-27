<?php
declare(strict_types=1);

namespace App\Models;

/**
 * The kinds of fee a school charges.
 */
final class FeeHead
{
    public static function module(): array
    {
        return [
            'table' => 'fee_heads',
            'route' => '/fee-heads',
            'title' => 'Fee Heads',
            'singular' => 'fee head',
            'permission' => 'fees.manage',
            'managePermission' => 'fees.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Name'],
                ['name' => 'name_bn', 'label' => 'Name in Bangla', 'format' => 'muted'],
                ['name' => 'frequency', 'label' => 'Frequency', 'format' => 'badge'],
                ['name' => 'is_optional', 'label' => 'Optional', 'format' => 'bool', 'on' => 'Optional', 'off' => 'Required'],
                ['name' => 'sort_order', 'label' => 'Order', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'name_bn', 'label' => 'Name in Bangla', 'type' => 'text', 'max' => 150],
                ['name' => 'frequency', 'label' => 'Frequency', 'type' => 'select', 'required' => true, 'default' => 'monthly', 'options' => [
                    'monthly' => 'Monthly',
                    'yearly' => 'Yearly',
                    'one-time' => 'One time',
                ]],
                ['name' => 'is_optional', 'label' => 'Optional', 'type' => 'checkbox', 'checkboxLabel' => 'Students may opt out of this fee', 'default' => 0],
                ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'default' => 0],
            ],
            'search' => ['name', 'name_bn'],
            'order' => 'sort_order ASC, name ASC',
        ];
    }
}
