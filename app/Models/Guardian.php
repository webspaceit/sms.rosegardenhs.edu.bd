<?php
declare(strict_types=1);

namespace App\Models;

/**
 * The guardian and parent register.
 */
final class Guardian
{
    public static function module(): array
    {
        return [
            'table' => 'guardians',
            'route' => '/guardians',
            'title' => 'Guardians',
            'singular' => 'guardian',
            'permission' => 'guardians.manage',
            'managePermission' => 'guardians.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Name'],
                ['name' => 'relation', 'label' => 'Relation', 'format' => 'badge'],
                ['name' => 'phone', 'label' => 'Phone'],
                ['name' => 'occupation', 'label' => 'Occupation', 'format' => 'muted'],
                ['name' => 'district', 'label' => 'District', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Full name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'name_bn', 'label' => 'Name in Bangla', 'type' => 'text', 'max' => 150],
                ['name' => 'relation', 'label' => 'Relation', 'type' => 'select', 'required' => true, 'default' => 'Father', 'options' => [
                    'Father' => 'Father',
                    'Mother' => 'Mother',
                    'Guardian' => 'Guardian',
                    'Grandparent' => 'Grandparent',
                    'Sibling' => 'Sibling',
                    'Other' => 'Other',
                ]],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'required' => true, 'max' => 20],
                ['name' => 'email', 'label' => 'Email', 'type' => 'text', 'max' => 190],
                ['name' => 'occupation', 'label' => 'Occupation', 'type' => 'text', 'max' => 100],
                ['name' => 'nid', 'label' => 'National ID', 'type' => 'text', 'max' => 50],
                ['name' => 'district', 'label' => 'District', 'type' => 'text', 'max' => 100],
                ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'rows' => 2],
            ],
            'search' => ['name', 'name_bn', 'phone', 'occupation'],
            'order' => 'name ASC',
        ];
    }
}
