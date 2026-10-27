<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A school bus route.
 */
final class TransportRoute
{
    public static function module(): array
    {
        return [
            'table' => 'transport_routes',
            'route' => '/transport-routes',
            'title' => 'Transport Routes',
            'singular' => 'route',
            'permission' => 'transport.manage',
            'managePermission' => 'transport.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Route'],
                ['name' => 'driver_name', 'label' => 'Driver'],
                ['name' => 'driver_phone', 'label' => 'Phone', 'format' => 'muted'],
                ['name' => 'vehicle_no', 'label' => 'Vehicle', 'format' => 'muted'],
                ['name' => 'fare', 'label' => 'Fare'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Route name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'driver_name', 'label' => 'Driver name', 'type' => 'text', 'max' => 150],
                ['name' => 'driver_phone', 'label' => 'Driver phone', 'type' => 'text', 'max' => 20],
                ['name' => 'vehicle_no', 'label' => 'Vehicle number', 'type' => 'text', 'max' => 30],
                ['name' => 'fare', 'label' => 'Monthly fare', 'type' => 'number', 'default' => 0, 'step' => '0.01'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['name', 'driver_name', 'vehicle_no'],
            'order' => 'name ASC',
        ];
    }
}
