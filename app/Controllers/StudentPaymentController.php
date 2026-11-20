<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BranchContext;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;
use PDO;
use Throwable;

final class StudentPaymentController
{
    public function history(Request $request): void
    {
        Guard::requirePermission('fees.manage');
        $branch = BranchContext::id();
        $stmt = Database::read()->prepare('SELECT p.id,p.receipt_no,p.paid_on,p.amount,p.payment_method,p.reference_no,s.name AS student_name,s.student_id AS student_code FROM student_payments p JOIN students s ON s.id=p.student_id WHERE p.branch_id=:branch ORDER BY p.paid_on DESC,p.id DESC LIMIT 300');
        $stmt->execute(['branch' => $branch]);
        View::render('fees/payment-receipts', ['title' => 'Student payment receipts', 'user' => Auth::user(), 'payments' => $stmt->fetchAll()]);
    }

    public function collect(Request $request): void
    {
        Guard::requirePermission('fees.manage');
        $db = Database::read();
        $branch = BranchContext::id();
        $students = $db->prepare('SELECT s.id,s.student_id,s.name,s.roll_no,s.class_id,s.section_id,s.academic_year_id,c.name AS class_name,sec.name AS section_name,ay.name AS academic_year FROM students s LEFT JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN academic_years ay ON ay.id=s.academic_year_id WHERE s.branch_id=:branch ORDER BY c.numeric_level,sec.name,s.roll_no,s.name');
        $students->execute(['branch' => $branch]);
        $studentRows = $students->fetchAll();
        $studentId = max(0, (int) $request->input('student_id', 0));
        $student = null;
        $invoices = [];
        if ($studentId > 0) {
            foreach ($studentRows as $row) if ((int) $row['id'] === $studentId) { $student = $row; break; }
            if ($student !== null) $invoices = $this->outstandingInvoices($studentId, $branch);
        }
        $classes = [];
        $sections = [];
        foreach ($studentRows as $row) {
            $classes[(int) $row['class_id']] = (string) ($row['class_name'] ?? 'Unassigned');
            if (!empty($row['section_id'])) $sections[(int) $row['section_id']] = ['name' => (string) ($row['section_name'] ?? ''), 'class_id' => (int) $row['class_id']];
        }
        if ($student !== null) {
            $selectedClassId = (int) $student['class_id'];
            $selectedSectionId = (int) $student['section_id'];
        } else {
            $selectedClassId = max(0, (int) $request->input('class_id', 0));
            $selectedSectionId = max(0, (int) $request->input('section_id', 0));
        }
        View::render('fees/collect-payment', [
            'title' => 'Collect student payment', 'user' => Auth::user(),
            'students' => $studentRows, 'student' => $student, 'invoices' => $invoices,
            'classes' => $classes, 'sections' => $sections,
            'selectedClassId' => $selectedClassId, 'selectedSectionId' => $selectedSectionId,
        ]);
    }

    public function save(Request $request): void
    {
        Guard::requirePermission('fees.manage');
        if (!Csrf::verify($request)) {
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/fees/new'), true, 419);
            exit;
        }
        $branch = BranchContext::id();
        $studentId = max(0, (int) $request->input('student_id', 0));
        $method = trim((string) $request->input('payment_method', ''));
        $allowedMethods = ['Cash', 'Bank Transfer', 'Cheque', 'Mobile Banking', 'Card', 'Other'];
        $paidOn = trim((string) $request->input('paid_on', date('Y-m-d')));
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $paidOn);
        $allocationsRaw = $request->input('allocations', []);
        $discountsRaw = $request->input('discounts', []);
        $reference = trim((string) $request->input('reference_no', ''));
        $notes = trim((string) $request->input('notes', ''));
        if ($studentId < 1 || !in_array($method, $allowedMethods, true) || !$date || $date->format('Y-m-d') !== $paidOn || !is_array($allocationsRaw) || !is_array($discountsRaw)) {
            Toast::flash('Check the student, payment method, date, and invoice allocations.', 'error');
            header('Location: ' . Url::to('/fees/new' . ($studentId ? '?student_id=' . $studentId : '')), true, 422);
            exit;
        }

