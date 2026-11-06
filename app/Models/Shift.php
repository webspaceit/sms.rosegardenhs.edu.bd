<?php
declare(strict_types=1);

namespace App\Models;

/**
 * The shifts a class may run in.
 */
final class Shift
{
    public static function module(): array
    {
        return [
            'table' => 'shifts',
            'route' => '/shifts',
            'title' => 'Shifts',
            'singular' => 'shift',
            'permission' => 'classes.manage',
            'managePermission' => 'classes.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Shift'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Shift name', 'type' => 'text', 'required' => true, 'max' => 50],
            ],
            'search' => ['name'],
            'order' => 'name ASC',
        ];
    }
}
