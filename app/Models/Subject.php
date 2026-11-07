<?php
declare(strict_types=1);

namespace App\Models;

/**
 * The subject register, following the NCTB curriculum.
 */
final class Subject
{
    public static function module(): array
    {
        return [
            'table' => 'subjects',
            'route' => '/subjects',
            'title' => 'Subjects',
            'singular' => 'subject',
            'permission' => 'subjects.manage',
            'managePermission' => 'subjects.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Name'],
                ['name' => 'code', 'label' => 'Code', 'format' => 'badge'],
                ['name' => 'type', 'label' => 'Type', 'format' => 'badge'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 100],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'max' => 20, 'hint' => 'Short subject code, for example BAN or ENG.'],
                ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'default' => 'core', 'options' => [
                    'core' => 'Core',
                    'elective' => 'Elective',
                    'skill' => 'Skill based',
                    'extra' => 'Extra curriculum',
                ]],
            ],
            'search' => ['name', 'code'],
            'order' => 'name ASC',
        ];
    }
}
