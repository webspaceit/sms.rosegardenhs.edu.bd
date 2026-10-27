<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A class timetable slot.
 */
final class ClassRoutine
{
    public static function module(): array
    {
        return [
            'table' => 'class_routines',
            'route' => '/class-routines',
            'title' => 'Class Routines',
            'singular' => 'routine',
            'permission' => 'classes.manage',
            'managePermission' => 'classes.manage',
            'columns' => [
                ['name' => 'class_name', 'label' => 'Class'],
                ['name' => 'section_name', 'label' => 'Section', 'format' => 'muted'],
                ['name' => 'day_of_week', 'label' => 'Day'],
                ['name' => 'subject_name', 'label' => 'Subject'],
                ['name' => 'teacher_name', 'label' => 'Teacher', 'format' => 'muted'],
                ['name' => 'start_time', 'label' => 'From'],
                ['name' => 'end_time', 'label' => 'To'],
            ],
            'fields' => [
                ['name' => 'class_id', 'label' => 'Class', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'classes']],
                ['name' => 'section_id', 'label' => 'Section', 'type' => 'select', 'options' => [Catalogue::class, 'sections']],
                ['name' => 'day_of_week', 'label' => 'Day', 'type' => 'select', 'required' => true, 'default' => 'Sunday', 'options' => [
                    'Sunday' => 'Sunday', 'Monday' => 'Monday', 'Tuesday' => 'Tuesday', 'Wednesday' => 'Wednesday',
                    'Thursday' => 'Thursday', 'Friday' => 'Friday', 'Saturday' => 'Saturday',
                ]],
                ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'subjects']],
                ['name' => 'teacher_id', 'label' => 'Teacher', 'type' => 'select', 'options' => [Catalogue::class, 'teachers']],
                ['name' => 'start_time', 'label' => 'Start time', 'type' => 'text', 'required' => true, 'max' => 20, 'hint' => 'For example 08:00.'],
                ['name' => 'end_time', 'label' => 'End time', 'type' => 'text', 'required' => true, 'max' => 20],
                ['name' => 'room', 'label' => 'Room', 'type' => 'text', 'max' => 50],
            ],
            'search' => ['c.name', 's.name', 'sub.name', 't.name'],
            'order' => 'c.numeric_level ASC, FIELD(cr.day_of_week, "Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"), cr.start_time ASC',
            'select' => 'cr.*, c.name AS class_name, s.name AS section_name, sub.name AS subject_name, t.name AS teacher_name',
            'from' => 'class_routines cr JOIN classes c ON c.id = cr.class_id LEFT JOIN sections s ON s.id = cr.section_id JOIN subjects sub ON sub.id = cr.subject_id LEFT JOIN teachers t ON t.id = cr.teacher_id',
        ];
    }
}
