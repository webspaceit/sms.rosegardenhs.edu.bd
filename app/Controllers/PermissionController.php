<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Guard;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;
use App\Models\Permission;

/**
 * The role and permission matrix.
 */
final class PermissionController
{
    public function index(Request $request): void
    {
        Guard::requireAdmin();

        $groups = Permission::catalogue();
        if ($groups === []) {
            Toast::flash('No permissions are defined yet. Run scripts/seed-permissions.php to create the default catalogue and grants.', 'error');
        }

        View::render('settings/permissions', [
            'title' => 'Permissions & roles',
            'user' => Auth::user(),
            'groups' => $groups,
            'roles' => Permission::ROLES,
            'roleMap' => Permission::roleMap(),
        ]);
    }

    public function update(Request $request): void
    {
        Guard::requireAdmin();

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/permissions'), true, 419);
            exit;
        }

        $grants = $request->input('grants', []);
        $grants = is_array($grants) ? $grants : [];

        $saved = 0;
        foreach (Permission::ROLES as $role => $label) {
            $ids = $grants[$role] ?? [];
            $ids = is_array($ids) ? array_map('intval', $ids) : [];
            Permission::grantToRole($role, $ids);
            $saved++;
        }

        Toast::flash('Permissions updated for ' . $saved . ' roles.');
        header('Location: ' . Url::to('/permissions'));
        exit;
    }
}
