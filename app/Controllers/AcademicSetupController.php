<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;
use App\Models\AcademicSetup;
use App\Models\AcademicGroup;
use PDOException;

final class AcademicSetupController
{
    public function index(Request $request): void
    {
        $this->requireAdmin();
        View::render('academics/index', [
            'title' => 'Academic setup',
            'user' => Auth::user(),
            'years' => AcademicSetup::years(),
            'classes' => AcademicSetup::classes(),
        ]);
    }

    public function academicGroups(Request $request): void
    {
        $this->requireAdmin();
        View::render('academics/groups', [
            'title' => 'Academic groups',
            'user' => Auth::user(),
            'groups' => AcademicGroup::all(),
        ]);
    }

    public function storeAcademicGroup(Request $request): void
    {
        $this->requireAdmin();
        if (!$this->verifyGroupCsrf($request)) return;
        $name = trim((string) $request->input('name', ''));
        if (!$this->validGroupName($name)) {
            Toast::flash('Enter a group name up to 100 characters.', 'error');
            header('Location: ' . Url::to('/academic-groups')); exit;
        }
        try {
            AcademicGroup::create($name);
            Toast::flash('Academic group added.');
        } catch (PDOException $error) {
            error_log((string) $error);
            Toast::flash('That academic group already exists or could not be saved.', 'error');
        }
        header('Location: ' . Url::to('/academic-groups')); exit;
    }

    public function updateAcademicGroup(Request $request): void
    {
        $this->requireAdmin();
        if (!$this->verifyGroupCsrf($request)) return;
        $id = (int) $request->input('id', 0);
        $name = trim((string) $request->input('name', ''));
        if ($id < 1 || !$this->validGroupName($name)) {
            Toast::flash('Enter a valid academic group name.', 'error');
            header('Location: ' . Url::to('/academic-groups')); exit;
        }
        try {
            if (AcademicGroup::rename($id, $name)) Toast::flash('Academic group updated.');
            else Toast::flash('Academic group not found.', 'error');
        } catch (PDOException $error) {
            error_log((string) $error);
            Toast::flash('That academic group name already exists or could not be saved.', 'error');
        }
        header('Location: ' . Url::to('/academic-groups')); exit;
    }

    public function deleteAcademicGroup(Request $request): void
    {
        $this->requireAdmin();
        if (!$this->verifyGroupCsrf($request)) return;
        $result = AcademicGroup::delete((int) $request->input('id', 0));
        if ($result === 'deleted') Toast::flash('Academic group deleted.');
        elseif ($result === 'in_use') Toast::flash('This group is assigned to students. Change or remove those assignments before deleting it.', 'error');
        else Toast::flash('Academic group not found.', 'error');
        header('Location: ' . Url::to('/academic-groups')); exit;
    }

    private function verifyGroupCsrf(Request $request): bool
    {
        if (Csrf::verify($request)) return true;
        http_response_code(419);
        Toast::flash('Your session expired. Please try again.', 'error');
        header('Location: ' . Url::to('/academic-groups'), true, 419); exit;
    }

    private function validGroupName(string $name): bool
    {
        preg_match_all('/./us', $name, $characters);
        $length = isset($characters[0]) ? count($characters[0]) : strlen($name);
        return $name !== '' && $length <= 100;
    }

    public function storeYear(Request $request): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrf($request)) { return; }
        $name = trim((string) $request->input('name', ''));
        $start = (string) $request->input('start_date', '');
        $end = (string) $request->input('end_date', '');
        $startDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $start);
        $endDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $end);
        if ($name === '' || strlen($name) > 20 || !$startDate || !$endDate
            || $startDate->format('Y-m-d') !== $start || $endDate->format('Y-m-d') !== $end || $endDate < $startDate) {
            Toast::flash('Enter an academic year name and a valid date range.', 'error');
            header('Location: ' . Url::to('/academics')); exit;
        }
        try {
            AcademicSetup::createYear($name, $start, $end);
            Toast::flash('Academic year created. Set it as current when ready.');
        } catch (PDOException $error) {
            error_log((string) $error);
            Toast::flash('That academic year already exists or could not be saved.', 'error');
        }
        header('Location: ' . Url::to('/academics')); exit;
    }

    public function setCurrentYear(Request $request): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrf($request)) { return; }
        if (AcademicSetup::setCurrentYear((int) $request->input('id', 0))) {
            Toast::flash('Current academic year updated.');
        } else {
            Toast::flash('Academic year not found.', 'error');
        }
        header('Location: ' . Url::to('/academics')); exit;
    }

    public function storeClass(Request $request): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrf($request)) { return; }
        $level = (int) $request->input('numeric_level', 0);
        $name = trim((string) $request->input('name', ''));
        $shift = (string) $request->input('shift', '');
        if ($level < 1 || $level > 10 || $name === '' || strlen($name) > 50 || !in_array($shift, ['Morning', 'Day', 'Both'], true)) {
            Toast::flash('Enter a class name, level from 1 to 10, and valid shift.', 'error');
            header('Location: ' . Url::to('/academics')); exit;
        }
        try {
            AcademicSetup::createClass($name, $level, $shift);
            Toast::flash('Class created. Add one or more sections to it.');
        } catch (PDOException $error) {
            error_log((string) $error);
            Toast::flash('That class name or level already exists.', 'error');
        }
        header('Location: ' . Url::to('/academics')); exit;
    }

    public function storeSection(Request $request): void
    {
        $this->requireAdmin();
        if (!$this->verifyCsrf($request)) { return; }
        $classId = (int) $request->input('class_id', 0);
        $name = trim((string) $request->input('name', ''));
        $room = trim((string) $request->input('room_no', ''));
        $validClassIds = array_map(static fn (array $class): int => (int) $class['id'], AcademicSetup::classes());
        if (!in_array($classId, $validClassIds, true) || $name === '' || strlen($name) > 20 || strlen($room) > 30) {
            Toast::flash('Choose a class and enter a valid section name.', 'error');
            header('Location: ' . Url::to('/academics')); exit;
        }
        try {
            AcademicSetup::createSection($classId, $name, $room !== '' ? $room : null);
            Toast::flash('Section added.');
        } catch (PDOException $error) {
            error_log((string) $error);
            Toast::flash('That section already exists in the selected class.', 'error');
        }
        header('Location: ' . Url::to('/academics')); exit;
    }

    private function verifyCsrf(Request $request): bool
    {
        if (Csrf::verify($request)) { return true; }
        http_response_code(419);
        Toast::flash('Your session expired. Please try again.', 'error');
        header('Location: ' . Url::to('/academics'), true, 419); exit;
    }

    private function requireAdmin(): void
    {
        if (Auth::check() && in_array(Auth::user()['role'] ?? '', ['admin', 'superadmin'], true)) { return; }
        if (Auth::check()) {
            Toast::flash('You do not have permission to change academic setup.', 'error');
            header('Location: ' . Url::to('/dashboard')); exit;
        }
        Toast::flash('Please sign in to continue.', 'error');
        header('Location: ' . Url::to('/login')); exit;
    }
}
