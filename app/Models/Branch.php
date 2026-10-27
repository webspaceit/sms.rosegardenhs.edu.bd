<?php
declare(strict_types=1);

namespace App\Models;

/**
 * School branches, for schools running more than one campus.
 */
final class Branch
{
    public static function module(): array
    {
        return [
            'table' => 'branches',
            'route' => '/branches',
            'title' => 'Branches',
            'singular' => 'branch',
            'permission' => 'branches.manage',
            'managePermission' => 'branches.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Name'],
                ['name' => 'address', 'label' => 'Address', 'format' => 'muted'],
                ['name' => 'phone', 'label' => 'Phone', 'format' => 'muted'],
                ['name' => 'email', 'label' => 'Email', 'format' => 'muted'],
                ['name' => 'is_main', 'label' => 'Main', 'format' => 'bool', 'on' => 'Main', 'off' => 'Branch'],
                ['name' => 'status', 'label' => 'Status', 'format' => 'badge'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'max' => 255],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'max' => 20],
                ['name' => 'email', 'label' => 'Email', 'type' => 'text', 'max' => 190],
                ['name' => 'is_main', 'label' => 'Main campus', 'type' => 'checkbox', 'checkboxLabel' => 'This is the main campus', 'default' => 0],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'default' => 'active', 'options' => [
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ]],
            ],
            'search' => ['name', 'address', 'phone'],
            'order' => 'is_main DESC, name ASC',
        ];
    }
}
