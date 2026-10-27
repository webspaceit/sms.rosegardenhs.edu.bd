<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Leave requested by a teacher.
 */
final class LeaveRequest
{
    public static function module(): array
    {
        return [
            'table' => 'leave_requests',
            'route' => '/leave-requests',
            'title' => 'Leave Requests',
            'singular' => 'leave request',
            'permission' => 'attendance.manage',
            'managePermission' => 'attendance.manage',
            'columns' => [
                ['name' => 'teacher_name', 'label' => 'Teacher'],
                ['name' => 'type', 'label' => 'Type', 'format' => 'badge'],
                ['name' => 'start_date', 'label' => 'From'],
                ['name' => 'end_date', 'label' => 'To'],
                ['name' => 'status', 'label' => 'Status', 'format' => 'badge'],
                ['name' => 'reason', 'label' => 'Reason', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'teacher_id', 'label' => 'Teacher', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'teachers']],
                ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'default' => 'casual', 'options' => [
                    'sick' => 'Sick',
                    'casual' => 'Casual',
                    'annual' => 'Annual',
                    'maternity' => 'Maternity',
                    'unpaid' => 'Unpaid',
                ]],
                ['name' => 'start_date', 'label' => 'From', 'type' => 'date', 'required' => true],
                ['name' => 'end_date', 'label' => 'To', 'type' => 'date', 'required' => true],
                ['name' => 'reason', 'label' => 'Reason', 'type' => 'text', 'max' => 255],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'default' => 'pending', 'options' => [
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ]],
                ['name' => 'admin_remarks', 'label' => 'Administrator remarks', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['t.name', 'l.reason'],
            'order' => "FIELD(l.status, 'pending', 'approved', 'rejected') ASC, l.start_date DESC",
            'select' => 'l.*, t.name AS teacher_name',
            'from' => 'leave_requests l JOIN teachers t ON t.id = l.teacher_id',
        ];
    }
}
