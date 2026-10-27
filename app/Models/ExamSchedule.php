<?php
declare(strict_types=1);

namespace App\Models;

/**
 * When a subject is examined.
 */
final class ExamSchedule
{
    public static function module(): array
    {
        return [
            'table' => 'exam_schedules',
            'route' => '/exam-schedules',
            'title' => 'Exam Schedules',
            'singular' => 'schedule',
            'permission' => 'exams.manage',
            'managePermission' => 'exams.manage',
            'columns' => [
                ['name' => 'exam_name', 'label' => 'Exam'],
                ['name' => 'subject_name', 'label' => 'Subject'],
                ['name' => 'class_name', 'label' => 'Class', 'format' => 'muted'],
                ['name' => 'date', 'label' => 'Date'],
                ['name' => 'start_time', 'label' => 'From'],
                ['name' => 'end_time', 'label' => 'To'],
                ['name' => 'hall', 'label' => 'Hall', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'exam_id', 'label' => 'Exam', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'exams']],
                ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'subjects']],
                ['name' => 'class_id', 'label' => 'Class', 'type' => 'select', 'options' => [Catalogue::class, 'classes'], 'hint' => 'Leave blank when the whole class sits the paper.'],
                ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ['name' => 'start_time', 'label' => 'Start time', 'type' => 'text', 'max' => 20],
                ['name' => 'end_time', 'label' => 'End time', 'type' => 'text', 'max' => 20],
                ['name' => 'hall', 'label' => 'Hall', 'type' => 'text', 'max' => 50],
            ],
            'search' => ['e.name', 's.name', 'c.name'],
            'order' => 'es.date ASC, s.name ASC',
            'select' => 'es.*, e.name AS exam_name, s.name AS subject_name, c.name AS class_name',
            'from' => 'exam_schedules es JOIN exams e ON e.id = es.exam_id JOIN subjects s ON s.id = es.subject_id LEFT JOIN classes c ON c.id = es.class_id',
        ];
    }
}
