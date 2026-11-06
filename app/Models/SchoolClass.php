<?php
declare(strict_types=1);

namespace App\Models;

/**
 * The list of classes a student may be placed in.
 */
final class SchoolClass
{
    public static function module(): array
    {
        return [
            'table' => 'classes',
            'route' => '/classes',
            'title' => 'Classes',
            'singular' => 'class',
            'permission' => 'classes.manage',
            'managePermission' => 'classes.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Class'],
                ['name' => 'numeric_level', 'label' => 'Level'],
                ['name' => 'shift', 'label' => 'Shift', 'format' => 'badge'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Class name', 'type' => 'text', 'required' => true, 'max' => 50],
                ['name' => 'numeric_level', 'label' => 'Numeric level', 'type' => 'number', 'required' => true],
                ['name' => 'shift', 'label' => 'Shift', 'type' => 'select', 'default' => 'Day', 'options' => ['Morning' => 'Morning', 'Day' => 'Day', 'Both' => 'Both']],
            ],
            'search' => ['name'],
            'order' => 'numeric_level ASC',
        ];
    }
}
