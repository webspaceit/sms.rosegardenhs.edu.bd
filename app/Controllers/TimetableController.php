<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BranchContext;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Core\View;
use App\Models\Catalogue;

/**
 * The weekly class timetable.
 *
 * The days of the week run across the top and the periods run down the side,
 * which is how a timetable is printed on the wall.
 */
final class TimetableController
{
    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public function index(Request $request): void
    {
        Guard::requirePermission('classes.manage');

        $classId = (int) $request->input('class_id', 0);
        $sectionId = (int) $request->input('section_id', 0);

        $grid = [];
        $periods = [];

        if ($classId > 0) {
            $sql = 'SELECT cr.*, s.name AS subject_name, t.name AS teacher_name FROM class_routines cr'
                . ' JOIN subjects s ON s.id = cr.subject_id AND s.branch_id=cr.branch_id'
                . ' LEFT JOIN teachers t ON t.id = cr.teacher_id AND t.branch_id=cr.branch_id'
                . ' WHERE cr.class_id = :class_id AND cr.branch_id=:branch';
            $bindings = ['class_id' => $classId, 'branch' => BranchContext::id()];

            if ($sectionId > 0) {
                $sql .= ' AND cr.section_id = :section_id';
                $bindings['section_id'] = $sectionId;
            }

            $statement = Database::connection()->prepare($sql);
            $statement->execute($bindings);

            foreach ($statement->fetchAll() as $row) {
                $start = (string) $row['start_time'];
                $periods[$start] = (string) $row['end_time'];
                $grid[(string) $row['day_of_week']][$start] = $row;
            }
        }

        ksort($periods);

        View::render('timetable/index', [
            'title' => 'Weekly timetable',
            'user' => Auth::user(),
            'classes' => Catalogue::classes(),
            'sections' => Catalogue::sections(),
            'classId' => $classId,
            'sectionId' => $sectionId,
            'days' => self::DAYS,
            'periods' => $periods,
            'grid' => $grid,
        ]);
    }
}
