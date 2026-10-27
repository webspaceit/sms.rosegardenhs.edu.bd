<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/** Database-backed login rate limiter suitable for shared hosting. */
final class LoginThrottle
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    public static function blocked(string $email): bool
    {
        $row = self::find($email);
        return $row !== null && (int) $row['attempts'] >= self::MAX_ATTEMPTS
            && strtotime((string) $row['last_attempt_at']) > time() - self::WINDOW_MINUTES * 60;
    }

    public static function failed(string $email): void
    {
        $db = Database::connection();
        $statement = $db->prepare(
            'INSERT INTO login_attempts (email_hash, ip_hash, attempts, last_attempt_at) '
            . 'VALUES (:email, :ip, 1, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE '
            . 'attempts = IF(last_attempt_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 15 MINUTE), 1, attempts + 1), '
            . 'last_attempt_at = CURRENT_TIMESTAMP'
        );
        $statement->execute(self::keys($email));
    }

    public static function succeeded(string $email): void
    {
        $statement = Database::connection()->prepare('DELETE FROM login_attempts WHERE email_hash = :email AND ip_hash = :ip');
        $statement->execute(self::keys($email));
    }

    private static function find(string $email): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT attempts, last_attempt_at FROM login_attempts WHERE email_hash = :email AND ip_hash = :ip LIMIT 1'
        );
        $statement->execute(self::keys($email));
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    private static function keys(string $email): array
    {
        return [
            'email' => hash('sha256', strtolower(trim($email))),
            'ip' => hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown')),
        ];
    }
}
