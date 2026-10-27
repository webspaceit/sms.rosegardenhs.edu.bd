<?php
declare(strict_types=1);

namespace App\Models;

/**
 * The school holiday list.
 */
final class Holiday
{
    public static function module(): array
    {
        return [
            'table' => 'holidays',
            'route' => '/holidays',
            'title' => 'Holidays',
            'singular' => 'holiday',
            'permission' => 'holidays.manage',
            'managePermission' => 'holidays.manage',
            'columns' => [
                ['name' => 'title', 'label' => 'Title'],
                ['name' => 'date', 'label' => 'Date'],
                ['name' => 'description', 'label' => 'Description', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 255],
                ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3],
            ],
            'search' => ['title', 'description'],
            'order' => 'date DESC, id DESC',
        ];
    }
}
