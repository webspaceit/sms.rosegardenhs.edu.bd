<?php
declare(strict_types=1);

namespace App\Core;

/** Per-user academic-session selection, scoped to the working campus. */
final class AcademicYearContext
{
    /** @return array<int, array{id:int,name:string,is_current:int}> */
    public static function years(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, is_current, start_date, end_date FROM academic_years WHERE branch_id = :branch ORDER BY is_current DESC, start_date DESC'
        );
        $statement->execute(['branch' => BranchContext::id()]);
        return $statement->fetchAll();
    }

    /** @return array{id:int,name:string,is_current:int}|null */
    public static function selected(): ?array
    {
        $branchId = BranchContext::id();
        $selections = $_SESSION['active_academic_year_by_branch'] ?? [];
        $selectedId = is_array($selections) ? (int) ($selections[$branchId] ?? 0) : 0;
        if ($selectedId > 0) {
            $statement = Database::connection()->prepare(
                'SELECT id, name, is_current, start_date, end_date FROM academic_years WHERE id = :id AND branch_id = :branch LIMIT 1'
            );
            $statement->execute(['id' => $selectedId, 'branch' => $branchId]);
            $year = $statement->fetch();
            if ($year) return $year;
            if (!isset($_SESSION['active_academic_year_by_branch']) || !is_array($_SESSION['active_academic_year_by_branch'])) {
                $_SESSION['active_academic_year_by_branch'] = [];
            }
            unset($_SESSION['active_academic_year_by_branch'][$branchId]);
        }

        $statement = Database::connection()->prepare(
            'SELECT id, name, is_current, start_date, end_date FROM academic_years WHERE branch_id = :branch ORDER BY is_current DESC, start_date DESC LIMIT 1'
        );
        $statement->execute(['branch' => $branchId]);
        $year = $statement->fetch();
        return $year ?: null;
    }

    public static function select(int $id): bool
    {
        $branchId = BranchContext::id();
        $statement = Database::connection()->prepare(
            'SELECT id FROM academic_years WHERE id = :id AND branch_id = :branch LIMIT 1'
        );
        $statement->execute(['id' => $id, 'branch' => $branchId]);
        if ($statement->fetchColumn() === false) return false;
        if (!isset($_SESSION['active_academic_year_by_branch']) || !is_array($_SESSION['active_academic_year_by_branch'])) {
            $_SESSION['active_academic_year_by_branch'] = [];
        }
        $_SESSION['active_academic_year_by_branch'][$branchId] = $id;
        return true;
    }
}
