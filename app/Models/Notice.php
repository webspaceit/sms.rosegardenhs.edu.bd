<?php
declare(strict_types=1);

namespace App\Models;

/**
 * The notice board.
 */
final class Notice
{
    public static function module(): array
    {
        return [
            'table' => 'notices',
            'route' => '/notices',
            'title' => 'Notice Board',
            'singular' => 'notice',
            'permission' => 'notices.view',
            'managePermission' => 'notices.create',
            'columns' => [
                ['name' => 'title', 'label' => 'Title'],
                ['name' => 'type', 'label' => 'Type', 'format' => 'badge'],
                ['name' => 'published_date', 'label' => 'Published'],
                ['name' => 'expires_date', 'label' => 'Expires'],
                ['name' => 'is_published', 'label' => 'Status', 'format' => 'bool', 'on' => 'Published', 'off' => 'Draft'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 255],
                ['name' => 'content', 'label' => 'Content', 'type' => 'textarea', 'required' => true, 'rows' => 6],
                ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'default' => 'general', 'options' => [
                    'general' => 'General',
                    'academic' => 'Academic',
                    'event' => 'Event',
                    'exam' => 'Examination',
                    'urgent' => 'Urgent',
                ]],
                ['name' => 'target_roles', 'label' => 'Audience', 'type' => 'text', 'max' => 255, 'hint' => 'Comma separated roles, for example "teacher,parent". Leave blank for everyone.'],
                ['name' => 'published_date', 'label' => 'Published date', 'type' => 'date', 'required' => true],
                ['name' => 'expires_date', 'label' => 'Expires on', 'type' => 'date'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox', 'checkboxLabel' => 'Show this notice on the board', 'default' => 1],
            ],
            'search' => ['title', 'content'],
            'order' => 'published_date DESC, id DESC',
            'insertDefaults' => static function (): array {
                return ['created_by' => (int) (\App\Core\Auth::user()['id'] ?? 0)];
            },
        ];
    }
}
