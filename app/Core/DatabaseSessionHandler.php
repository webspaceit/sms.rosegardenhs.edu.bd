<?php
declare(strict_types=1);

namespace App\Core;

use SessionHandlerInterface;

/**
 * Stores PHP sessions in MySQL so every web node shares the same session
 * state behind a load balancer.
 */
final class DatabaseSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $statement = Database::connection()->prepare(
            'SELECT payload FROM sessions WHERE id = :id AND last_activity > :threshold LIMIT 1'
        );
        $statement->execute(['id' => $id, 'threshold' => time() - (int) ini_get('session.gc_maxlifetime')]);
        $payload = $statement->fetchColumn();

        return $payload === false ? '' : (string) $payload;
    }

    public function write(string $id, string $data): bool
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO sessions (id, payload, last_activity) VALUES (:id, :payload, :activity)'
            . ' ON DUPLICATE KEY UPDATE payload = VALUES(payload), last_activity = VALUES(last_activity)'
        );
        return $statement->execute(['id' => $id, 'payload' => $data, 'activity' => time()]);
    }

    public function destroy(string $id): bool
    {
        $statement = Database::connection()->prepare('DELETE FROM sessions WHERE id = :id');
        return $statement->execute(['id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $statement = Database::connection()->prepare('DELETE FROM sessions WHERE last_activity < :threshold');
        $statement->execute(['threshold' => time() - $max_lifetime]);
        return $statement->rowCount();
    }
}
