<?php
declare(strict_types=1);

namespace App\Models;

/**
 * An exam a student sits online.
 */
final class OnlineExam
{
    public static function module(): array
    {
        return [
            'table' => 'online_exams',
            'route' => '/online-exams',
            'title' => 'Online Examinations',
            'singular' => 'online exam',
            'permission' => 'exams.manage',
            'managePermission' => 'exams.manage',
            'columns' => [
                ['name' => 'title', 'label' => 'Title'],
                ['name' => 'class_name', 'label' => 'Class', 'format' => 'muted'],
                ['name' => 'subject_name', 'label' => 'Subject', 'format' => 'muted'],
                ['name' => 'duration_minutes', 'label' => 'Minutes'],
                ['name' => 'total_marks', 'label' => 'Marks'],
                ['name' => 'status', 'label' => 'Status', 'format' => 'badge'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 255],
                ['name' => 'class_id', 'label' => 'Class', 'type' => 'select', 'options' => [Catalogue::class, 'classes']],
                ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'options' => [Catalogue::class, 'subjects']],
                ['name' => 'duration_minutes', 'label' => 'Duration (minutes)', 'type' => 'number', 'required' => true, 'default' => 30],
                ['name' => 'total_marks', 'label' => 'Total marks', 'type' => 'number', 'required' => true, 'default' => 20],
                ['name' => 'start_time', 'label' => 'Opens at', 'type' => 'text', 'max' => 20, 'hint' => 'For example 2026-10-22 09:00.'],
                ['name' => 'end_time', 'label' => 'Closes at', 'type' => 'text', 'max' => 20],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'default' => 'draft', 'options' => [
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'completed' => 'Completed',
                ]],
                ['name' => 'instructions', 'label' => 'Instructions', 'type' => 'textarea', 'rows' => 4],
            ],
            'search' => ['oe.title', 'oe.instructions'],
            'order' => 'oe.id DESC',
            'select' => 'oe.*, c.name AS class_name, s.name AS subject_name',
            'from' => 'online_exams oe LEFT JOIN classes c ON c.id = oe.class_id LEFT JOIN subjects s ON s.id = oe.subject_id',
        ];
    }
}
