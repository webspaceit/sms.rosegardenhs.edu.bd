<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Which teacher teaches which subject in which class.
 */
final class TeacherSubject
{
    public static function module(): array
    {
        return [
            'table' => 'teacher_subject',
            'route' => '/teacher-subjects',
            'title' => 'Teacher Subjects',
            'singular' => 'assignment',
            'permission' => 'classes.manage',
            'managePermission' => 'classes.manage',
            'columns' => [
                ['name' => 'teacher_name', 'label' => 'Teacher'],
                ['name' => 'class_name', 'label' => 'Class'],
                ['name' => 'subject_name', 'label' => 'Subject'],
                ['name' => 'is_class_teacher', 'label' => 'Class teacher', 'format' => 'bool', 'on' => 'Yes', 'off' => 'No'],
            ],
            'fields' => [
                ['name' => 'teacher_id', 'label' => 'Teacher', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'teachers']],
                ['name' => 'class_id', 'label' => 'Class', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'classes']],
                ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'subjects']],
            ],
            'search' => ['t.name', 'c.name', 's.name'],
            'order' => 'c.numeric_level ASC, t.name ASC',
            'select' => 'ts.*, t.name AS teacher_name, c.name AS class_name, s.name AS subject_name, CASE WHEN c.class_teacher_id = ts.teacher_id THEN 1 ELSE 0 END AS is_class_teacher',
            'from' => 'teacher_subject ts JOIN teachers t ON t.id = ts.teacher_id JOIN classes c ON c.id = ts.class_id JOIN subjects s ON s.id = ts.subject_id',
        ];
    }
}