        $allocations = [];
        foreach ($allocationsRaw as $invoiceId => $rawAmount) {
            $invoiceId = (int) $invoiceId;
            $rawAmount = trim((string) $rawAmount);
            if ($invoiceId < 1 || $rawAmount === '' || !is_numeric($rawAmount)) continue;
            $cents = (int) round((float) $rawAmount * 100);
            if ($cents > 0) $allocations[$invoiceId] = $cents;
        }
        $discounts = [];
        foreach ($discountsRaw as $invoiceId => $rawAmount) {
            $invoiceId = (int) $invoiceId;
            $rawAmount = trim((string) $rawAmount);
            if ($invoiceId < 1 || $rawAmount === '' || !is_numeric($rawAmount)) continue;
            $cents = (int) round((float) $rawAmount * 100);
            if ($cents > 0) $discounts[$invoiceId] = $cents;
        }
        if (($allocations === [] && $discounts === []) || strlen($reference) > 100 || strlen($notes) > 255) {
            Toast::flash('Enter a payment amount or an approved discount for at least one invoice.', 'error');
            header('Location: ' . Url::to('/fees/new?student_id=' . $studentId), true, 422);
            exit;
        }
        if ($discounts !== [] && !Guard::can('accounts.manage')) {
            Toast::flash('Only staff with Accounts permission can approve invoice discounts.', 'error');
            header('Location: ' . Url::to('/fees/new?student_id=' . $studentId), true, 403);
            exit;
        }

