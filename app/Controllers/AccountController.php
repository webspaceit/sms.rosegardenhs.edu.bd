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
        $feesCollected = (float) $db->query("SELECT COALESCE((SELECT SUM(amount) FROM student_payments WHERE branch_id=$branch),0) + COALESCE((SELECT SUM(fc.amount) FROM fee_collections fc WHERE fc.branch_id=$branch AND fc.status='paid' AND NOT EXISTS (SELECT 1 FROM student_payment_allocations a JOIN student_payments p ON p.id=a.payment_id WHERE a.fee_collection_id=fc.id AND p.branch_id=$branch)),0)")->fetchColumn();
        $feesDue = (float) $db->query("SELECT COALESCE(SUM(GREATEST(fc.amount - COALESCE(pa.allocated, 0) - COALESCE(fd.discounted, 0), 0)), 0) FROM fee_collections fc LEFT JOIN (SELECT a.fee_collection_id, SUM(a.amount) AS allocated FROM student_payment_allocations a JOIN student_payments p ON p.id=a.payment_id WHERE p.branch_id=$branch GROUP BY a.fee_collection_id) pa ON pa.fee_collection_id=fc.id LEFT JOIN (SELECT fee_collection_id,SUM(amount) AS discounted FROM fee_invoice_discounts WHERE branch_id=$branch AND approved_by IS NOT NULL GROUP BY fee_collection_id) fd ON fd.fee_collection_id=fc.id WHERE fc.branch_id=$branch AND fc.status <> 'paid'")->fetchColumn();
        $feeInvoices = (int) $db->query("SELECT COUNT(*) FROM fee_collections WHERE branch_id=$branch")->fetchColumn();

        $income = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE branch_id=$branch AND type = 'income'")->fetchColumn();
        $expense = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE branch_id=$branch AND type = 'expense'")->fetchColumn();
        $salaryPaid = (float) $db->query("SELECT COALESCE(SUM(net_pay), 0) FROM salary_payments WHERE branch_id=$branch")->fetchColumn();
        $discounts = (float) $db->query("SELECT COALESCE((SELECT SUM(amount) FROM fee_discounts WHERE branch_id=$branch),0) + COALESCE((SELECT SUM(amount) FROM fee_invoice_discounts WHERE branch_id=$branch AND approved_by IS NOT NULL),0)")->fetchColumn();

        $byHead = $db->query(
            'SELECT h.name, COUNT(fc.id) AS invoices, COALESCE(SUM(fc.amount), 0) AS total,'
            . ' COALESCE(SUM(CASE WHEN fc.status = "paid" THEN fc.amount ELSE COALESCE(pa.allocated,0) END), 0) AS paid'
            . ' FROM fee_heads h LEFT JOIN fee_collections fc ON fc.fee_head_id = h.id AND fc.branch_id=h.branch_id'
            . ' LEFT JOIN (SELECT a.fee_collection_id, SUM(a.amount) AS allocated FROM student_payment_allocations a JOIN student_payments p ON p.id=a.payment_id WHERE p.branch_id=' . $branch . ' GROUP BY a.fee_collection_id) pa ON pa.fee_collection_id=fc.id'
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
