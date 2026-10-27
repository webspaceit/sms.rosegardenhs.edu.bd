<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Daily student attendance.
 */
final class Attendance
{
    public static function module(): array
    {
        return [
            'table' => 'attendances',
            'route' => '/attendance',
            'title' => 'Student Attendance',
            'singular' => 'attendance record',
            'permission' => 'attendance.manage',
            'managePermission' => 'attendance.manage',
            'columns' => [
                ['name' => 'date', 'label' => 'Date'],
                ['name' => 'student_name', 'label' => 'Student'],
                ['name' => 'class_name', 'label' => 'Class'],
                ['name' => 'status', 'label' => 'Status', 'format' => 'badge'],
                ['name' => 'remarks', 'label' => 'Remarks', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'students']],
                ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'default' => 'present', 'options' => [
                    'present' => 'Present',
                    'absent' => 'Absent',
                    'late' => 'Late',
                    'half_day' => 'Half day',
                    'leave' => 'Leave',
                ]],
                ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['s.name', 's.student_id', 'a.remarks'],
            'order' => 'a.date DESC, s.roll_no ASC',
            'select' => 'a.*, s.name AS student_name, c.name AS class_name',
            'from' => 'attendances a JOIN students s ON s.id = a.student_id JOIN classes c ON c.id = s.class_id',
        ];
    }
}
