<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BranchContext;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Core\Url;

final class UploadController
{
    public function show(Request $request): void
    {
        if (!Auth::check()) { http_response_code(403); exit; }
        $relative = (string) $request->input('path', '');
        if ($relative === '' || str_contains($relative, '..') || str_starts_with($relative, '/') || str_contains($relative, "\\")) {
            http_response_code(404); exit;
        }
        if (!$this->mayRead($relative)) { http_response_code(403); exit; }
        $root = realpath(BASE_PATH . '/storage/uploads');
        $path = realpath(BASE_PATH . '/storage/uploads/' . $relative);
        if ($root === false || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
            http_response_code(404); exit;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        readfile($path);
    }

    private function mayRead(string $relative): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if (str_starts_with($relative, 'student-photos/')) {
            if (Guard::can('students.view')) {
                $query = Database::connection()->prepare('SELECT 1 FROM students WHERE photo=:path AND branch_id=:branch LIMIT 1');
                $query->execute(['path' => $relative, 'branch' => BranchContext::id()]);
                if ($query->fetchColumn()) return true;
            }
            if (($user['link_type'] ?? '') === 'student') {
                $query = Database::connection()->prepare('SELECT 1 FROM students WHERE id=:id AND branch_id=:branch AND photo=:path LIMIT 1');
                $query->execute(['id' => (int) $user['link_id'], 'branch' => BranchContext::id(), 'path' => $relative]);
                return (bool) $query->fetchColumn();
            }
            if (($user['link_type'] ?? '') === 'guardian') {
                $query = Database::connection()->prepare('SELECT 1 FROM student_guardian sg JOIN students s ON s.id=sg.student_id AND s.branch_id=sg.branch_id WHERE sg.guardian_id=:id AND sg.branch_id=:branch AND s.photo=:path LIMIT 1');
                $query->execute(['id' => (int) $user['link_id'], 'branch' => BranchContext::id(), 'path' => $relative]);
                return (bool) $query->fetchColumn();
            }
            return false;
        }
        if (str_starts_with($relative, 'study-materials/')) {
            if (Guard::can('study-materials.manage')) {
                $query = Database::connection()->prepare('SELECT 1 FROM study_materials WHERE file_path=:path AND branch_id=:branch LIMIT 1');
                $query->execute(['path' => $relative, 'branch' => BranchContext::id()]);
                if ($query->fetchColumn()) return true;
            }
            $linkType = (string) ($user['link_type'] ?? '');
            $linkId = (int) ($user['link_id'] ?? 0);
            if ($linkType === 'teacher') {
                $query = Database::connection()->prepare('SELECT 1 FROM study_materials WHERE file_path=:path AND teacher_id=:id AND branch_id=:branch LIMIT 1');
                $query->execute(['path' => $relative, 'id' => $linkId, 'branch' => BranchContext::id()]);
                return (bool) $query->fetchColumn();
            }
            if ($linkType === 'student' || $linkType === 'guardian') {
                $relation = $linkType === 'student' ? 's.id=:id' : 'sg.guardian_id=:id';
                $query = Database::connection()->prepare(
                    'SELECT 1 FROM study_materials m JOIN students s ON s.class_id=m.class_id AND s.branch_id=m.branch_id '
                    . ($linkType === 'guardian' ? 'JOIN student_guardian sg ON sg.student_id=s.id AND sg.branch_id=s.branch_id ' : '')
                    . 'WHERE m.file_path=:path AND m.branch_id=:branch AND ' . $relation . ' AND s.status=\'active\' LIMIT 1'
                );
                $query->execute(['path' => $relative, 'id' => $linkId, 'branch' => BranchContext::id()]);
                return (bool) $query->fetchColumn();
            }
            return false;
        }
        if (str_starts_with($relative, 'exam-attachments/')) {
            if (!Guard::can('exam-suggestions.manage')) return false;
            $query = Database::connection()->prepare('SELECT 1 FROM exam_suggestions WHERE file_path=:path AND branch_id=:branch LIMIT 1');
            $query->execute(['path' => $relative, 'branch' => BranchContext::id()]);
            return (bool) $query->fetchColumn();
        }
        return false;
    }
}
