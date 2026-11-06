<?php
declare(strict_types=1);

namespace App\Models;

/**
 * The sections a class is divided into.
 */
final class Section
{
    public static function module(): array
    {
        return [
            'table' => 'sections',
            'route' => '/sections',
            'title' => 'Sections',
            'singular' => 'section',
            'permission' => 'classes.manage',
            'managePermission' => 'classes.manage',
            'from' => 'sections s LEFT JOIN classes c ON c.id = s.class_id AND c.branch_id = s.branch_id',
            'select' => 's.*, c.name AS class_name',
            'columns' => [
                ['name' => 'class_name', 'label' => 'Class'],
                ['name' => 'name', 'label' => 'Section'],
                ['name' => 'room_no', 'label' => 'Room', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'class_id', 'label' => 'Class', 'type' => 'select', 'required' => true, 'options' => static fn (): array => Catalogue::classes()],
                ['name' => 'name', 'label' => 'Section name', 'type' => 'text', 'required' => true, 'max' => 20],
                ['name' => 'room_no', 'label' => 'Room number', 'type' => 'text', 'max' => 30],
            ],
            'search' => ['s.name'],
            'order' => 'c.numeric_level ASC, s.name ASC',
        ];
    }
}
