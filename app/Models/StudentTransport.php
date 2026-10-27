<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A student who uses the school transport.
 */
final class StudentTransport
{
    public static function module(): array
    {
        return [
            'table' => 'student_transport',
            'route' => '/student-transport',
            'title' => 'Student Transport',
            'singular' => 'allocation',
            'permission' => 'transport.manage',
            'managePermission' => 'transport.manage',
            'columns' => [
                ['name' => 'student_name', 'label' => 'Student'],
                ['name' => 'route_name', 'label' => 'Route'],
                ['name' => 'stop_name', 'label' => 'Stop', 'format' => 'muted'],
                ['name' => 'start_date', 'label' => 'From'],
                ['name' => 'is_active', 'label' => 'Active', 'format' => 'bool', 'on' => 'Active', 'off' => 'Inactive'],
            ],
            'fields' => [
                ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'students']],
                ['name' => 'transport_route_id', 'label' => 'Route', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'transportRoutes']],
                ['name' => 'transport_stop_id', 'label' => 'Stop', 'type' => 'select', 'options' => [Catalogue::class, 'transportStops']],
                ['name' => 'start_date', 'label' => 'From', 'type' => 'date'],
                ['name' => 'end_date', 'label' => 'To', 'type' => 'date'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checkboxLabel' => 'This student is currently on the route', 'default' => 1],
            ],
            'search' => ['s.name', 's.student_id', 'r.name'],
            'order' => 's.name ASC',
            'select' => 'st.*, s.name AS student_name, r.name AS route_name, ts.name AS stop_name',
            'from' => 'student_transport st JOIN students s ON s.id = st.student_id JOIN transport_routes r ON r.id = st.transport_route_id LEFT JOIN transport_stops ts ON ts.id = st.transport_stop_id',
        ];
    }
}
