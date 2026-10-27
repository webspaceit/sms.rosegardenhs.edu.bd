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
 * Printable identity cards for the students of a class.
 */
final class IdCardController
{
    public function index(Request $request): void
    {
        Guard::requirePermission('id-cards.view');

        $classId = (int) $request->input('class_id', 0);
        $sectionId = (int) $request->input('section_id', 0);

        $sql = 'SELECT s.id, s.name, s.name_bn, s.student_id, s.roll_no, s.photo,'
            . ' c.name AS class_name, sec.name AS section_name, g.phone AS guardian_phone'
            . ' FROM students s JOIN classes c ON c.id = s.class_id AND c.branch_id=s.branch_id JOIN sections sec ON sec.id = s.section_id AND sec.branch_id=s.branch_id'
            . ' LEFT JOIN student_guardian sg ON sg.student_id = s.id AND sg.branch_id=s.branch_id AND sg.is_primary = 1'
            . ' LEFT JOIN guardians g ON g.id = sg.guardian_id AND g.branch_id=sg.branch_id'
            . ' WHERE s.class_id = :class_id AND s.branch_id=:branch';

        $bindings = ['class_id' => $classId, 'branch' => BranchContext::id()];
        if ($sectionId > 0) {
            $sql .= ' AND s.section_id = :section_id';
            $bindings['section_id'] = $sectionId;
        }
        $sql .= ' ORDER BY s.roll_no ASC';

        $statement = Database::connection()->prepare($sql);
        $statement->execute($bindings);

        View::render('results/id-cards', [
            'title' => 'ID cards',
            'user' => Auth::user(),
            'classes' => Catalogue::classes(),
            'sections' => Catalogue::sections(),
            'classId' => $classId,
            'sectionId' => $sectionId,
            'students' => $statement->fetchAll(),
        ]);
    }
}
