<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Money in and money out that is not a student fee.
 */
final class Transaction
{
    public static function module(): array
    {
        return [
            'table' => 'transactions',
            'route' => '/accounts/transactions',
            'title' => 'Transactions',
            'singular' => 'transaction',
            'permission' => 'accounts.manage',
            'managePermission' => 'accounts.manage',
            'columns' => [
                ['name' => 'date', 'label' => 'Date'],
                ['name' => 'type', 'label' => 'Type', 'format' => 'badge'],
                ['name' => 'category', 'label' => 'Category'],
                ['name' => 'amount', 'label' => 'Amount'],
                ['name' => 'description', 'label' => 'Description', 'format' => 'muted'],
                ['name' => 'payment_method', 'label' => 'Method', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'default' => 'expense', 'options' => [
                    'income' => 'Income',
                    'expense' => 'Expense',
                ]],
                ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'required' => true, 'max' => 100, 'hint' => 'For example Salary, Electricity bill, Donations.'],
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'required' => true, 'default' => 0, 'step' => '0.01'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'text', 'max' => 255],
                ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ['name' => 'payment_method', 'label' => 'Payment method', 'type' => 'text', 'max' => 50],
                ['name' => 'reference', 'label' => 'Reference', 'type' => 'text', 'max' => 100],
            ],
            'search' => ['category', 'description', 'reference'],
            'order' => 'date DESC, id DESC',
        ];
    }
}
