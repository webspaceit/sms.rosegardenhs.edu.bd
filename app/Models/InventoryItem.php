<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A stock item the school keeps.
 */
final class InventoryItem
{
    public static function module(): array
    {
        return [
            'table' => 'inventory_items',
            'route' => '/inventory',
            'title' => 'Inventory',
            'singular' => 'item',
            'permission' => 'inventory.manage',
            'managePermission' => 'inventory.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Item'],
                ['name' => 'category_name', 'label' => 'Category', 'format' => 'muted'],
                ['name' => 'quantity', 'label' => 'In stock'],
                ['name' => 'minimum_quantity', 'label' => 'Minimum'],
                ['name' => 'unit_price', 'label' => 'Unit price'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Item name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => [Catalogue::class, 'inventoryCategories']],
                ['name' => 'quantity', 'label' => 'Quantity in stock', 'type' => 'number', 'required' => true, 'default' => 0],
                ['name' => 'minimum_quantity', 'label' => 'Minimum quantity', 'type' => 'number', 'default' => 0, 'hint' => 'The list warns when stock falls to this level.'],
                ['name' => 'unit_price', 'label' => 'Unit price', 'type' => 'number', 'default' => 0, 'step' => '0.01'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['i.name', 'i.description', 'c.name'],
            'order' => 'c.name ASC, i.name ASC',
            'select' => 'i.*, c.name AS category_name',
            'from' => 'inventory_items i LEFT JOIN inventory_categories c ON c.id = i.category_id',
        ];
    }
}
