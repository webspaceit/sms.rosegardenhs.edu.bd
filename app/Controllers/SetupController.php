<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;
use App\Models\User;

final class SetupController
{
    public function form(Request $request): void
    {
        if (User::count() > 0) { http_response_code(404); View::render('errors/404', ['title' => 'Page not found']); return; }
        View::render('auth/setup', ['title' => 'Create administrator']);
    }
    public function createAdmin(Request $request): void
    {
        if (User::count() > 0 || !Csrf::verify($request)) { http_response_code(404); return; }
        $name = trim((string) $request->input('name'));
        $email = filter_var(trim((string) $request->input('email')), FILTER_VALIDATE_EMAIL);
        $password = (string) $request->input('password');
        if ($name === '' || !$email || strlen($password) < 10 || $password !== $request->input('password_confirmation')) {
            Toast::flash('Enter valid details. Use a password of at least 10 characters and make sure both password fields match.', 'error');
            header('Location: ' . Url::to('/setup')); exit;
        }
        User::createAdmin($name, $email, $password);
        Toast::flash('Administrator created. You can now sign in.');
        header('Location: ' . Url::to('/login')); exit;
    }
}
