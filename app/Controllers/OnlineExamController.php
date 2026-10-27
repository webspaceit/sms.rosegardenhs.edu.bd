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

/**
 * The questions of an online exam and the marks its students scored.
 */
final class OnlineExamController
{
    public function questions(Request $request): void
    {
        Guard::requirePermission('exams.manage');

        $examId = (int) $request->input('exam', 0);
        $exam = $this->exam($examId);

        if ($exam === null) {
            Toast::flash('Online exam not found.', 'error');
            header('Location: ' . Url::to('/online-exams'));
            exit;
        }

        View::render('results/online-questions', [
            'title' => 'Questions · ' . $exam['title'],
            'user' => Auth::user(),
            'exam' => $exam,
            'questions' => $this->questionsFor($examId),
        ]);
    }

    public function storeQuestion(Request $request): void
    {
        Guard::requirePermission('exams.manage');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/online-exams'));
            exit;
        }

        $examId = (int) $request->input('exam', 0);
        $question = trim((string) $request->input('question', ''));
        $type = (string) $request->input('type', 'mcq');

        if ($question === '' || !in_array($type, ['mcq', 'written'], true)) {
            Toast::flash('A question and a type are required.', 'error');
            header('Location: ' . Url::to('/online-exams/questions?exam=' . $examId));
            exit;
        }

        $options = trim((string) $request->input('options', ''));
        $options = $options === '' ? null : json_encode(array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $options)))));

        $statement = Database::connection()->prepare(
            'INSERT INTO exam_questions (online_exam_id, question, type, options, correct_answer, marks, branch_id)'
            . ' VALUES (:exam_id, :question, :type, :options, :correct_answer, :marks, :branch)'
        );
        $statement->execute([
            'exam_id' => $examId,
            'question' => $question,
            'type' => $type,
            'options' => $options,
            'correct_answer' => trim((string) $request->input('correct_answer', '')) ?: null,
            'marks' => (int) $request->input('marks', 1),
            'branch' => BranchContext::id(),
        ]);

        Toast::flash('Question added.');
        header('Location: ' . Url::to('/online-exams/questions?exam=' . $examId));
        exit;
    }

    public function deleteQuestion(Request $request): void
    {
        Guard::requirePermission('exams.manage');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/online-exams'));
            exit;
        }

        $statement = Database::connection()->prepare('DELETE FROM exam_questions WHERE id = :id AND branch_id=:branch');
        $statement->execute(['id' => (int) $request->input('id', 0), 'branch' => BranchContext::id()]);

        Toast::flash('Question removed.');
        header('Location: ' . Url::to('/online-exams/questions?exam=' . (int) $request->input('exam', 0)));
        exit;
    }

    public function results(Request $request): void
    {
        Guard::requirePermission('exams.manage');

        $examId = (int) $request->input('exam', 0);
        $exam = $this->exam($examId);

        if ($exam === null) {
            Toast::flash('Online exam not found.', 'error');
            header('Location: ' . Url::to('/online-exams'));
            exit;
        }

        $statement = Database::connection()->prepare(
            'SELECT es.*, s.name AS student_name, s.student_id AS student_code, s.roll_no'
            . ' FROM exam_submissions es JOIN students s ON s.id = es.student_id AND s.branch_id=es.branch_id'
            . ' WHERE es.online_exam_id = :exam_id AND es.branch_id=:branch ORDER BY es.score DESC, s.roll_no ASC'
        );
        $statement->execute(['exam_id' => $examId, 'branch' => BranchContext::id()]);

        View::render('results/online-results', [
            'title' => 'Results · ' . $exam['title'],
            'user' => Auth::user(),
            'exam' => $exam,
            'submissions' => $statement->fetchAll(),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function exam(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM online_exams WHERE id = :id AND branch_id=:branch LIMIT 1');
        $statement->execute(['id' => $id, 'branch' => BranchContext::id()]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function questionsFor(int $examId): array
    {
        $statement = Database::connection()->prepare('SELECT * FROM exam_questions WHERE online_exam_id = :id AND branch_id=:branch ORDER BY id');
        $statement->execute(['id' => $examId, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }
}
