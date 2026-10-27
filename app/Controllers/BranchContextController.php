<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\BranchContext;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;

final class BranchContextController
{
    public function switch(Request $request): void
    {
        if (!BranchContext::canSwitch()) {
            http_response_code(403);
            exit;
        }
        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/dashboard'));
            exit;
        }
        $branchId = (int) $request->input('branch_id', 0);
        if (!BranchContext::isActive($branchId)) {
            Toast::flash('Choose an active branch.', 'error');
            header('Location: ' . Url::to('/dashboard'));
            exit;
        }
        $_SESSION['active_branch_id'] = $branchId;
        AuditLog::record('branch.switched', 'branches', $branchId);
        Toast::flash('Active branch changed.');
        $returnTo = (string) $request->input('return_to', '/dashboard');
        if (!str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//') || str_contains($returnTo, "\r") || str_contains($returnTo, "\n")) $returnTo = '/dashboard';
        header('Location: ' . Url::to($returnTo));
        exit;
    }
}
