<?php
declare(strict_types=1);

namespace App\Models;

/**
 * The list of staff designations.
 */
final class Designation
{
    public static function module(): array
    {
        return [
            'table' => 'designations',
            'route' => '/designations',
            'title' => 'Designations',
            'singular' => 'designation',
            'permission' => 'designations.manage',
            'managePermission' => 'designations.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Title'],
                ['name' => 'description', 'label' => 'Description', 'format' => 'muted'],
                ['name' => 'is_active', 'label' => 'Status', 'format' => 'bool', 'on' => 'Active', 'off' => 'Inactive'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 100, 'hint' => 'For example Head Teacher, Assistant Teacher, Office Assistant.'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'text', 'max' => 255],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checkboxLabel' => 'Available for new staff records', 'default' => 1],
            ],
            'search' => ['name', 'description'],
            'order' => 'name ASC',
        ];
    }
}
