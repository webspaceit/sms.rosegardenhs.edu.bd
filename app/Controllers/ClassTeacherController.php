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
 * Who is in charge of each class.
 *
 * A class teacher is a teacher subject assignment flagged as the class
 * teacher, so the two screens always agree.
 */
final class ClassTeacherController
{
    public function index(Request $request): void
    {
        Guard::requirePermission('classes.manage');

        $statement = Database::connection()->query(
            'SELECT c.id AS class_id, c.name AS class_name, c.numeric_level,'
            . ' ts.subject_id, ts.teacher_id, t.name AS teacher_name, s.name AS subject_name'
            . ' FROM classes c'
            . ' LEFT JOIN teacher_subject ts ON ts.class_id = c.id AND ts.branch_id=c.branch_id AND ts.is_class_teacher = 1'
            . ' LEFT JOIN teachers t ON t.id = ts.teacher_id AND t.branch_id=c.branch_id'
            . ' LEFT JOIN subjects s ON s.id = ts.subject_id AND s.branch_id=c.branch_id'
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

        if ($classId <= 0) {
            Toast::flash('Choose a class first.', 'error');
            header('Location: ' . Url::to('/class-teachers'));
            exit;
        }

        $classCheck = Database::connection();
        $classStatement = $classCheck->prepare('SELECT 1 FROM classes WHERE id=:id AND branch_id=:branch LIMIT 1');
        $classStatement->execute(['id' => $classId, 'branch' => BranchContext::id()]);
        if ($classStatement->fetchColumn() === false) {
            Toast::flash('Choose a class in the active campus.', 'error');
            header('Location: ' . Url::to('/class-teachers'));
            exit;
        }

        if ($teacherId > 0 && !$this->teacherExists($teacherId)) {
            Toast::flash('That teacher record no longer exists.', 'error');
            header('Location: ' . Url::to('/class-teachers'));
            exit;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $branch = BranchContext::id();
            $db->prepare('UPDATE teacher_subject SET is_class_teacher = 0 WHERE class_id = :class_id AND branch_id=:branch')->execute(['class_id' => $classId, 'branch' => $branch]);

            if ($teacherId > 0) {
                $statement = $db->prepare(
                    'SELECT id FROM teacher_subject WHERE class_id = :class_id AND teacher_id = :teacher_id AND branch_id=:branch LIMIT 1'
                );
                $statement->execute(['class_id' => $classId, 'teacher_id' => $teacherId, 'branch' => $branch]);
                $existing = $statement->fetchColumn();

                if ($existing === false) {
                    $subject = $db->query(
                        'SELECT subject_id FROM class_subject WHERE class_id = ' . $classId . ' AND branch_id=' . $branch . ' ORDER BY sort_order LIMIT 1'
                    )->fetchColumn();
                    if ($subject !== false) {
                        $db->prepare(
                            'INSERT INTO teacher_subject (teacher_id, class_id, subject_id, is_class_teacher, branch_id) VALUES (:teacher_id, :class_id, :subject_id, 1, :branch)'
                        )->execute(['teacher_id' => $teacherId, 'class_id' => $classId, 'subject_id' => (int) $subject, 'branch' => $branch]);
                    }
                } else {
                    $db->prepare('UPDATE teacher_subject SET is_class_teacher = 1 WHERE id = :id AND branch_id=:branch')->execute(['id' => (int) $existing, 'branch' => $branch]);
                }
            }

            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            \App\Core\ErrorLog::write($error);
            Toast::flash('The class teacher could not be saved. Please try again.', 'error');
            header('Location: ' . Url::to('/class-teachers'));
            exit;
        }

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
