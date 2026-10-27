<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Core\View;
use App\Core\BranchContext;

/**
 * The account dashboard: what has come in and what has gone out.
 */
final class AccountController
{
    public function dashboard(Request $request): void
    {
        Guard::requirePermission('accounts.manage');

        $db = Database::connection();

        $branch = BranchContext::id();
        $feesCollected = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM fee_collections WHERE branch_id=$branch AND status = 'paid'")->fetchColumn();
        $feesDue = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM fee_collections WHERE branch_id=$branch AND status IN ('due', 'partial')")->fetchColumn();
        $feeInvoices = (int) $db->query("SELECT COUNT(*) FROM fee_collections WHERE branch_id=$branch")->fetchColumn();

        $income = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE branch_id=$branch AND type = 'income'")->fetchColumn();
        $expense = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE branch_id=$branch AND type = 'expense'")->fetchColumn();
        $salaryPaid = (float) $db->query("SELECT COALESCE(SUM(net_pay), 0) FROM salary_payments WHERE branch_id=$branch")->fetchColumn();
        $discounts = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM fee_discounts WHERE branch_id=$branch")->fetchColumn();

        $byHead = $db->query(
            'SELECT h.name, COUNT(fc.id) AS invoices, COALESCE(SUM(fc.amount), 0) AS total,'
            . ' COALESCE(SUM(CASE WHEN fc.status = "paid" THEN fc.amount ELSE 0 END), 0) AS paid'
            . ' FROM fee_heads h LEFT JOIN fee_collections fc ON fc.fee_head_id = h.id AND fc.branch_id=h.branch_id'
            . ' WHERE h.branch_id=' . $branch
            . ' GROUP BY h.id, h.name ORDER BY h.sort_order, h.name'
        )->fetchAll();

        $recent = $db->query('SELECT * FROM transactions WHERE branch_id=' . $branch . ' ORDER BY date DESC, id DESC LIMIT 8')->fetchAll();

        $byMonth = $db->query(
            "SELECT DATE_FORMAT(date, '%Y-%m') AS month,"
            . " COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income,"
            . ' COALESCE(SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END), 0) AS expense'
            . ' FROM transactions WHERE branch_id=' . $branch . ' GROUP BY DATE_FORMAT(date, "%Y-%m") ORDER BY month DESC LIMIT 6'
        )->fetchAll();

        View::render('accounts/dashboard', [
            'title' => 'Account dashboard',
            'user' => Auth::user(),
            'feesCollected' => $feesCollected,
            'feesDue' => $feesDue,
            'feeInvoices' => $feeInvoices,
            'income' => $income,
            'expense' => $expense,
            'salaryPaid' => $salaryPaid,
            'discounts' => $discounts,
            'balance' => $feesCollected + $income - $expense - $salaryPaid,
            'byHead' => $byHead,
            'recent' => $recent,
            'byMonth' => $byMonth,
        ]);
    }
}
