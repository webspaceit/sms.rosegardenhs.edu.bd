<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\BranchContext;

/**
 * The configuration of a result screen.
 *
 * Each of the six result screens — the report card, fail list, section
 * result, tabulation sheet, merit list and admit card — is set up by naming
 * the class, section, exam and academic year it covers. The rows are kept in
 * one table and separated by their type.
 */
final class ReportConfig
{
    /** The result screens and the type each one stores. */
    public const TYPES = [
        'report' => 'Report card',
        'fail-list' => 'Fail list',
        'section-result' => 'Section result',
        'tabulation' => 'Tabulation sheet',
        'merit-list' => 'Merit list',
        'admit-card' => 'Admit card',
    ];

    /**
     * The configurations of one result screen.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forType(string $type): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM report_configs WHERE type = :type AND branch_id=:branch ORDER BY class_name, section, academic_year DESC'
        );
        $statement->execute(['type' => $type, 'branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /** A single configuration. */
    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM report_configs WHERE id = :id AND branch_id=:branch LIMIT 1');
        $statement->execute(['id' => $id, 'branch' => BranchContext::id()]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Save a configuration.
     *
     * @param array<string, mixed> $data
     */
    public static function save(int $id, array $data): int
    {
        $db = Database::connection();

        if ($id > 0) {
            $statement = $db->prepare(
                'UPDATE report_configs SET type = :type, class_name = :class_name, section = :section,'
                . ' exam_name = :exam_name, academic_year = :academic_year, config = :config WHERE id = :id AND branch_id=:branch'
            );
            $statement->execute($data + ['id' => $id, 'branch' => BranchContext::id()]);
            return $id;
        }

        $statement = $db->prepare(
            'INSERT INTO report_configs (type, class_name, section, exam_name, academic_year, config, branch_id)'
            . ' VALUES (:type, :class_name, :section, :exam_name, :academic_year, :config, :branch)'
        );
        $statement->execute($data + ['branch' => BranchContext::id()]);
        return (int) $db->lastInsertId();
    }

    public static function delete(int $id): void
    {
        $statement = Database::connection()->prepare('DELETE FROM report_configs WHERE id = :id AND branch_id=:branch');
        $statement->execute(['id' => $id, 'branch' => BranchContext::id()]);
    }

    /**
     * The class id behind a class name, when the class exists.
     */
    public static function classId(string $className): int
    {
        $statement = Database::connection()->prepare('SELECT id FROM classes WHERE name = :name AND branch_id=:branch LIMIT 1');
        $statement->execute(['name' => $className, 'branch' => BranchContext::id()]);
        $id = $statement->fetchColumn();
        return $id === false ? 0 : (int) $id;
    }
}
