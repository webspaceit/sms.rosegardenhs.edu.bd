<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\BranchContext;

final class User
{
    /**
     * The columns a signed in user carries around.
     */
    private const COLUMNS = 'id, name, email, password, role, link_type, link_id, branch_id, is_active, created_at, updated_at';

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE id = :id AND branch_id=:branch LIMIT 1');
        $stmt->execute(['id' => $id, 'branch' => BranchContext::id()]);
        return $stmt->fetch() ?: null;
    }

    public static function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /**
     * Every account, with the name of the person it is linked to.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listing(): array
    {
        return Database::connection()->query(
            'SELECT u.id, u.name, u.email, u.role, u.link_type, u.link_id, u.branch_id, u.is_active, u.created_at,'
            . ' CASE u.link_type'
            . "   WHEN 'student' THEN (SELECT s.student_id FROM students s WHERE s.id = u.link_id)"
            . "   WHEN 'teacher' THEN (SELECT t.employee_id FROM teachers t WHERE t.id = u.link_id)"
            . "   WHEN 'guardian' THEN (SELECT g.name FROM guardians g WHERE g.id = u.link_id)"
            . '   ELSE NULL END AS linked_name'
            . ' FROM users u WHERE u.branch_id=' . BranchContext::id() . ' ORDER BY u.role, u.name'
        )->fetchAll();
    }

    /**
     * Create an account.
     *
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password, role, link_type, link_id, is_active, created_at, branch_id)'
            . ' VALUES (:name, :email, :password, :role, :link_type, :link_id, :is_active, NOW(), :branch_id)'
        );
        $stmt->execute($data + ['branch_id' => BranchContext::id()]);
        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Update an account. The password is left alone when it is blank.
     *
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data, ?string $password): void
    {
        $assignments = 'name = :name, email = :email, role = :role, link_type = :link_type, link_id = :link_id, is_active = :is_active';
        $data['id'] = $id;
        $data['branch_id'] = BranchContext::id();

        if ($password !== null && $password !== '') {
            $assignments .= ', password = :password';
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $stmt = Database::connection()->prepare('UPDATE users SET ' . $assignments . ' WHERE id = :id AND branch_id=:branch_id');
        $stmt->execute($data);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id AND branch_id=:branch');
        $stmt->execute(['id' => $id, 'branch' => BranchContext::id()]);
    }

    public static function createAdmin(string $name, string $email, string $password): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO users (name, email, password, role, is_active, created_at, branch_id) VALUES (:name, :email, :password, \'admin\', 1, NOW(), :branch_id)');
        $stmt->execute(['name' => $name, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT), 'branch_id' => BranchContext::id()]);
    }
}
