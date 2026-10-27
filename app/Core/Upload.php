<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Dependency-free upload handler for shared-hosting deployments. */
final class Upload
{
    public static function store(string $field, string $directory, array $extensions, int $maxBytes): ?string
    {
        $file = $_FILES[$field] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('The file upload failed or exceeds the allowed size.');
        }
        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, $extensions, true) || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            throw new RuntimeException('This file type is not allowed.');
        }
        $allowedMime = [
            'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp'],
            'pdf' => ['application/pdf'], 'doc' => ['application/msword', 'application/vnd.ms-office'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'ppt' => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
            'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'mp4' => ['video/mp4', 'application/octet-stream'],
        ];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        if ($mime === false || !in_array($mime, $allowedMime[$extension] ?? [], true)) {
            throw new RuntimeException('The uploaded file does not match its file type.');
        }
        $relative = trim($directory, '/') . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
        $root = BASE_PATH . '/storage/uploads';
        $target = $root . '/' . $relative;
        $folder = dirname($target);
        if (!is_dir($folder) && !mkdir($folder, 0750, true) && !is_dir($folder)) {
            throw new RuntimeException('Upload storage is not available.');
        }
        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            throw new RuntimeException('The uploaded file could not be saved.');
        }
        return $relative;
    }

    public static function delete(?string $relative): void
    {
        if (!$relative || str_contains($relative, '..') || str_starts_with($relative, '/')) return;
        $path = BASE_PATH . '/storage/uploads/' . $relative;
        if (is_file($path)) @unlink($path);
    }
}
