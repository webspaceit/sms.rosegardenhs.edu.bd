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
                ['name' => 'target_roles', 'label' => 'Audience', 'type' => 'multiselect', 'virtual' => true, 'options' => Permission::ROLES, 'hint' => 'Leave blank to show the notice to everyone.'],
                ['name' => 'published_date', 'label' => 'Published date', 'type' => 'date', 'required' => true],
                ['name' => 'expires_date', 'label' => 'Expires on', 'type' => 'date'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox', 'checkboxLabel' => 'Show this notice on the board', 'default' => 1],
            ],
            'search' => ['title', 'content'],
            'order' => 'published_date DESC, id DESC',
            'recordForEdit' => static function (array $record): array {
                $rows = \App\Core\Database::connection()->query(
                    'SELECT role FROM notice_roles WHERE notice_id = ' . (int) $record['id'] . ' ORDER BY role'
                )->fetchAll();
                $record['target_roles'] = implode(',', array_column($rows, 'role'));
                return $record;
            },
            'afterSave' => static function (int $id, array $data, \App\Core\Request $request): void {
                $db = \App\Core\Database::connection();
                $db->prepare('DELETE FROM notice_roles WHERE notice_id = :id')->execute(['id' => $id]);
                $roles = $request->input('target_roles', []);
                $roles = is_array($roles) ? array_values($roles) : [];
                $insert = $db->prepare('INSERT INTO notice_roles (notice_id, role) VALUES (:notice_id, :role)');
                foreach ($roles as $role) {
                    $insert->execute(['notice_id' => $id, 'role' => (string) $role]);
                }
            },
            'insertDefaults' => static function (): array {
                return ['created_by' => (int) (\App\Core\Auth::user()['id'] ?? 0)];
            },
        ];
    }
}
