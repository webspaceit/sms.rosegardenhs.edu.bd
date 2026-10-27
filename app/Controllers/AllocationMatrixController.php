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
use App\Models\Catalogue;

/**
 * The teacher allocation matrix.
 *
 * Shows every subject of a class beside the teacher who teaches it, and lets
 * an administrator change the teacher in one step.
 */
final class AllocationMatrixController
{
    public function index(Request $request): void
    {
        Guard::requirePermission('classes.manage');

        $classId = (int) $request->input('class_id', 0);
        if ($classId <= 0) {
            $first = array_key_first(Catalogue::classes());
            $classId = $first !== null ? (int) $first : 0;
        }

        View::render('allocation/index', [
            'title' => 'Allocation matrix',
            'user' => Auth::user(),
            'classes' => Catalogue::classes(),
            'classId' => $classId,
            'rows' => $this->matrix($classId),
            'teachers' => Catalogue::teachers(),
        ]);
    }

    public function update(Request $request): void
    {
        Guard::requirePermission('classes.manage');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/allocation-matrix'));
            exit;
        }

        $classId = (int) $request->input('class_id', 0);
        $classCheck = Database::connection()->prepare('SELECT 1 FROM classes WHERE id=:id AND branch_id=:branch LIMIT 1');
        $classCheck->execute(['id' => $classId, 'branch' => BranchContext::id()]);
        if ($classCheck->fetchColumn() === false) {
            Toast::flash('Choose a class in the active campus.', 'error');
            header('Location: ' . Url::to('/allocation-matrix'));
            exit;
        }
        $subjects = $request->input('subjects', []);
        $subjects = is_array($subjects) ? $subjects : [];

        $teacherIds = array_map('intval', array_keys(Catalogue::teachers()));
        $validSubjects = array_map('intval', array_keys(Catalogue::subjects()));

        foreach ($subjects as $subjectId => $teacherId) {
            if ((int) $teacherId > 0 && !in_array((int) $teacherId, $teacherIds, true)) {
                Toast::flash('One of the chosen teachers no longer exists.', 'error');
                header('Location: ' . Url::to('/allocation-matrix?class_id=' . $classId));
                exit;
            }
            if (!in_array((int) $subjectId, $validSubjects, true)) {
                Toast::flash('One of the chosen subjects no longer exists.', 'error');
                header('Location: ' . Url::to('/allocation-matrix?class_id=' . $classId));
                exit;
            }
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $find = $db->prepare('SELECT id, teacher_id FROM teacher_subject WHERE class_id = :class_id AND subject_id = :subject_id AND branch_id=:branch LIMIT 1');
            $insert = $db->prepare(
                'INSERT INTO teacher_subject (teacher_id, class_id, subject_id, is_class_teacher, branch_id) VALUES (:teacher_id, :class_id, :subject_id, 0, :branch)'
            );
            $update = $db->prepare('UPDATE teacher_subject SET teacher_id = :teacher_id WHERE id = :id AND branch_id=:branch');
            $clear = $db->prepare('DELETE FROM teacher_subject WHERE class_id = :class_id AND subject_id = :subject_id AND branch_id=:branch');

            $changed = 0;
            foreach ($subjects as $subjectId => $teacherId) {
                $subjectId = (int) $subjectId;
                $teacherId = (int) $teacherId;

                $find->execute(['class_id' => $classId, 'subject_id' => $subjectId, 'branch' => BranchContext::id()]);
                $existing = $find->fetch();

                if ($teacherId <= 0) {
                    if ($existing !== false) {
                        $clear->execute(['class_id' => $classId, 'subject_id' => $subjectId, 'branch' => BranchContext::id()]);
                        $changed++;
                    }
                    continue;
                }

                if ($existing === false) {
                    $insert->execute(['teacher_id' => $teacherId, 'class_id' => $classId, 'subject_id' => $subjectId, 'branch' => BranchContext::id()]);
                } else {
                    $update->execute(['teacher_id' => $teacherId, 'id' => (int) $existing['id'], 'branch' => BranchContext::id()]);
                }
                $changed++;
            }

            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            \App\Core\ErrorLog::write($error);
            Toast::flash('The allocation matrix could not be saved. Please try again.', 'error');
            header('Location: ' . Url::to('/allocation-matrix?class_id=' . $classId));
            exit;
        }

        Toast::flash('Allocation matrix updated (' . $changed . ' subject(s)).');
        header('Location: ' . Url::to('/allocation-matrix?class_id=' . $classId));
        exit;
    }

    /**
     * The subjects of a class and the teacher assigned to each.
     *
     * @return array<int, array<string, mixed>>
     */
    private function matrix(int $classId): array
    {
        if ($classId <= 0) {
            return [];
        }

        $statement = Database::connection()->prepare(
            'SELECT s.id AS subject_id, s.name AS subject_name, s.code,'
            . ' ts.id AS assignment_id, ts.teacher_id, ts.is_class_teacher, t.name AS teacher_name'
            . ' FROM class_subject cs JOIN subjects s ON s.id = cs.subject_id AND s.branch_id=cs.branch_id'
            . ' LEFT JOIN teacher_subject ts ON ts.subject_id = s.id AND ts.class_id = cs.class_id AND ts.branch_id=cs.branch_id'
            . ' LEFT JOIN teachers t ON t.id = ts.teacher_id AND t.branch_id=cs.branch_id'
            . ' WHERE cs.class_id = :class_id AND cs.branch_id=:branch ORDER BY cs.sort_order, s.name'
        );
        $statement->execute(['class_id' => $classId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }
}
