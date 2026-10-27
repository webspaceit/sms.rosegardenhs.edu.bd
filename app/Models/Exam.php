<?php
declare(strict_types=1);

namespace App\Models;

/**
 * An examination.
 */
final class Exam
{
    public static function module(): array
    {
        return [
            'table' => 'exams',
            'route' => '/exams',
            'title' => 'Exams',
            'singular' => 'exam',
            'permission' => 'exams.manage',
            'managePermission' => 'exams.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Name'],
                ['name' => 'type', 'label' => 'Type', 'format' => 'badge'],
                ['name' => 'year_name', 'label' => 'Academic year', 'format' => 'muted'],
                ['name' => 'start_date', 'label' => 'Starts'],
                ['name' => 'end_date', 'label' => 'Ends'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 150, 'hint' => 'For example Half Yearly Examination 2026.'],
                ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'default' => 'other', 'options' => [
                    'unit' => 'Unit test',
                    'mid-term' => 'Mid term',
                    'half-yearly' => 'Half yearly',
                    'annual' => 'Annual',
                    'model-test' => 'Model test',
                    'other' => 'Other',
                ]],
                ['name' => 'academic_year_id', 'label' => 'Academic year', 'type' => 'select', 'options' => [Catalogue::class, 'academicYears']],
                ['name' => 'start_date', 'label' => 'Start date', 'type' => 'date'],
                ['name' => 'end_date', 'label' => 'End date', 'type' => 'date'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['e.name', 'e.description'],
            'order' => 'e.start_date DESC, e.name ASC',
            'select' => 'e.*, y.name AS year_name',
            'from' => 'exams e LEFT JOIN academic_years y ON y.id = e.academic_year_id',
        ];
    }
}
