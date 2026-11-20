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
        $students = $db->prepare('SELECT s.id,s.student_id,s.name,s.roll_no,c.name AS class_name FROM students s LEFT JOIN classes c ON c.id=s.class_id WHERE s.branch_id=:branch ORDER BY s.name,s.student_id');
        $students->execute(['branch' => $branch]);
        $studentRows = $students->fetchAll();
        $studentId = max(0, (int) $request->input('student_id', 0));
        $student = null;
        $invoices = [];
        if ($studentId > 0) {
            foreach ($studentRows as $row) if ((int) $row['id'] === $studentId) { $student = $row; break; }
            if ($student !== null) $invoices = $this->outstandingInvoices($studentId, $branch);
        }
        View::render('fees/collect-payment', [
            'title' => 'Collect student payment', 'user' => Auth::user(),
            'students' => $studentRows, 'student' => $student, 'invoices' => $invoices,
        ]);
    }

    public function save(Request $request): void
    {
        Guard::requirePermission('fees.manage');
        if (!Csrf::verify($request)) {
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/fees/collect'), true, 419);
            exit;
        }
        $branch = BranchContext::id();
        $studentId = max(0, (int) $request->input('student_id', 0));
        $method = trim((string) $request->input('payment_method', ''));
        $allowedMethods = ['Cash', 'Bank Transfer', 'Cheque', 'Mobile Banking', 'Card', 'Other'];
        $paidOn = trim((string) $request->input('paid_on', date('Y-m-d')));
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $paidOn);
        $allocationsRaw = $request->input('allocations', []);
        $reference = trim((string) $request->input('reference_no', ''));
        $notes = trim((string) $request->input('notes', ''));
        if ($studentId < 1 || !in_array($method, $allowedMethods, true) || !$date || $date->format('Y-m-d') !== $paidOn || !is_array($allocationsRaw)) {
            Toast::flash('Check the student, payment method, date, and invoice allocations.', 'error');
            header('Location: ' . Url::to('/fees/collect' . ($studentId ? '?student_id=' . $studentId : '')), true, 422);
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
        if ($allocations === [] || array_sum($allocations) < 1 || strlen($reference) > 100 || strlen($notes) > 255) {
            Toast::flash('Enter a positive amount for at least one invoice.', 'error');
            header('Location: ' . Url::to('/fees/collect?student_id=' . $studentId), true, 422);
            exit;
        }

        $db = Database::connection();
        try {
            $db->beginTransaction();
            $studentCheck = $db->prepare('SELECT id FROM students WHERE id=:student AND branch_id=:branch FOR UPDATE');
            $studentCheck->execute(['student' => $studentId, 'branch' => $branch]);
            if (!$studentCheck->fetchColumn()) throw new \RuntimeException('Student not found in the current campus.');

            $locked = $db->prepare('SELECT fc.id,fc.amount,fc.status,fc.student_id FROM fee_collections fc WHERE fc.id=:id AND fc.student_id=:student AND fc.branch_id=:branch FOR UPDATE');
            $allocatedBefore = $db->prepare('SELECT COALESCE(SUM(a.amount),0) FROM student_payment_allocations a JOIN student_payments p ON p.id=a.payment_id WHERE a.fee_collection_id=:invoice AND p.branch_id=:branch');
            foreach ($allocations as $invoiceId => $cents) {
                $locked->execute(['id' => $invoiceId, 'student' => $studentId, 'branch' => $branch]);
                $invoice = $locked->fetch();
                if (!$invoice || $invoice['status'] === 'paid') throw new \RuntimeException('One selected invoice is no longer outstanding. Refresh and try again.');
                $allocatedBefore->execute(['invoice' => $invoiceId, 'branch' => $branch]);
                $paidCents = (int) round((float) $allocatedBefore->fetchColumn() * 100);
                $dueCents = max(0, (int) round((float) $invoice['amount'] * 100) - $paidCents);
                if ($cents > $dueCents) throw new \RuntimeException('An allocation exceeds its invoice balance. Refresh and enter the current balances.');
            }

            $total = array_sum($allocations);
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
            $forInvoice = $db->prepare('SELECT fc.amount,fc.status,COALESCE((SELECT SUM(a.amount) FROM student_payment_allocations a JOIN student_payments p ON p.id=a.payment_id WHERE a.fee_collection_id=fc.id AND p.branch_id=:branch),0) AS allocated FROM fee_collections fc WHERE fc.id=:id');
            $updateInvoice = $db->prepare('UPDATE fee_collections SET status=:status,paid_on=CASE WHEN :is_paid=1 THEN :paid_on ELSE paid_on END,payment_method=:method WHERE id=:id AND branch_id=:branch');
            foreach ($allocations as $invoiceId => $cents) {
                $insertAllocation->execute(['payment' => $paymentId, 'invoice' => $invoiceId, 'amount' => number_format($cents / 100, 2, '.', ''), 'branch' => $branch]);
                $forInvoice->execute(['branch' => $branch, 'id' => $invoiceId]);
                $state = $forInvoice->fetch();
                $isPaid = (int) round((float) $state['allocated'] * 100) >= (int) round((float) $state['amount'] * 100);
                $updateInvoice->execute(['status' => $isPaid ? 'paid' : 'partial', 'is_paid' => $isPaid ? 1 : 0, 'paid_on' => $paidOn, 'method' => $method, 'id' => $invoiceId, 'branch' => $branch]);
            }
            $db->commit();
            Toast::flash('Payment recorded. Receipt ' . $receiptNo . ' is ready to print.');
            header('Location: ' . Url::to('/fees/receipt?id=' . $paymentId));
            exit;
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            Toast::flash($error instanceof \RuntimeException ? $error->getMessage() : 'Payment could not be recorded. No invoice balances were changed.', 'error');
            header('Location: ' . Url::to('/fees/collect?student_id=' . $studentId), true, 422);
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
        $allocations = $db->prepare('SELECT a.amount,fc.invoice_no,fc.month,h.name AS fee_head FROM student_payment_allocations a JOIN fee_collections fc ON fc.id=a.fee_collection_id JOIN fee_heads h ON h.id=fc.fee_head_id WHERE a.payment_id=:payment ORDER BY fc.due_date,fc.id');
        $allocations->execute(['payment' => $payment['id']]);
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
        ]);
    }

    private function outstandingInvoices(int $studentId, int $branch): array
    {
        $stmt = Database::read()->prepare('SELECT fc.id,fc.invoice_no,fc.month,fc.amount,fc.due_date,h.name AS fee_head,COALESCE((SELECT SUM(a.amount) FROM student_payment_allocations a JOIN student_payments p ON p.id=a.payment_id WHERE a.fee_collection_id=fc.id AND p.branch_id=fc.branch_id),0) AS allocated FROM fee_collections fc JOIN fee_heads h ON h.id=fc.fee_head_id WHERE fc.student_id=:student AND fc.branch_id=:branch AND fc.status <> \'paid\' ORDER BY fc.due_date,fc.id');
        $stmt->execute(['student' => $studentId, 'branch' => $branch]);
        return array_values(array_filter($stmt->fetchAll(), static fn (array $invoice): bool => (float) $invoice['amount'] - (float) $invoice['allocated'] > 0.001));
    }
}
