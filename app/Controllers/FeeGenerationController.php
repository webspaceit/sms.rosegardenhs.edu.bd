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
use App\Models\Student;
use DateTimeImmutable;

final class FeeGenerationController
{
    public function form(Request $request): void
    {
        Guard::requirePermission('fees.manage');
        View::render('fees/generate', ['title' => 'Generate fee invoices', 'user' => Auth::user(), 'years' => Student::academicYears()]);
    }

    public function generate(Request $request): void
    {
        Guard::requirePermission('fees.manage');
        if (!Csrf::verify($request)) {
            http_response_code(419); Toast::flash('Your session expired. Please try again.', 'error'); header('Location: ' . Url::to('/fees/generate')); exit;
        }
        $yearId = (int) $request->input('academic_year_id', 0);
        $month = (string) $request->input('month', '');
        $dueDate = (string) $request->input('due_date', '');
        $monthDate = DateTimeImmutable::createFromFormat('!Y-m', $month);
        $dueDateValue = $dueDate === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
        $validDueDate = $dueDate === '' || ($dueDateValue !== false && $dueDateValue !== null && $dueDateValue->format('Y-m-d') === $dueDate);
        if ($yearId < 1 || !$monthDate || $monthDate->format('Y-m') !== $month || !$validDueDate) {
            Toast::flash('Choose an academic year, valid billing month, and due date.', 'error'); header('Location: ' . Url::to('/fees/generate')); exit;
        }
        $db = Database::connection();
        $sql = "SELECT s.id AS student_id, s.academic_year_id, fs.fee_head_id, fs.amount, h.frequency, h.name AS head_name
                FROM students s JOIN fee_structures fs ON fs.class_id = s.class_id AND fs.branch_id=s.branch_id
                JOIN fee_heads h ON h.id = fs.fee_head_id AND h.branch_id=s.branch_id
                WHERE s.branch_id=:branch AND s.status = 'active' AND s.academic_year_id = :year_id AND h.is_optional = 0";
        $branchId = BranchContext::id();
        $rows = $db->prepare($sql); $rows->execute(['year_id' => $yearId, 'branch' => $branchId]);
        $insert = $db->prepare("INSERT IGNORE INTO fee_collections (student_id, fee_head_id, academic_year_id, invoice_no, month, amount, status, due_date, remarks, branch_id)
                                VALUES (:student_id, :fee_head_id, :academic_year_id, :invoice_no, :month, :amount, 'due', :due_date, 'Generated from fee structure', :branch_id)");
        $created = 0; $db->beginTransaction();
        try {
            foreach ($rows->fetchAll() as $row) {
                $frequency = (string) $row['frequency'];
                $period = match ($frequency) {
                    'yearly' => 'Y' . $monthDate->format('Y'),
                    'one-time' => 'O' . $yearId,
                    default => 'M' . $monthDate->format('Ym'),
                };
                $invoice = sprintf('AUTO-%d-%d-%d-%s', $yearId, (int) $row['student_id'], (int) $row['fee_head_id'], $period);
                $invoice = substr($invoice, 0, 50);
                $insert->execute([
                    'student_id' => (int) $row['student_id'], 'fee_head_id' => (int) $row['fee_head_id'],
                    'academic_year_id' => $yearId, 'invoice_no' => $invoice,
                    'month' => $frequency === 'monthly' ? $monthDate->format('Y-m') : ucfirst($frequency) . ' ' . $monthDate->format('Y'),
                    'amount' => $row['amount'], 'due_date' => $dueDate !== '' ? $dueDate : null, 'branch_id' => $branchId,
                ]);
                $created += $insert->rowCount();
            }
            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            \App\Core\ErrorLog::write($error); Toast::flash('Fee invoices could not be generated.', 'error'); header('Location: ' . Url::to('/fees/generate')); exit;
        }
        Toast::flash($created . ' fee invoice(s) generated. Existing invoices were left unchanged.');
        header('Location: ' . Url::to('/fees')); exit;
    }
}
