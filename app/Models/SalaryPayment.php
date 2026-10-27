<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * A salary paid to a teacher for a month.
 */
final class SalaryPayment
{
    public static function module(): array
    {
        return [
            'table' => 'salary_payments',
            'route' => '/salary-payments',
            'title' => 'Salary Payments',
            'singular' => 'salary payment',
            'permission' => 'payroll.manage',
            'managePermission' => 'payroll.manage',
            'columns' => [
                ['name' => 'teacher_name', 'label' => 'Teacher'],
                ['name' => 'month', 'label' => 'Month'],
                ['name' => 'year', 'label' => 'Year'],
                ['name' => 'gross_salary', 'label' => 'Gross'],
                ['name' => 'deductions', 'label' => 'Deductions'],
                ['name' => 'net_pay', 'label' => 'Net pay'],
                ['name' => 'paid_date', 'label' => 'Paid on'],
            ],
            'fields' => [
                ['name' => 'teacher_id', 'label' => 'Teacher', 'type' => 'select', 'required' => true, 'options' => [Catalogue::class, 'teachers']],
                ['name' => 'paid_date', 'label' => 'Paid on', 'type' => 'date', 'required' => true],
                ['name' => 'payment_method', 'label' => 'Payment method', 'type' => 'text', 'max' => 50],
                ['name' => 'reference_no', 'label' => 'Reference number', 'type' => 'text', 'max' => 50],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['t.name', 'p.month', 'p.year', 'p.reference_no'],
            'order' => 'p.paid_date DESC, p.id DESC',
            'select' => 'p.*, t.name AS teacher_name',
            'from' => 'salary_payments p JOIN teachers t ON t.id = p.teacher_id',
            'beforeSave' => static function (array $data): array {
                $statement = Database::connection()->prepare('SELECT basic_salary, house_rent, medical, transport_allowance, other_allowances, deductions FROM salary_structures WHERE teacher_id = :teacher_id AND effective_from <= :paid_date ORDER BY effective_from DESC LIMIT 1');
                $statement->execute(['teacher_id' => (int) $data['teacher_id'], 'paid_date' => $data['paid_date']]);
                $structure = $statement->fetch();
                if (!$structure) throw new \RuntimeException('Add an effective salary structure for this teacher before creating payroll.');
                $gross = (float) $structure['basic_salary'] + (float) $structure['house_rent'] + (float) $structure['medical'] + (float) $structure['transport_allowance'] + (float) $structure['other_allowances'];
                $data['month'] = date('F', strtotime((string) $data['paid_date']));
                $data['year'] = date('Y', strtotime((string) $data['paid_date']));
                $data['gross_salary'] = round($gross, 2);
                $data['deductions'] = round((float) $structure['deductions'], 2);
                $data['net_pay'] = round(max(0, $gross - (float) $structure['deductions']), 2);
                return $data;
            },
        ];
    }
}
