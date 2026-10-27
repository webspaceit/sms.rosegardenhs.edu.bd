<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Whether a student sat a particular exam paper.
 */
final class ExamAttendance
{
    public static function module(): array
    {
        return [
            'table' => 'exam_attendances',
            'route' => '/exam-attendances',
            'title' => 'Exam Attendance',
            'singular' => 'exam attendance',
            'permission' => 'exam-attendance.manage',
            'managePermission' => 'exam-attendance.manage',
            'columns' => [
                ['name' => 'exam_name', 'label' => 'Exam'],
                ['name' => 'student_name', 'label' => 'Student'],
                ['name' => 'subject_name', 'label' => 'Subject'],
                ['name' => 'present', 'label' => 'Present', 'format' => 'bool', 'on' => 'Present', 'off' => 'Absent'],
                ['name' => 'remarks', 'label' => 'Remarks', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'exam_id', 'label' => 'Exam', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'exams']],
                ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'students']],
                ['name' => 'subject_id', 'label' => 'Subject', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'subjects']],
                ['name' => 'present', 'label' => 'Present', 'type' => 'checkbox', 'checkboxLabel' => 'The student sat this paper', 'default' => 1],
                ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['e.name', 's.name', 'sub.name'],
            'order' => 'e.start_date DESC, s.roll_no ASC',
            'select' => 'ea.*, e.name AS exam_name, s.name AS student_name, sub.name AS subject_name',
            'from' => 'exam_attendances ea JOIN exams e ON e.id = ea.exam_id JOIN students s ON s.id = ea.student_id JOIN subjects sub ON sub.id = ea.subject_id',
        ];
    }
}
