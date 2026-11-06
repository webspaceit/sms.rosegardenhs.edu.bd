<?php
declare(strict_types=1);

namespace App\Models;

/**
 * The academic departments staff belong to.
 */
final class Department
{
    public static function module(): array
    {
        return [
            'table' => 'departments',
            'route' => '/departments',
            'title' => 'Departments',
            'singular' => 'department',
            'permission' => 'classes.manage',
            'managePermission' => 'classes.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Department'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Department name', 'type' => 'text', 'required' => true, 'max' => 100],
            ],
            'search' => ['name'],
            'order' => 'name ASC',
        ];
    }
}
