<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Daily teacher attendance.
 */
final class TeacherAttendance
{
    public static function module(): array
    {
        return [
            'table' => 'teacher_attendances',
            'route' => '/teacher-attendances',
            'title' => 'Teacher Attendance',
            'singular' => 'attendance record',
            'permission' => 'attendance.manage',
            'managePermission' => 'attendance.manage',
            'columns' => [
                ['name' => 'date', 'label' => 'Date'],
                ['name' => 'teacher_name', 'label' => 'Teacher'],
                ['name' => 'status', 'label' => 'Status', 'format' => 'badge'],
                ['name' => 'remarks', 'label' => 'Remarks', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'teacher_id', 'label' => 'Teacher', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'teachers']],
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
            'search' => ['t.name', 'ta.remarks'],
            'order' => 'ta.date DESC, t.name ASC',
            'select' => 'ta.*, t.name AS teacher_name',
            'from' => 'teacher_attendances ta JOIN teachers t ON t.id = ta.teacher_id',
        ];
    }
}
