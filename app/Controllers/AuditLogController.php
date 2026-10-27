<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BranchContext;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Core\View;

final class AuditLogController
{
    public function index(Request $request): void
    {
        Guard::requirePermission('settings.manage');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 50;
        $db = Database::connection();
        $branch = BranchContext::id();
        $total = (int) $db->query('SELECT COUNT(*) FROM audit_logs WHERE branch_id=' . $branch)->fetchColumn();
        $statement = $db->prepare('SELECT user_email,action,entity,entity_id,details,ip_address,created_at FROM audit_logs WHERE branch_id=:branch ORDER BY id DESC LIMIT :limit OFFSET :offset');
        $statement->bindValue(':branch', $branch, \PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $statement->execute();
        View::render('settings/audit-logs', [
            'title' => 'Audit log', 'user' => Auth::user(), 'records' => $statement->fetchAll(),
            'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage)), 'total' => $total,
        ]);
    }
}
