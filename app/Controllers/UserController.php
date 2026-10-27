<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Csrf;
use App\Core\Guard;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;
use App\Models\Permission;
use App\Models\Person;
use App\Models\User;

/**
 * The user accounts.
 *
 * An account carries the role it acts as and, for a teacher, a student or a
 * parent, the person record it is linked to. Linking an account is what
 * gives a person their own portal.
 */
final class UserController
{
    public function index(Request $request): void
    {
        Guard::requirePermission('settings.manage');

        View::render('users/index', [
            'title' => 'User accounts',
            'user' => Auth::user(),
            'users' => User::listing(),
            'roles' => Permission::ROLES,
        ]);
    }

    public function create(Request $request): void
    {
        Guard::requirePermission('settings.manage');

        View::render('users/form', [
            'title' => 'New account',
            'user' => Auth::user(),
            'account' => null,
            'roles' => Permission::ROLES,
            'students' => Person::studentOptions(),
            'teachers' => Person::teacherOptions(),
            'guardians' => Person::guardianOptions(),
        ]);
    }

    public function store(Request $request): void
    {
        Guard::requirePermission('settings.manage');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/users'), true, 419);
            exit;
        }

        $data = $this->validated($request);
        if ($data === null) {
            header('Location: ' . Url::to('/users/new'));
            exit;
        }

        $password = (string) $request->input('password', '');
        if (strlen($password) < 8) {
            Toast::flash('The password must be at least 8 characters long.', 'error');
            header('Location: ' . Url::to('/users/new'));
            exit;
        }

        try {
            $id = User::create($data + ['password' => password_hash($password, PASSWORD_DEFAULT)]);
        } catch (\PDOException $error) {
            \App\Core\ErrorLog::write($error);
            Toast::flash('That email address is already in use.', 'error');
            header('Location: ' . Url::to('/users/new'));
            exit;
        }

        AuditLog::record('user.created', 'users', $id, ['status' => $data['role']]);

        Toast::flash('The account was created.');
        header('Location: ' . Url::to('/users'));
        exit;
    }

    public function edit(Request $request): void
    {
        Guard::requirePermission('settings.manage');

        $account = User::find((int) $request->input('id', 0));
        if ($account === null) {
            Toast::flash('Account not found.', 'error');
            header('Location: ' . Url::to('/users'));
            exit;
        }

        View::render('users/form', [
            'title' => 'Edit account',
            'user' => Auth::user(),
            'account' => $account,
            'roles' => Permission::ROLES,
            'students' => Person::studentOptions(),
            'teachers' => Person::teacherOptions(),
            'guardians' => Person::guardianOptions(),
        ]);
    }

    public function update(Request $request): void
    {
        Guard::requirePermission('settings.manage');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/users'), true, 419);
            exit;
        }

        $id = (int) $request->input('id', 0);
        if (User::find($id) === null) {
            Toast::flash('Account not found.', 'error');
            header('Location: ' . Url::to('/users'));
            exit;
        }

        $data = $this->validated($request);
        if ($data === null) {
            header('Location: ' . Url::to('/users/edit?id=' . $id));
            exit;
        }

        $password = (string) $request->input('password', '');
        if ($password !== '' && strlen($password) < 8) {
            Toast::flash('The password must be at least 8 characters long.', 'error');
            header('Location: ' . Url::to('/users/edit?id=' . $id));
            exit;
        }

        try {
            User::update($id, $data, $password);
        } catch (\PDOException $error) {
            \App\Core\ErrorLog::write($error);
            Toast::flash('That email address is already in use.', 'error');
            header('Location: ' . Url::to('/users/edit?id=' . $id));
            exit;
        }

        AuditLog::record('user.updated', 'users', $id, ['status' => $data['role']]);

        Toast::flash('The account was updated.');
        header('Location: ' . Url::to('/users'));
        exit;
    }

    public function destroy(Request $request): void
    {
        Guard::requirePermission('settings.manage');

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/users'), true, 419);
            exit;
        }

        $id = (int) $request->input('id', 0);
        if ($id === (int) (Auth::user()['id'] ?? 0)) {
            Toast::flash('You cannot remove the account you are signed in with.', 'error');
            header('Location: ' . Url::to('/users'));
            exit;
        }

        User::delete($id);
        AuditLog::record('user.deleted', 'users', $id);
        Toast::flash('The account was removed.');
        header('Location: ' . Url::to('/users'));
        exit;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validated(Request $request): ?array
    {
        $name = trim((string) $request->input('name', ''));
        $email = filter_var(trim((string) $request->input('email', '')), FILTER_VALIDATE_EMAIL);
        $role = (string) $request->input('role', 'admin');
        $linkType = trim((string) $request->input('link_type', ''));
        $linkId = (int) $request->input('link_id', 0);

        $errors = [];
        if ($name === '') {
            $errors[] = 'The name is required.';
        }
        if ($email === false) {
            $errors[] = 'A valid email address is required.';
        }
        if (!isset(Permission::ROLES[$role])) {
            $errors[] = 'Choose a role for the account.';
        }
        if ($linkType !== '' && !in_array($linkType, ['student', 'teacher', 'guardian'], true)) {
            $errors[] = 'Choose a valid link type.';
        }

        if ($errors !== []) {
            Toast::flash(implode(' ', $errors), 'error');
            return null;
        }

        return [
            'name' => $name,
            'email' => (string) $email,
            'role' => $role,
            'link_type' => $linkType !== '' ? $linkType : null,
            'link_id' => $linkType !== '' && $linkId > 0 ? $linkId : null,
            'is_active' => (int) $request->input('is_active', '1') === 1 ? 1 : 0,
        ];
    }
}
