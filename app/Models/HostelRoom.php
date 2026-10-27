<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A room in the school hostel.
 */
final class HostelRoom
{
    public static function module(): array
    {
        return [
            'table' => 'hostel_rooms',
            'route' => '/hostel-rooms',
            'title' => 'Hostel Rooms',
            'singular' => 'room',
            'permission' => 'hostel.manage',
            'managePermission' => 'hostel.manage',
            'columns' => [
                ['name' => 'room_no', 'label' => 'Room'],
                ['name' => 'type', 'label' => 'Type', 'format' => 'badge'],
                ['name' => 'capacity', 'label' => 'Capacity'],
                ['name' => 'rent_per_bed', 'label' => 'Rent per bed'],
                ['name' => 'facilities', 'label' => 'Facilities', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'room_no', 'label' => 'Room number', 'type' => 'text', 'required' => true, 'max' => 30],
                ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'default' => 'double', 'options' => [
                    'single' => 'Single',
                    'double' => 'Double',
                    'triple' => 'Triple',
                    'dormitory' => 'Dormitory',
                ]],
                ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number', 'required' => true, 'default' => 2],
                ['name' => 'rent_per_bed', 'label' => 'Rent per bed', 'type' => 'number', 'default' => 0, 'step' => '0.01'],
                ['name' => 'facilities', 'label' => 'Facilities', 'type' => 'text', 'max' => 255, 'hint' => 'For example Attach bathroom, Balcony.'],
            ],
            'search' => ['room_no', 'facilities'],
            'order' => 'room_no ASC',
        ];
    }
}
