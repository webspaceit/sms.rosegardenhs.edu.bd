<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Study materials published for classes and subjects.
 */
final class StudyMaterial
{
    public static function module(): array
    {
        return [
            'table' => 'study_materials',
            'route' => '/study-materials',
            'fileField' => 'file_path',
            'title' => 'Study Materials',
            'singular' => 'study material',
            'permission' => 'study-materials.manage',
            'managePermission' => 'study-materials.manage',
            'columns' => [
                ['name' => 'title', 'label' => 'Title'],
                ['name' => 'class_name', 'label' => 'Class', 'format' => 'muted'],
                ['name' => 'subject_name', 'label' => 'Subject', 'format' => 'muted'],
                ['name' => 'teacher_name', 'label' => 'Teacher', 'format' => 'muted'],
                ['name' => 'file_type', 'label' => 'File type', 'format' => 'badge'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 255],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3],
                ['name' => 'file_path', 'label' => 'Upload file', 'type' => 'file', 'directory' => 'study-materials', 'extensions' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'mp4'], 'maxBytes' => 20971520],
                ['name' => 'file_type', 'label' => 'File type', 'type' => 'text', 'max' => 100, 'hint' => 'For example PDF, DOCX, Video.'],
                ['name' => 'class_id', 'label' => 'Class', 'type' => 'select', 'options' => [Catalogue::class, 'classes']],
                ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'options' => [Catalogue::class, 'subjects']],
                ['name' => 'teacher_id', 'label' => 'Teacher', 'type' => 'select', 'options' => [Catalogue::class, 'teachers']],
            ],
            'search' => ['m.title', 'm.description'],
            'order' => 'm.title ASC',
            // The list joins the related names, so those columns are not part
            // of the plain table definition.
            'select' => 'm.*, c.name AS class_name, s.name AS subject_name, t.name AS teacher_name',
            'from' => 'study_materials m LEFT JOIN classes c ON c.id = m.class_id LEFT JOIN subjects s ON s.id = m.subject_id LEFT JOIN teachers t ON t.id = m.teacher_id',
        ];
    }
}
