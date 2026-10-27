<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

/** Lightweight, dependency-free audit trail for sensitive application actions. */
final class AuditLog
{
    public static function record(string $action, string $entity, ?int $entityId = null, array $details = []): void
    {
        try {
            $user = Auth::user();
            $safe = array_intersect_key($details, array_flip(['route', 'count', 'source', 'status']));
            $statement = Database::connection()->prepare(
                'INSERT INTO audit_logs (user_id, user_email, action, entity, entity_id, details, ip_address, created_at, branch_id) '
                . 'VALUES (:user_id, :user_email, :action, :entity, :entity_id, :details, :ip_address, CURRENT_TIMESTAMP, :branch_id)'
            );
            $statement->execute([
                'user_id' => $user['id'] ?? null,
                'user_email' => $user['email'] ?? null,
                'action' => substr($action, 0, 100),
                'entity' => substr($entity, 0, 100),
                'entity_id' => $entityId,
                'details' => $safe === [] ? null : json_encode($safe, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
                'branch_id' => BranchContext::id(),
            ]);
        } catch (Throwable $error) {
            error_log('Audit log write failed: ' . $error->getMessage());
        }
    }
}
