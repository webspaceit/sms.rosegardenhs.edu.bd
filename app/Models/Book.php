<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A book in the school library.
 */
final class Book
{
    public static function module(): array
    {
        return [
            'table' => 'books',
            'route' => '/books',
            'title' => 'Library Books',
            'singular' => 'book',
            'permission' => 'library.manage',
            'managePermission' => 'library.manage',
            'columns' => [
                ['name' => 'title', 'label' => 'Title'],
                ['name' => 'author', 'label' => 'Author', 'format' => 'muted'],
                ['name' => 'isbn', 'label' => 'ISBN', 'format' => 'muted'],
                ['name' => 'quantity', 'label' => 'Copies'],
                ['name' => 'available', 'label' => 'Available'],
                ['name' => 'shelf_location', 'label' => 'Shelf', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 255],
                ['name' => 'author', 'label' => 'Author', 'type' => 'text', 'max' => 150],
                ['name' => 'publisher', 'label' => 'Publisher', 'type' => 'text', 'max' => 150],
                ['name' => 'isbn', 'label' => 'ISBN', 'type' => 'text', 'max' => 50],
                ['name' => 'edition', 'label' => 'Edition', 'type' => 'text', 'max' => 50],
                ['name' => 'quantity', 'label' => 'Total copies', 'type' => 'number', 'required' => true, 'default' => 1],
                ['name' => 'available', 'label' => 'Available copies', 'type' => 'number', 'required' => true, 'default' => 1],
                ['name' => 'shelf_location', 'label' => 'Shelf location', 'type' => 'text', 'max' => 100],
            ],
            'search' => ['title', 'author', 'publisher', 'isbn'],
            'order' => 'title ASC',
        ];
    }
}
