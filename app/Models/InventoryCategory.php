<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A group of inventory items.
 */
final class InventoryCategory
{
    public static function module(): array
    {
        return [
            'table' => 'inventory_categories',
            'route' => '/inventory/categories',
            'title' => 'Inventory Categories',
            'singular' => 'category',
            'permission' => 'inventory.manage',
            'managePermission' => 'inventory.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Name'],
                ['name' => 'description', 'label' => 'Description', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 100],
                ['name' => 'description', 'label' => 'Description', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['name', 'description'],
            'order' => 'name ASC',
        ];
    }
}
