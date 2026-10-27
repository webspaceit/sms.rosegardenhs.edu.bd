<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;
use App\Models\DashboardData;

final class DashboardController
{
    public function index(Request $request): void
    {
        if (!Auth::check()) { Toast::flash('Please sign in to continue.', 'error'); header('Location: ' . Url::to('/login')); exit; }

        // A teacher, a student or a parent belongs in their own portal.
        $role = Auth::user()['role'] ?? '';
        if (in_array($role, ['teacher', 'student', 'parent'], true)) {
            header('Location: ' . Url::to('/portal'));
            exit;
        }

        $summary = \App\Core\Cache::remember('dashboard:' . \App\Core\BranchContext::id(), 30, static fn (): array => DashboardData::summary());
        View::render('dashboard/index', ['title' => 'Dashboard', 'user' => Auth::user()] + $summary);
    }
}
