<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
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

final class StudentPromotionController
{
    public function index(Request $request): void
    {
        Guard::requirePermission('students.edit');
        $db = Database::connection();
        View::render('students/promote', [
            'title' => 'Promote students',
            'user' => Auth::user(),
            'years' => $db->query('SELECT id,name,start_date,end_date,is_current FROM academic_years WHERE branch_id=' . BranchContext::id() . ' ORDER BY start_date DESC')->fetchAll(),
            'classes' => $db->query('SELECT id,name,numeric_level FROM classes WHERE branch_id=' . BranchContext::id() . ' ORDER BY numeric_level')->fetchAll(),
            'sections' => $db->query('SELECT id,class_id,name FROM sections WHERE branch_id=' . BranchContext::id() . ' ORDER BY class_id,name')->fetchAll(),
        ]);
    }

    public function promote(Request $request): void
    {
        Guard::requirePermission('students.edit');
        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/students/promote'));
            exit;
        }

        $sourceYear = (int) $request->input('source_year_id', 0);
        $targetYear = (int) $request->input('target_year_id', 0);
        $sourceClass = (int) $request->input('source_class_id', 0);
        $targetClass = (int) $request->input('target_class_id', 0);
        $targetSection = (int) $request->input('target_section_id', 0);
        $db = Database::connection();
        $valid = $db->prepare('SELECT COUNT(*) FROM sections WHERE id=:section AND class_id=:class AND branch_id=:branch');
        $valid->execute(['section' => $targetSection, 'class' => $targetClass, 'branch' => BranchContext::id()]);
        $yearCheck = $db->prepare('SELECT COUNT(*) FROM academic_years WHERE id IN (:source,:target) AND branch_id=:branch');
        $yearCheck->execute(['source' => $sourceYear, 'target' => $targetYear, 'branch' => BranchContext::id()]);
        if ($sourceYear <= 0 || $targetYear <= 0 || $sourceYear === $targetYear || $sourceClass <= 0 || $targetClass <= 0
            || $targetSection <= 0 || (int) $valid->fetchColumn() !== 1 || (int) $yearCheck->fetchColumn() !== 2) {
            Toast::flash('Select a valid source class, source year, target class, section, and different target year.', 'error');
            header('Location: ' . Url::to('/students/promote'));
            exit;
        }

        try {
            $db->beginTransaction();
            $students = $db->prepare('SELECT id,academic_year_id,class_id,section_id,roll_no,status FROM students WHERE academic_year_id=:year AND class_id=:class AND status=\'active\' AND branch_id=:branch ORDER BY roll_no,id FOR UPDATE');
            $students->execute(['year' => $sourceYear, 'class' => $sourceClass, 'branch' => BranchContext::id()]);
            $records = $students->fetchAll(PDO::FETCH_ASSOC);
            if ($records === []) throw new \RuntimeException('No active students were found in the selected source class and year.');

            $nextRollQuery = $db->prepare('SELECT COALESCE(MAX(roll_no),0)+1 FROM students WHERE academic_year_id=:year AND class_id=:class AND section_id=:section AND branch_id=:branch');
            $nextRollQuery->execute(['year' => $targetYear, 'class' => $targetClass, 'section' => $targetSection, 'branch' => BranchContext::id()]);
            $roll = (int) $nextRollQuery->fetchColumn();
            $history = $db->prepare('INSERT INTO student_enrollment_history (student_id,academic_year_id,class_id,section_id,roll_no,status,recorded_by,branch_id) VALUES (:student,:year,:class,:section,:roll,:status,:user,:branch)');
            $update = $db->prepare('UPDATE students SET academic_year_id=:year,class_id=:class,section_id=:section,roll_no=:roll WHERE id=:id AND branch_id=:branch');
            foreach ($records as $record) {
                $history->execute([
                    'student' => (int) $record['id'], 'year' => (int) $record['academic_year_id'],
                    'class' => (int) $record['class_id'], 'section' => (int) $record['section_id'],
                    'roll' => (int) $record['roll_no'], 'status' => $record['status'],
                    'user' => (int) (Auth::user()['id'] ?? 0) ?: null,
                    'branch' => BranchContext::id(),
                ]);
                $update->execute(['year' => $targetYear, 'class' => $targetClass, 'section' => $targetSection, 'roll' => $roll++, 'id' => (int) $record['id'], 'branch' => BranchContext::id()]);
            }
            $db->commit();
            AuditLog::record('students.promoted', 'students', null, ['count' => count($records), 'source' => $sourceYear . ':' . $sourceClass]);
            Toast::flash(count($records) . ' student(s) promoted. Their previous class, section, roll, and year were saved to enrollment history.');
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            error_log((string) $error);
            Toast::flash($error instanceof \RuntimeException ? $error->getMessage() : 'Promotion could not be completed; no records were changed.', 'error');
        }
        header('Location: ' . Url::to('/students/promote'));
        exit;
    }
}
