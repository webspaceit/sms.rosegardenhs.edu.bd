<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A certificate issued to a student.
 */
final class Certificate
{
    public static function module(): array
    {
        return [
            'table' => 'certificates',
            'route' => '/certificates',
            'title' => 'Certificates',
            'singular' => 'certificate',
            'permission' => 'certificates.manage',
            'managePermission' => 'certificates.manage',
            'columns' => [
                ['name' => 'certificate_no', 'label' => 'Number', 'format' => 'muted'],
                ['name' => 'student_name', 'label' => 'Student'],
                ['name' => 'type', 'label' => 'Type', 'format' => 'badge'],
                ['name' => 'issue_date', 'label' => 'Issued'],
                ['name' => 'remarks', 'label' => 'Remarks', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'students']],
                ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'default' => 'Merit', 'options' => [
                    'Merit' => 'Merit',
                    'Participation' => 'Participation',
                    'Academic' => 'Academic',
                    'Sports' => 'Sports',
                    'Character' => 'Character',
                    'Transfer' => 'Transfer',
                ]],
                ['name' => 'certificate_no', 'label' => 'Certificate number', 'type' => 'text', 'required' => true, 'max' => 50],
                ['name' => 'issue_date', 'label' => 'Issue date', 'type' => 'date', 'required' => true],
                ['name' => 'content', 'label' => 'Content', 'type' => 'textarea', 'rows' => 4],
                ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['c.certificate_no', 's.name', 'c.type'],
            'order' => 'c.issue_date DESC, c.id DESC',
            'select' => 'c.*, s.name AS student_name',
            'from' => 'certificates c JOIN students s ON s.id = c.student_id',
        ];
    }
}
