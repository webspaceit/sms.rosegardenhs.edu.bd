<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Complaints raised by students, staff or parents.
 */
final class Complaint
{
    public static function module(): array
    {
        return [
            'table' => 'complaints',
            'route' => '/complaints',
            'title' => 'Complaints',
            'singular' => 'complaint',
            'permission' => 'complaints.manage',
            'managePermission' => 'complaints.manage',
            'columns' => [
                ['name' => 'complainant_name', 'label' => 'Complainant'],
                ['name' => 'complainant_phone', 'label' => 'Phone', 'format' => 'muted'],
                ['name' => 'type', 'label' => 'Type', 'format' => 'badge'],
                ['name' => 'status', 'label' => 'Status', 'format' => 'badge'],
                ['name' => 'description', 'label' => 'Description', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'complainant_name', 'label' => 'Complainant name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'complainant_phone', 'label' => 'Phone', 'type' => 'text', 'max' => 20],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true, 'rows' => 5],
                ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'default' => 'other', 'options' => [
                    'student' => 'Student',
                    'staff' => 'Staff',
                    'other' => 'Other',
                ]],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'default' => 'pending', 'options' => [
                    'pending' => 'Pending',
                    'investigating' => 'Investigating',
                    'resolved' => 'Resolved',
                    'dismissed' => 'Dismissed',
                ]],
                ['name' => 'resolution_notes', 'label' => 'Resolution notes', 'type' => 'textarea', 'rows' => 3],
            ],
            'search' => ['complainant_name', 'description'],
            'order' => "FIELD(status, 'pending', 'investigating', 'resolved', 'dismissed') ASC, id DESC",
        ];
    }
}
