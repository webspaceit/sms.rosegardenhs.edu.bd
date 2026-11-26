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
            'from' => 'subjects s LEFT JOIN subject_types st ON st.id = s.type_id AND st.branch_id = s.branch_id',
            'select' => 's.*, st.name AS type_name',
            'columns' => [
                ['name' => 'name', 'label' => 'Name'],
                ['name' => 'code', 'label' => 'Code', 'format' => 'badge'],
                ['name' => 'type_name', 'label' => 'Type', 'format' => 'badge'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 100],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'max' => 20, 'hint' => 'Short subject code, for example BAN or ENG.'],
                ['name' => 'type_id', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'subjectTypes']],
            ],
            'search' => ['s.name', 's.code'],
            'order' => 's.name ASC',
        ];
    }
}
