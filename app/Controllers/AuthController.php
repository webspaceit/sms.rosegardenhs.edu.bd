<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Csrf;
use App\Core\LoginThrottle;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;

final class AuthController
{
    public function loginForm(Request $request): void
    {
        if (Auth::check()) { header('Location: ' . Url::to(Auth::homeRoute())); exit; }
        View::render('auth/login', ['title' => 'Sign in']);
    }
    public function login(Request $request): void
    {
        if (!Csrf::verify($request)) { http_response_code(419); Toast::flash('Your session expired. Please try again.', 'error'); header('Location: ' . Url::to('/login'), true, 419); exit; }
        $email = filter_var(trim((string) $request->input('email')), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            Toast::flash('The email or password is incorrect.', 'error');
            header('Location: ' . Url::to('/login')); exit;
        }
        if (LoginThrottle::blocked($email)) {
            Toast::flash('Too many unsuccessful sign-in attempts. Wait 15 minutes and try again.', 'error');
            header('Location: ' . Url::to('/login')); exit;
        }
        if (!Auth::attempt($email, (string) $request->input('password'))) {
            LoginThrottle::failed($email);
            AuditLog::record('auth.login_failed', 'users');
            Toast::flash('The email or password is incorrect.', 'error');
            header('Location: ' . Url::to('/login')); exit;
        }
        LoginThrottle::succeeded($email);
        AuditLog::record('auth.login', 'users', (int) (Auth::user()['id'] ?? 0));
        Toast::flash('Welcome back.');
        header('Location: ' . Url::to(Auth::homeRoute())); exit;
    }
    public function logout(Request $request): void
    {
        if (!Csrf::verify($request)) { http_response_code(419); return; }
        Auth::logout();
        Toast::flash('You have signed out.');
        header('Location: ' . Url::to('/login')); exit;
    }
}
