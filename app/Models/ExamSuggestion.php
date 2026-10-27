<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A teacher's notes and advice about an exam paper.
 */
final class ExamSuggestion
{
    public static function module(): array
    {
        return [
            'table' => 'exam_suggestions',
            'route' => '/exam-suggestions',
            'fileField' => 'file_path',
            'title' => 'Exam Suggestions',
            'singular' => 'suggestion',
            'permission' => 'exam-suggestions.manage',
            'managePermission' => 'exam-suggestions.manage',
            'columns' => [
                ['name' => 'title', 'label' => 'Title'],
                ['name' => 'exam_name', 'label' => 'Exam'],
                ['name' => 'subject_name', 'label' => 'Subject', 'format' => 'muted'],
                ['name' => 'teacher_name', 'label' => 'Teacher', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 255],
                ['name' => 'exam_id', 'label' => 'Exam', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'exams']],
                ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'options' => [Catalogue::class, 'subjects']],
                ['name' => 'teacher_id', 'label' => 'Teacher', 'type' => 'select', 'options' => [Catalogue::class, 'teachers']],
                ['name' => 'suggestion', 'label' => 'Suggestion', 'type' => 'textarea', 'rows' => 6],
                ['name' => 'file_path', 'label' => 'Upload attachment', 'type' => 'file', 'directory' => 'exam-attachments', 'extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], 'maxBytes' => 10485760],
            ],
            'search' => ['es.title', 'es.suggestion', 'e.name'],
            'order' => 'es.id DESC',
            'select' => 'es.*, e.name AS exam_name, s.name AS subject_name, t.name AS teacher_name',
            'from' => 'exam_suggestions es JOIN exams e ON e.id = es.exam_id LEFT JOIN subjects s ON s.id = es.subject_id LEFT JOIN teachers t ON t.id = es.teacher_id',
        ];
    }
}
