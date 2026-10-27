<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A stop along a transport route.
 */
final class TransportStop
{
    public static function module(): array
    {
        return [
            'table' => 'transport_stops',
            'route' => '/transport-stops',
            'title' => 'Transport Stops',
            'singular' => 'stop',
            'permission' => 'transport.manage',
            'managePermission' => 'transport.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Stop'],
                ['name' => 'route_name', 'label' => 'Route'],
                ['name' => 'pickup_time', 'label' => 'Pickup'],
                ['name' => 'drop_time', 'label' => 'Drop'],
                ['name' => 'sort_order', 'label' => 'Order', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'transport_route_id', 'label' => 'Route', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'transportRoutes']],
                ['name' => 'name', 'label' => 'Stop name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'pickup_time', 'label' => 'Pickup time', 'type' => 'text', 'max' => 20, 'hint' => 'For example 07:15.'],
                ['name' => 'drop_time', 'label' => 'Drop time', 'type' => 'text', 'max' => 20],
                ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'default' => 0],
            ],
            'search' => ['s.name', 'r.name'],
            'order' => 'r.name ASC, s.sort_order ASC, s.name ASC',
            'select' => 's.*, r.name AS route_name',
            'from' => 'transport_stops s JOIN transport_routes r ON r.id = s.transport_route_id',
        ];
    }
}
