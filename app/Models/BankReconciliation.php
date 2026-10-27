<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A bank statement matched against the school's own books.
 */
final class BankReconciliation
{
    public static function module(): array
    {
        return [
            'table' => 'bank_reconciliations',
            'route' => '/accounts/reconciliations',
            'title' => 'Bank Reconciliations',
            'singular' => 'reconciliation',
            'permission' => 'accounts.manage',
            'managePermission' => 'accounts.manage',
            'columns' => [
                ['name' => 'account_name', 'label' => 'Account'],
                ['name' => 'account_number', 'label' => 'Number', 'format' => 'muted'],
                ['name' => 'statement_date', 'label' => 'Statement date'],
                ['name' => 'ending_balance', 'label' => 'Statement'],
                ['name' => 'cleared_balance', 'label' => 'Cleared'],
                ['name' => 'difference', 'label' => 'Difference'],
                ['name' => 'status', 'label' => 'Status', 'format' => 'badge'],
            ],
            'fields' => [
                ['name' => 'account_name', 'label' => 'Account name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'account_number', 'label' => 'Account number', 'type' => 'text', 'max' => 50],
                ['name' => 'statement_date', 'label' => 'Statement date', 'type' => 'date', 'required' => true],
                ['name' => 'ending_balance', 'label' => 'Ending balance', 'type' => 'number', 'required' => true, 'default' => 0, 'step' => '0.01'],
                ['name' => 'cleared_balance', 'label' => 'Cleared balance', 'type' => 'number', 'required' => true, 'default' => 0, 'step' => '0.01'],
                ['name' => 'difference', 'label' => 'Difference', 'type' => 'number', 'required' => true, 'default' => 0, 'step' => '0.01'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'default' => 'pending', 'options' => [
                    'pending' => 'Pending',
                    'cleared' => 'Cleared',
                    'discrepancy' => 'Discrepancy',
                ]],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'text', 'max' => 255],
            ],
            'search' => ['account_name', 'account_number', 'notes'],
            'order' => 'statement_date DESC, id DESC',
        ];
    }
}
