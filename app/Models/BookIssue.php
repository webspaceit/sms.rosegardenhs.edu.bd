<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A book lent to a student or a teacher.
 */
final class BookIssue
{
    public static function module(): array
    {
        return [
            'table' => 'book_issues',
            'route' => '/book-issues',
            'title' => 'Book Issues',
            'singular' => 'issue',
            'permission' => 'library.manage',
            'managePermission' => 'library.manage',
            'columns' => [
                ['name' => 'book_title', 'label' => 'Book'],
                ['name' => 'borrower', 'label' => 'Borrowed by'],
                ['name' => 'issue_date', 'label' => 'Issued'],
                ['name' => 'due_date', 'label' => 'Due'],
                ['name' => 'return_date', 'label' => 'Returned'],
                ['name' => 'status', 'label' => 'Status', 'format' => 'badge'],
            ],
            'fields' => [
                ['name' => 'book_id', 'label' => 'Book', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'books']],
                ['name' => 'student_id', 'label' => 'Student', 'type' => 'select', 'options' => [Catalogue::class, 'students'], 'hint' => 'Leave blank when the book goes to a teacher.'],
                ['name' => 'teacher_id', 'label' => 'Teacher', 'type' => 'select', 'options' => [Catalogue::class, 'teachers']],
                ['name' => 'issue_date', 'label' => 'Issue date', 'type' => 'date', 'required' => true],
                ['name' => 'due_date', 'label' => 'Due date', 'type' => 'date'],
                ['name' => 'return_date', 'label' => 'Return date', 'type' => 'date'],
                ['name' => 'fine', 'label' => 'Fine', 'type' => 'number', 'default' => 0, 'step' => '0.01'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'default' => 'issued', 'options' => [
                    'issued' => 'Issued',
                    'returned' => 'Returned',
                    'overdue' => 'Overdue',
                    'lost' => 'Lost',
                ]],
            ],
            'search' => ['b.title', 's.name', 't.name'],
            'order' => 'bi.issue_date DESC, bi.id DESC',
            'select' => "bi.*, b.title AS book_title, COALESCE(s.name, t.name, '—') AS borrower",
            'from' => 'book_issues bi JOIN books b ON b.id = bi.book_id LEFT JOIN students s ON s.id = bi.student_id LEFT JOIN teachers t ON t.id = bi.teacher_id',
        ];
    }
}
