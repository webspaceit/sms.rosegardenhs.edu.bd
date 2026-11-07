<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Student houses, the house system used for sports and competitions.
 */
final class StudentHouse
{
    public static function module(): array
    {
        return [
            'table' => 'student_houses',
            'route' => '/student-houses',
            'title' => 'Student Houses',
            'singular' => 'house',
            'permission' => 'student-houses.manage',
            'managePermission' => 'student-houses.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Name'],
                ['name' => 'description', 'label' => 'Description', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 100],
                ['name' => 'color', 'label' => 'Colour', 'type' => 'text', 'max' => 20, 'default' => '#0ea5a4'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['name'],
            'order' => 'name ASC',
        ];
    }
}
