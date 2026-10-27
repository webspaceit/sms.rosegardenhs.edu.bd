<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\BranchContext;

/**
 * Small lookup lists shared by the module forms.
 *
 * Every method returns an id => label map, which is exactly the shape the
 * generic form expects for a select field.
 */
final class Catalogue
{
    /** @return array<int, string> id => label */
    public static function classes(): array
    {
        return self::options('SELECT id, name FROM classes ORDER BY numeric_level', 'name');
    }

    /** @return array<int, string> id => label */
    public static function subjects(): array
    {
        return self::options('SELECT id, name FROM subjects ORDER BY name', 'name');
    }

    /** @return array<int, string> id => label */
    public static function teachers(): array
    {
        return self::options('SELECT id, name FROM teachers ORDER BY name', 'name');
    }

    /** @return array<int, string> id => label */
    public static function students(): array
    {
        return self::options('SELECT id, CONCAT(name, " (", student_id, ")") AS label FROM students ORDER BY name', 'label');
    }

    /** @return array<int, string> id => label */
    public static function sections(): array
    {
        return self::options('SELECT s.id, CONCAT(c.name, " · ", s.name) AS label FROM sections s JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id ORDER BY c.numeric_level, s.name', 'label');
    }

    /** @return array<int, string> id => label */
    public static function feeHeads(): array
    {
        return self::options('SELECT id, name FROM fee_heads ORDER BY sort_order, name', 'name');
    }

    /** @return array<int, string> id => label */
    public static function exams(): array
    {
        return self::options('SELECT id, name FROM exams ORDER BY start_date DESC, name', 'name');
    }

    /** @return array<int, string> id => label */
    public static function transportRoutes(): array
    {
        return self::options('SELECT id, name FROM transport_routes ORDER BY name', 'name');
    }

    /** @return array<int, string> id => label */
    public static function transportStops(): array
    {
        return self::options('SELECT id, name FROM transport_stops ORDER BY sort_order, name', 'name');
    }

    /** @return array<int, string> id => label */
    public static function hostelRooms(): array
    {
        return self::options('SELECT id, room_no FROM hostel_rooms ORDER BY room_no', 'room_no');
    }

    /** @return array<int, string> id => label */
    public static function inventoryCategories(): array
    {
        return self::options('SELECT id, name FROM inventory_categories ORDER BY name', 'name');
    }

    /** @return array<int, string> id => label */
    public static function books(): array
    {
        return self::options('SELECT id, title FROM books ORDER BY title', 'title');
    }

    /** @return array<int, string> id => label */
    public static function academicYears(): array
    {
        return self::options('SELECT id, name FROM academic_years ORDER BY start_date DESC', 'name');
    }

    /** @return array<int, string> id => label */
    public static function users(): array
    {
        return self::options('SELECT id, name FROM users WHERE role <> "student" ORDER BY name', 'name');
    }

    /**
     * Run a query and turn it into an id => label map.
     *
     * @return array<int, string>
     */
    private static function options(string $sql, string $labelColumn): array
    {
        $branchId = BranchContext::id();
        preg_match('/\bFROM\s+([A-Za-z_][A-Za-z0-9_]*)(?:\s+(?:AS\s+)?([A-Za-z_][A-Za-z0-9_]*))?/i', $sql, $matches);
        $table = (string) ($matches[1] ?? '');
        $alias = (string) ($matches[2] ?? $table);
        if (in_array(strtoupper($alias), ['WHERE','JOIN','LEFT','RIGHT','INNER','OUTER','ORDER','GROUP'], true)) $alias = $table;
        $where = stripos($sql, ' WHERE ') !== false ? ' AND ' : ' WHERE ';
        if ($table !== '') {
            $orderAt = stripos($sql, ' ORDER BY ');
            $clause = $where . $alias . '.branch_id=' . $branchId . ' ';
            $sql = $orderAt === false ? $sql . $clause : substr($sql, 0, $orderAt) . $clause . substr($sql, $orderAt);
        }
        $rows = Database::connection()->query($sql)->fetchAll();
        $options = [];
        foreach ($rows as $row) {
            $options[(int) $row['id']] = (string) $row[$labelColumn];
        }
        return $options;
    }
}
