<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Lesson plans prepared by teachers.
 */
final class LessonPlan
{
    public static function module(): array
    {
        return [
            'table' => 'lesson_plans',
            'route' => '/lesson-plans',
            'title' => 'Lesson Plans',
            'singular' => 'lesson plan',
            'permission' => 'lesson-plans.manage',
            'managePermission' => 'lesson-plans.manage',
            'columns' => [
                ['name' => 'topic', 'label' => 'Topic'],
                ['name' => 'class_name', 'label' => 'Class', 'format' => 'muted'],
                ['name' => 'subject_name', 'label' => 'Subject', 'format' => 'muted'],
                ['name' => 'teacher_name', 'label' => 'Teacher', 'format' => 'muted'],
                ['name' => 'lesson_date', 'label' => 'Date'],
            ],
            'fields' => [
                ['name' => 'topic', 'label' => 'Topic', 'type' => 'text', 'required' => true, 'max' => 255],
                ['name' => 'topic_overview', 'label' => 'Topic overview', 'type' => 'textarea', 'rows' => 4],
                ['name' => 'lesson_breakdown', 'label' => 'Lesson breakdown', 'type' => 'textarea', 'rows' => 6, 'hint' => 'Describe the steps of the lesson.'],
                ['name' => 'class_id', 'label' => 'Class', 'type' => 'select', 'options' => [Catalogue::class, 'classes']],
                ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'options' => [Catalogue::class, 'subjects']],
                ['name' => 'teacher_id', 'label' => 'Teacher', 'type' => 'select', 'options' => [Catalogue::class, 'teachers']],
                ['name' => 'lesson_date', 'label' => 'Lesson date', 'type' => 'date'],
            ],
            'search' => ['l.topic', 'l.topic_overview'],
            'order' => 'l.lesson_date DESC, l.id DESC',
            'select' => 'l.*, c.name AS class_name, s.name AS subject_name, t.name AS teacher_name',
            'from' => 'lesson_plans l LEFT JOIN classes c ON c.id = l.class_id LEFT JOIN subjects s ON s.id = l.subject_id LEFT JOIN teachers t ON t.id = l.teacher_id',
        ];
    }
}