        $db = Database::connection();
        try {
            $db->beginTransaction();
            $studentCheck = $db->prepare('SELECT id FROM students WHERE id=:student AND branch_id=:branch FOR UPDATE');
            $studentCheck->execute(['student' => $studentId, 'branch' => $branch]);
            if (!$studentCheck->fetchColumn()) throw new \RuntimeException('Student not found in the current campus.');

            $locked = $db->prepare('SELECT fc.id,fc.amount,fc.status,fc.student_id,fc.fee_head_id,COALESCE((SELECT SUM(d.amount) FROM fee_invoice_discounts d WHERE d.fee_collection_id=fc.id AND d.approved_by IS NOT NULL),0) AS discounted FROM fee_collections fc WHERE fc.id=:id AND fc.student_id=:student AND fc.branch_id=:branch FOR UPDATE');
            $allocatedBefore = $db->prepare('SELECT COALESCE(SUM(a.amount),0) FROM student_payment_allocations a JOIN student_payments p ON p.id=a.payment_id WHERE a.fee_collection_id=:invoice AND p.branch_id=:branch');
            $lockedInvoices = [];
            foreach (array_unique(array_merge(array_keys($allocations), array_keys($discounts))) as $invoiceId) {
                $locked->execute(['id' => $invoiceId, 'student' => $studentId, 'branch' => $branch]);
                $invoice = $locked->fetch();
                if (!$invoice || $invoice['status'] === 'paid') throw new \RuntimeException('One selected invoice is no longer outstanding. Refresh and try again.');
                $lockedInvoices[$invoiceId] = $invoice;
                $allocatedBefore->execute(['invoice' => $invoiceId, 'branch' => $branch]);
                $paidCents = (int) round((float) $allocatedBefore->fetchColumn() * 100);
                $dueCents = max(0, (int) round((float) $invoice['amount'] * 100) - $paidCents - (int) round((float) $invoice['discounted'] * 100));
                if (($allocations[$invoiceId] ?? 0) + ($discounts[$invoiceId] ?? 0) > $dueCents) throw new \RuntimeException('A payment and discount exceed an invoice balance. Refresh and enter the current balances.');
            }

            $total = array_sum($allocations);
            if ($total === 0 && $discounts !== []) $method = 'Discount';
            $insertPayment = $db->prepare('INSERT INTO student_payments (student_id,paid_on,amount,payment_method,reference_no,notes,received_by,branch_id) VALUES (:student,:paid_on,:amount,:method,:reference,:notes,:user,:branch)');
            $insertPayment->execute([
                'student' => $studentId, 'paid_on' => $paidOn, 'amount' => number_format($total / 100, 2, '.', ''),
                'method' => $method, 'reference' => $reference !== '' ? $reference : null,
                'notes' => $notes !== '' ? $notes : null, 'user' => Auth::user()['id'] ?? null, 'branch' => $branch,
            ]);
            $paymentId = (int) $db->lastInsertId();
            $receiptNo = 'RCP-' . date('Ymd', strtotime($paidOn)) . '-' . str_pad((string) $paymentId, 6, '0', STR_PAD_LEFT);
            $db->prepare('UPDATE student_payments SET receipt_no=:receipt WHERE id=:id')->execute(['receipt' => $receiptNo, 'id' => $paymentId]);
            $insertAllocation = $db->prepare('INSERT INTO student_payment_allocations (payment_id,fee_collection_id,amount,branch_id) VALUES (:payment,:invoice,:amount,:branch)');
            $insertDiscount = $db->prepare('INSERT INTO fee_invoice_discounts (student_id,fee_collection_id,payment_id,amount,reason,approved_by,approved_on,branch_id) VALUES (:student,:invoice,:payment,:amount,:reason,:approver,:date,:branch)');
            foreach ($discounts as $invoiceId => $cents) {
                $insertDiscount->execute(['student' => $studentId, 'invoice' => $invoiceId, 'payment' => $paymentId, 'amount' => number_format($cents / 100, 2, '.', ''), 'reason' => $notes !== '' ? $notes : 'Approved during payment collection', 'approver' => Auth::user()['id'] ?? null, 'date' => $paidOn, 'branch' => $branch]);
            }
            $forInvoice = $db->prepare('SELECT fc.amount,fc.status,COALESCE((SELECT SUM(a.amount) FROM student_payment_allocations a JOIN student_payments p ON p.id=a.payment_id WHERE a.fee_collection_id=fc.id AND p.branch_id=:branch),0) AS allocated,COALESCE((SELECT SUM(d.amount) FROM fee_invoice_discounts d WHERE d.fee_collection_id=fc.id AND d.approved_by IS NOT NULL),0) AS discounted FROM fee_collections fc WHERE fc.id=:id');
            $updateInvoice = $db->prepare('UPDATE fee_collections SET status=:status,paid_on=CASE WHEN :is_paid=1 THEN :paid_on ELSE paid_on END,payment_method=:method WHERE id=:id AND branch_id=:branch');
            foreach (array_unique(array_merge(array_keys($allocations), array_keys($discounts))) as $invoiceId) {
                if (isset($allocations[$invoiceId])) $insertAllocation->execute(['payment' => $paymentId, 'invoice' => $invoiceId, 'amount' => number_format($allocations[$invoiceId] / 100, 2, '.', ''), 'branch' => $branch]);
                $forInvoice->execute(['branch' => $branch, 'id' => $invoiceId]);
                $state = $forInvoice->fetch();
                $isPaid = (int) round(((float) $state['allocated'] + (float) $state['discounted']) * 100) >= (int) round((float) $state['amount'] * 100);
                $updateInvoice->execute(['status' => $isPaid ? 'paid' : 'partial', 'is_paid' => $isPaid ? 1 : 0, 'paid_on' => $paidOn, 'method' => $method, 'id' => $invoiceId, 'branch' => $branch]);
            }
            $db->commit();
            Toast::flash('Payment and approved discounts saved. Receipt ' . $receiptNo . ' is ready to print.');
            header('Location: ' . Url::to('/fees/receipt?id=' . $paymentId));
            exit;
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            Toast::flash($error instanceof \RuntimeException ? $error->getMessage() : 'Payment could not be recorded. No invoice balances were changed.', 'error');
            header('Location: ' . Url::to('/fees/new?student_id=' . $studentId), true, 422);
            exit;
        }
    }

    public function receipt(Request $request): void
    {
        Guard::requirePermission('fees.manage');
        $branch = BranchContext::id();
        $db = Database::read();
        $stmt = $db->prepare('SELECT p.*,s.student_id AS student_code,s.name AS student_name,s.roll_no,s.mobile_number,s.academic_group,s.class_id,c.name AS class_name,sec.name AS section_name,sh.name AS shift_name,ay.name AS academic_year,b.name AS branch_name,u.name AS received_by_name FROM student_payments p JOIN students s ON s.id=p.student_id LEFT JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN shifts sh ON sh.name=s.shift AND sh.branch_id=s.branch_id LEFT JOIN academic_years ay ON ay.id=s.academic_year_id JOIN branches b ON b.id=p.branch_id LEFT JOIN users u ON u.id=p.received_by WHERE p.id=:id AND p.branch_id=:branch LIMIT 1');
        $stmt->execute(['id' => max(0, (int) $request->input('id', 0)), 'branch' => $branch]);
        $payment = $stmt->fetch();
        if (!$payment) { http_response_code(404); View::render('errors/404', ['title' => 'Receipt not found', 'user' => Auth::user()]); return; }
        $allocations = $db->prepare('SELECT COALESCE(a.amount,0) AS amount,COALESCE(d.amount,0) AS discount_amount,fc.invoice_no,fc.month,h.name AS fee_head FROM fee_collections fc JOIN fee_heads h ON h.id=fc.fee_head_id LEFT JOIN student_payment_allocations a ON a.fee_collection_id=fc.id AND a.payment_id=:payment LEFT JOIN (SELECT fee_collection_id,SUM(amount) AS amount FROM fee_invoice_discounts WHERE payment_id=:discount_payment AND approved_by IS NOT NULL GROUP BY fee_collection_id) d ON d.fee_collection_id=fc.id WHERE a.id IS NOT NULL OR d.fee_collection_id IS NOT NULL ORDER BY fc.due_date,fc.id');
        $allocations->execute(['discount_payment' => $payment['id'], 'payment' => $payment['id']]);
        $discountTotalQuery = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM fee_invoice_discounts WHERE payment_id=:payment AND approved_by IS NOT NULL');
        $discountTotalQuery->execute(['payment' => $payment['id']]);
        $discountTotal = (float) $discountTotalQuery->fetchColumn();
        $dueQuery = $db->prepare("SELECT COALESCE(SUM(GREATEST(fc.amount-COALESCE((SELECT SUM(a.amount) FROM student_payment_allocations a JOIN student_payments p ON p.id=a.payment_id WHERE a.fee_collection_id=fc.id AND p.branch_id=fc.branch_id),0)-COALESCE((SELECT SUM(d.amount) FROM fee_invoice_discounts d WHERE d.fee_collection_id=fc.id AND d.approved_by IS NOT NULL),0),0)),0) FROM fee_collections fc WHERE fc.student_id=:student AND fc.branch_id=:branch");
        $dueQuery->execute(['student' => $payment['student_id'], 'branch' => $branch]);
        $dueAfter = (float) $dueQuery->fetchColumn();
        $settings = $db->prepare('SELECT `key`,value FROM school_settings WHERE branch_id=:branch');
        $settings->execute(['branch' => $branch]);
        $schoolSettings = [];
        foreach ($settings->fetchAll() as $row) $schoolSettings[(string) $row['key']] = (string) $row['value'];
        $method = strtolower((string) $payment['payment_method']);
        $bankCopies = str_contains($method, 'bank transfer') || str_contains($method, 'cheque');
        View::render('fees/payment-receipt', [
            'title' => 'Payment receipt ' . $payment['receipt_no'], 'user' => Auth::user(),
            'payment' => $payment, 'allocations' => $allocations->fetchAll(),
            'schoolSettings' => $schoolSettings, 'bankCopies' => $bankCopies,
            'dueAfter' => $dueAfter, 'previousDue' => $dueAfter + (float) $payment['amount'] + $discountTotal,
            'discountTotal' => $discountTotal,
        ]);
    }

    private function outstandingInvoices(int $studentId, int $branch): array
    {
        $stmt = Database::read()->prepare('SELECT fc.id,fc.invoice_no,fc.month,fc.amount,fc.due_date,h.name AS fee_head,COALESCE((SELECT SUM(a.amount) FROM student_payment_allocations a JOIN student_payments p ON p.id=a.payment_id WHERE a.fee_collection_id=fc.id AND p.branch_id=fc.branch_id),0) AS allocated,COALESCE((SELECT SUM(d.amount) FROM fee_invoice_discounts d WHERE d.fee_collection_id=fc.id AND d.approved_by IS NOT NULL),0) AS discounted FROM fee_collections fc JOIN fee_heads h ON h.id=fc.fee_head_id WHERE fc.student_id=:student AND fc.branch_id=:branch AND fc.status <> \'paid\' ORDER BY fc.due_date,fc.id');
        $stmt->execute(['student' => $studentId, 'branch' => $branch]);
        return array_values(array_filter($stmt->fetchAll(), static fn (array $invoice): bool => (float) $invoice['amount'] - (float) $invoice['allocated'] - (float) $invoice['discounted'] > 0.001));
    }
}
