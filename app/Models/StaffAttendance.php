<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Daily staff attendance, for everyone who is not a teacher.
 */
final class StaffAttendance
{
    public static function module(): array
    {
        return [
            'table' => 'staff_attendances',
            'route' => '/staff-attendances',
            'title' => 'Staff Attendance',
            'singular' => 'attendance record',
            'permission' => 'staff-attendance.manage',
            'managePermission' => 'staff-attendance.manage',
            'columns' => [
                ['name' => 'date', 'label' => 'Date'],
                ['name' => 'staff_name', 'label' => 'Staff'],
                ['name' => 'status', 'label' => 'Status', 'format' => 'badge'],
                ['name' => 'remarks', 'label' => 'Remarks', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'user_id', 'label' => 'Staff member', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'users']],
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
            'search' => ['u.name', 'sa.remarks'],
            'order' => 'sa.date DESC, u.name ASC',
            'select' => 'sa.*, u.name AS staff_name',
            'from' => 'staff_attendances sa JOIN users u ON u.id = sa.user_id',
        ];
    }
}
