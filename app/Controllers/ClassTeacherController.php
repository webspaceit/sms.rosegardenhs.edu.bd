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
use App\Core\Upload;
use App\Core\View;
use App\Models\Catalogue;

/**
 * Who is in charge of each class.
 *
 * The class is the source of truth for its class teacher. Subject assignments
 * display that status, but do not control whether the class teacher is saved.
 */
final class ClassTeacherController
{
    public function index(Request $request): void
    {
        Guard::requirePermission('classes.manage');

        $statement = Database::connection()->query(
            'SELECT c.id AS class_id, c.name AS class_name, c.numeric_level,'
            . ' c.class_teacher_id AS teacher_id, c.class_teacher_signature, t.name AS teacher_name,'
            . ' (SELECT s.name FROM teacher_subject ts JOIN subjects s ON s.id=ts.subject_id AND s.branch_id=ts.branch_id'
            . ' WHERE ts.class_id=c.id AND ts.teacher_id=c.class_teacher_id AND ts.branch_id=c.branch_id'
            . ' ORDER BY ts.is_class_teacher DESC, ts.id LIMIT 1) AS subject_name'
            . ' FROM classes c'
            . ' LEFT JOIN teachers t ON t.id = c.class_teacher_id AND t.branch_id=c.branch_id'
            . ' WHERE c.branch_id=' . BranchContext::id()
            . ' ORDER BY c.numeric_level'
        );

        $rows = $statement->fetchAll();

        $classes = [];
        foreach ($rows as $row) {
            $classId = (int) $row['class_id'];
            if (!isset($classes[$classId])) {
                $classes[$classId] = [
                    'class_id' => $classId,
                    'class_name' => (string) $row['class_name'],
                    'numeric_level' => (int) $row['numeric_level'],
                    'teacher_id' => $row['teacher_id'] !== null ? (int) $row['teacher_id'] : 0,
                    'teacher_name' => (string) ($row['teacher_name'] ?? ''),
                    'subject_name' => (string) ($row['subject_name'] ?? ''),
                    'class_teacher_signature' => (string) ($row['class_teacher_signature'] ?? ''),
                ];
            }
        }

        View::render('allocation/class-teachers', [
            'title' => 'Class teachers',
            'user' => Auth::user(),
            'classes' => array_values($classes),
            'teachers' => Catalogue::teachers(),
        ]);
    }

    public function update(Request $request): void
    {
        Guard::requirePermission('classes.manage');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/class-teachers'));
            exit;
        }

        $classId = (int) $request->input('class_id', 0);
        $teacherId = (int) $request->input('teacher_id', 0);
        $uploadedSignature = null;

        if ($classId <= 0) {
            Toast::flash('Choose a class first.', 'error');
            header('Location: ' . Url::to('/class-teachers'));
            exit;
        }

        $classCheck = Database::connection();
        $classStatement = $classCheck->prepare('SELECT class_teacher_signature FROM classes WHERE id=:id AND branch_id=:branch LIMIT 1');
        $classStatement->execute(['id' => $classId, 'branch' => BranchContext::id()]);
        $classRecord = $classStatement->fetch();
        if ($classRecord === false) {
            Toast::flash('Choose a class in the active campus.', 'error');
            header('Location: ' . Url::to('/class-teachers'));
            exit;
        }

        if ($teacherId > 0 && !$this->teacherExists($teacherId)) {
            Toast::flash('That teacher record no longer exists.', 'error');
            header('Location: ' . Url::to('/class-teachers'));
            exit;
        }

        if ($teacherId === 0 && isset($_FILES['class_teacher_signature']) && (int) ($_FILES['class_teacher_signature']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            Toast::flash('Choose a class teacher before uploading a signature.', 'error');
            header('Location: ' . Url::to('/class-teachers'));
            exit;
        }

        try {
            $uploadedSignature = Upload::store('class_teacher_signature', 'class-teacher-signatures', ['jpg', 'jpeg', 'png', 'webp'], 2097152);
        } catch (\Throwable $error) {
            Toast::flash($error->getMessage(), 'error');
            header('Location: ' . Url::to('/class-teachers'));
            exit;
        }

        $db = Database::connection();
        $db->beginTransaction();
        $oldSignature = (string) ($classRecord['class_teacher_signature'] ?? '');

        try {
            $branch = BranchContext::id();
            $signature = $teacherId > 0 ? ($uploadedSignature ?? ($oldSignature !== '' ? $oldSignature : null)) : null;
            $save = $db->prepare('UPDATE classes SET class_teacher_id=:teacher_id, class_teacher_signature=:signature WHERE id=:class_id AND branch_id=:branch');
            $save->execute([
                'teacher_id' => $teacherId > 0 ? $teacherId : null,
                'signature' => $signature,
                'class_id' => $classId,
                'branch' => $branch,
            ]);
            if ($save->rowCount() === 0) {
                $verify = $db->prepare('SELECT id FROM classes WHERE id=:class_id AND branch_id=:branch LIMIT 1');
                $verify->execute(['class_id' => $classId, 'branch' => $branch]);
                if ($verify->fetchColumn() === false) throw new \RuntimeException('The class is no longer available in this campus.');
            }

            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Upload::delete($uploadedSignature);
            \App\Core\ErrorLog::write($error);
            Toast::flash('The class teacher could not be saved. Please try again.', 'error');
            header('Location: ' . Url::to('/class-teachers'));
            exit;
        }

        if ($oldSignature !== '' && ($teacherId === 0 || $uploadedSignature !== null)) Upload::delete($oldSignature);

        Toast::flash('Class teacher saved.');
        header('Location: ' . Url::to('/class-teachers'));
        exit;
    }

    /** Whether a teacher with this id is on the staff list. */
    private function teacherExists(int $teacherId): bool
    {
        $statement = Database::connection()->prepare('SELECT id FROM teachers WHERE id = :id AND branch_id=:branch LIMIT 1');
        $statement->execute(['id' => $teacherId, 'branch' => BranchContext::id()]);
        return $statement->fetchColumn() !== false;
    }
}
