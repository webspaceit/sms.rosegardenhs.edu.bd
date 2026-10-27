<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A student placed in a hostel room.
 */
final class HostelAllocation
{
    public static function module(): array
    {
        return [
            'table' => 'hostel_allocations',
            'route' => '/hostel-allocations',
            'title' => 'Hostel Allocations',
            'singular' => 'allocation',
            'permission' => 'hostel.manage',
            'managePermission' => 'hostel.manage',
            'columns' => [
                ['name' => 'student_name', 'label' => 'Student'],
                ['name' => 'room_no', 'label' => 'Room'],
                ['name' => 'start_date', 'label' => 'From'],
                ['name' => 'end_date', 'label' => 'To'],
                ['name' => 'is_active', 'label' => 'Active', 'format' => 'bool', 'on' => 'Active', 'off' => 'Inactive'],
            ],
            'fields' => [
                ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'students']],
                ['name' => 'hostel_room_id', 'label' => 'Room', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'hostelRooms']],
                ['name' => 'start_date', 'label' => 'From', 'type' => 'date'],
                ['name' => 'end_date', 'label' => 'To', 'type' => 'date'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checkboxLabel' => 'This allocation is current', 'default' => 1],
            ],
            'search' => ['s.name', 's.student_id', 'r.room_no'],
            'order' => 's.name ASC',
            'select' => 'ha.*, s.name AS student_name, r.room_no AS room_no',
            'from' => 'hostel_allocations ha JOIN students s ON s.id = ha.student_id JOIN hostel_rooms r ON r.id = ha.hostel_room_id',
        ];
    }
}
