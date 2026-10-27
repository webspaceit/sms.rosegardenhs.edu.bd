<?php
declare(strict_types=1);

namespace App\Models;

/**
 * The school calendar.
 */
final class Event
{
    public static function module(): array
    {
        return [
            'table' => 'events',
            'route' => '/events',
            'title' => 'Calendar',
            'singular' => 'event',
            'permission' => 'events.manage',
            'managePermission' => 'events.manage',
            'columns' => [
                ['name' => 'title', 'label' => 'Title'],
                ['name' => 'start_date', 'label' => 'Starts'],
                ['name' => 'end_date', 'label' => 'Ends'],
                ['name' => 'description', 'label' => 'Description', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 255],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3],
                ['name' => 'start_date', 'label' => 'Start date', 'type' => 'date', 'required' => true],
                ['name' => 'end_date', 'label' => 'End date', 'type' => 'date'],
                ['name' => 'color', 'label' => 'Colour', 'type' => 'text', 'max' => 20, 'default' => '#00704F', 'hint' => 'Hex colour used for the calendar entry.'],
            ],
            'search' => ['title', 'description'],
            'order' => 'start_date DESC, id DESC',
        ];
    }
}
